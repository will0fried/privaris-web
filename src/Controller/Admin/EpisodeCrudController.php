<?php

namespace App\Controller\Admin;

use App\Entity\Episode;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class EpisodeCrudController extends AbstractCrudController
{
    private const UPLOAD_SUBDIR = 'uploads/episodes';

    public function __construct(
        private readonly ImageOptimizer $imageOptimizer,
    ) {}

    public static function getEntityFqcn(): string { return Episode::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Épisode')
            ->setEntityLabelInPlural('Épisodes')
            ->setSearchFields(['title', 'excerpt', 'description'])
            ->setDefaultSort(['publishedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Épisode')->setIcon('fa fa-microphone');
        yield IntegerField::new('number', 'N°');
        yield TextField::new('season', 'Saison')->setHelp('Ex: S1, S2...');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title')->hideOnIndex();
        yield TextareaField::new('excerpt', 'Accroche')->setHelp('Max 280 caractères')->hideOnIndex();
        yield TextareaField::new('description', 'Description')->setNumOfRows(8)->hideOnIndex();
        yield TextareaField::new('transcript', 'Transcription (optionnelle)')->setNumOfRows(15)->hideOnIndex();

        yield FormField::addTab('Fichier audio')->setIcon('fa fa-file-audio');
        yield UrlField::new('audioUrl', 'URL du fichier audio')
            ->setHelp('URL publique du MP3. Upload via ton hébergeur ou un CDN audio (Castos, Podbean...).');
        yield IntegerField::new('audioSizeBytes', 'Taille (octets)')
            ->setHelp('Requis par le flux RSS iTunes.')->hideOnIndex();
        yield TextField::new('duration', 'Durée (HH:MM:SS)')->hideOnIndex();
        yield ChoiceField::new('audioMimeType', 'Type MIME')
            ->setChoices(['MP3' => 'audio/mpeg', 'M4A' => 'audio/x-m4a', 'OGG' => 'audio/ogg'])
            ->hideOnIndex();
        yield ImageField::new('coverImageUrl', 'Cover de l\'épisode')
            ->setBasePath('/' . self::UPLOAD_SUBDIR)
            ->setUploadDir('public/' . self::UPLOAD_SUBDIR)
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setHelp('Format carré recommandé (1400×1400 min pour Apple Podcasts). Auto-redimensionnée et optimisée en WebP.')
            ->hideOnIndex();

        yield FormField::addTab('Publication')->setIcon('fa fa-calendar');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Brouillon' => Episode::STATUS_DRAFT,
                'Programmé' => Episode::STATUS_SCHEDULED,
                'Publié'    => Episode::STATUS_PUBLISHED,
            ])
            ->renderAsBadges([
                Episode::STATUS_DRAFT     => 'secondary',
                Episode::STATUS_SCHEDULED => 'warning',
                Episode::STATUS_PUBLISHED => 'success',
            ]);
        yield DateTimeField::new('publishedAt', 'Publié le');
        yield BooleanField::new('explicit', 'Contenu explicite')->hideOnIndex();
        yield ChoiceField::new('episodeType', 'Type')
            ->setChoices(['Épisode complet' => 'full', 'Bande-annonce' => 'trailer', 'Bonus' => 'bonus'])
            ->hideOnIndex();

        yield FormField::addTab('Article lié')->setIcon('fa fa-link');
        yield AssociationField::new('article', 'Article du blog associé')
            ->setHelp('Facultatif : si l\'épisode accompagne un article du blog.');
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->optimizeCover($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->optimizeCover($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    private function optimizeCover(Episode $episode): void
    {
        $current = $episode->getCoverImageUrl();
        if ($current === null || $current === '') {
            return;
        }
        if (preg_match('#^https?://#i', $current) || str_starts_with($current, '//')) {
            return;
        }
        $filename = basename($current);
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp') {
            return;
        }
        // Cover podcast : carree, 1400 max (Apple Podcasts accepte 1400-3000, on cible 1400).
        $optimized = $this->imageOptimizer->optimize(
            filename:  $filename,
            subdir:    self::UPLOAD_SUBDIR,
            maxWidth:  1400,
            maxHeight: 1400,
            quality:   85,
        );
        if ($optimized !== $filename) {
            $episode->setCoverImageUrl($optimized);
        }
    }
}
