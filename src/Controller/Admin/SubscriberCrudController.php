<?php

namespace App\Controller\Admin;

use App\Entity\Subscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubscriberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Subscriber::class; }

    public function configureActions(Actions $actions): Actions
    {
        // Les abonnés sont créés via le formulaire public — on limite la création depuis l'admin
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email');
        yield TextField::new('firstName', 'Prénom')->hideOnIndex();
        yield ChoiceField::new('status')
            ->setChoices([
                'En attente'   => Subscriber::STATUS_PENDING,
                'Confirmé'     => Subscriber::STATUS_CONFIRMED,
                'Désinscrit'   => Subscriber::STATUS_UNSUBSCRIBED,
                'Email invalide' => Subscriber::STATUS_BOUNCED,
            ])
            ->renderAsBadges([
                Subscriber::STATUS_PENDING     => 'warning',
                Subscriber::STATUS_CONFIRMED   => 'success',
                Subscriber::STATUS_UNSUBSCRIBED => 'light',
                Subscriber::STATUS_BOUNCED     => 'danger',
            ]);
        yield TextField::new('source')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Inscrit le')->hideOnForm();
        yield DateTimeField::new('confirmedAt', 'Confirmé le')->hideOnForm();
    }
}
