<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\ImageOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private const UPLOAD_SUBDIR = 'uploads/avatars';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ImageOptimizer $imageOptimizer,
    ) {}

    public static function getEntityFqcn(): string { return User::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Utilisateur')->setEntityLabelInPlural('Utilisateurs');
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email');
        yield TextField::new('displayName', 'Nom affiché');
        yield SlugField::new('slug')->setTargetFieldName('displayName')->hideOnIndex();
        yield TextareaField::new('bio')->hideOnIndex();
        yield ImageField::new('avatarUrl', 'Avatar')
            ->setBasePath('/' . self::UPLOAD_SUBDIR)
            ->setUploadDir('public/' . self::UPLOAD_SUBDIR)
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->setFormTypeOption('attr', ['accept' => 'image/jpeg,image/png,image/webp'])
            ->setHelp('Photo carrée recommandée. Auto-redimensionnée à 400×400 et convertie en WebP.')
            ->hideOnIndex();
        yield ChoiceField::new('roles')
            ->allowMultipleChoices()
            ->setChoices([
                'Rédacteur'       => 'ROLE_EDITOR',
                'Administrateur'  => 'ROLE_ADMIN',
                'Super-admin'     => 'ROLE_SUPER_ADMIN',
            ]);
        yield BooleanField::new('totpEnabled', '2FA activé')->hideOnForm();

        $passwordField = TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
                'mapped'         => false,
            ])
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();
        yield $passwordField;
    }

    public function createEntity(string $entityFqcn): User
    {
        return new User();
    }

    /** @param User $entityInstance */
    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $em, $entityInstance): void
    {
        $this->hashPlainPasswordIfPresent($entityInstance);
        $this->optimizeAvatar($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    /** @param User $entityInstance */
    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $em, $entityInstance): void
    {
        $this->hashPlainPasswordIfPresent($entityInstance);
        $this->optimizeAvatar($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    private function hashPlainPasswordIfPresent(User $user): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if (!$request) return;

        $formData = $request->request->all('User') ?: $request->request->all('UserForm') ?: [];
        $plain = $formData['plainPassword']['first'] ?? null;

        if (!empty($plain)) {
            $user->setPassword($this->hasher->hashPassword($user, $plain));
        }
    }

    private function optimizeAvatar(User $user): void
    {
        $current = $user->getAvatarUrl();
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
        $optimized = $this->imageOptimizer->optimize(
            filename:  $filename,
            subdir:    self::UPLOAD_SUBDIR,
            maxWidth:  400,
            maxHeight: 400,
            quality:   85,
        );
        if ($optimized !== $filename) {
            $user->setAvatarUrl($optimized);
        }
    }
}
