<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    private const UPLOAD_SUBDIR = 'uploads/articles';

    public function __construct(
        private readonly ImageOptimizer $imageOptimizer,
        private readonly ArticleRepository $articleRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setSearchFields(['title', 'excerpt', 'content', 'slug'])
            ->setDefaultSort(['publishedAt' => 'DESC', 'createdAt' => 'DESC']);
    }

    /**
     * Charge le JS qui decoche visuellement les autres toggles "A la une"
     * quand l'admin en active un dans le listing.
     */
    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addJsFile('/admin/featured-toggle.js');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Contenu')->setIcon('fa fa-pen');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title')->hideOnIndex();
        yield TextareaField::new('excerpt', 'Chapô / résumé')
            ->setHelp('Max 280 caractères — servira d\'intro et de meta description si vide.')
            ->hideOnIndex();
        yield TextEditorField::new('content', 'Corps de l\'article')
            ->setNumOfRows(25)
            ->hideOnIndex()
            ->setHelp('Markdown ou HTML simple. Le rendu final utilise Twig markdown.');

        yield FormField::addTab('Média')->setIcon('fa fa-image');
        yield ImageField::new('coverImageUrl', 'Image de couverture')
            ->setBasePath('/' . self::UPLOAD_SUBDIR)
            ->setUploadDir('public/' . self::UPLOAD_SUBDIR)
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp,image/gif'])
            ->setHelp('JPG, PNG, WebP ou GIF. L\'image est automatiquement redimensionnée (max 1600×900) et convertie en WebP optimisé. Format recommandé : 16/9.')
            ->hideOnIndex();
        yield TextField::new('coverImageAlt', 'Alt image')->hideOnIndex()
            ->setHelp('Description courte pour l\'accessibilité et le SEO.');

        yield FormField::addTab('Publication')->setIcon('fa fa-calendar');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(fn () => [
                'Brouillon'  => Article::STATUS_DRAFT,
                'Programmé'  => Article::STATUS_SCHEDULED,
                'Publié'     => Article::STATUS_PUBLISHED,
                'Archivé'    => Article::STATUS_ARCHIVED,
            ])
            ->renderAsBadges([
                Article::STATUS_DRAFT     => 'secondary',
                Article::STATUS_SCHEDULED => 'warning',
                Article::STATUS_PUBLISHED => 'success',
                Article::STATUS_ARCHIVED  => 'light',
            ]);
        yield DateTimeField::new('publishedAt', 'Date de publication')->setFormat('dd/MM/yyyy HH:mm');
        yield BooleanField::new('featured', 'À la une')
            ->setHelp('Si activé, l\'article remplace la Une actuelle sur la home. Un seul "À la une" à la fois — le plus récent coché gagne.');
        yield BooleanField::new('alert', 'Alerte urgente')
            ->setHelp('Si activé, l\'article est épinglé dans le panneau Signal (sidebar) en haut de la home comme SEV-1.');
        yield IntegerField::new('readingMinutes', 'Durée de lecture (min)')->hideOnIndex();

        yield FormField::addTab('Classement')->setIcon('fa fa-folder-tree');
        yield AssociationField::new('category', 'Catégorie');
        yield AssociationField::new('tags', 'Tags')->setFormTypeOption('by_reference', false);
        yield AssociationField::new('author', 'Auteur')->hideOnIndex();

        yield FormField::addTab('SEO')->setIcon('fa fa-magnifying-glass');
        yield TextField::new('seoTitle', 'Titre SEO')->hideOnIndex()
            ->setHelp('Laisser vide pour utiliser le titre de l\'article.');
        yield TextareaField::new('seoDescription', 'Meta description')->hideOnIndex();
    }

    /**
     * Apres creation, optimise l'image uploadee + garantit l'unicite de la Une.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->optimizeCover($entityInstance);
        parent::persistEntity($em, $entityInstance);
        $this->enforceSingleFeatured($entityInstance);
    }

    /**
     * Apres edition, re-optimise si l'image a change + garantit l'unicite de la Une.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->optimizeCover($entityInstance);
        parent::updateEntity($em, $entityInstance);
        $this->enforceSingleFeatured($entityInstance);
    }

    /**
     * Si l'article courant est marque "a la une", on desactive ce flag sur
     * tous les autres articles. Appele apres persist() pour que l'article
     * courant ait deja son ID en base (cas creation).
     */
    private function enforceSingleFeatured(Article $article): void
    {
        if (!$article->isFeatured()) {
            return;
        }
        $this->articleRepository->demoteOtherFeatured($article->getId());
    }

    /**
     * Execute l'optim sur l'image et met a jour l'entite avec le nom final.
     * Ne fait rien si la valeur est une URL externe ou deja optimisee.
     */
    private function optimizeCover(Article $article): void
    {
        $current = $article->getCoverImageUrl();
        if ($current === null || $current === '') {
            return;
        }

        // URL externe : on laisse tel quel (retro-compatibilite avec les anciens champs).
        if (preg_match('#^https?://#i', $current) || str_starts_with($current, '//')) {
            return;
        }

        // EasyAdmin stocke juste le basename dans ce champ.
        $filename = basename($current);

        // Deja un .webp ? on saute l'optim (evite double compression).
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp') {
            return;
        }

        $optimized = $this->imageOptimizer->optimize(
            filename:  $filename,
            subdir:    self::UPLOAD_SUBDIR,
            maxWidth:  1600,
            maxHeight: 900,
            quality:   82,
        );

        if ($optimized !== $filename) {
            $article->setCoverImageUrl($optimized);
        }
    }
}
