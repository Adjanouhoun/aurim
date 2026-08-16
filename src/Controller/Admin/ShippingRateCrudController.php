<?php

namespace App\Controller\Admin;

use App\Entity\ShippingRate;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class ShippingRateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return ShippingRate::class; }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('market', 'Marché');
        yield ChoiceField::new('fulfillmentType', 'Mode')->setChoices(['Livraison à domicile' => 'delivery', 'Retrait dans un dépôt' => 'pickup']);
        yield TextField::new('label', 'Nom affiché')->setHelp('Exemple : Livraison à Nouakchott ou Dépôt AURIM Dakar.');
        yield TextField::new('city', 'Ville ou zone');
        yield TextField::new('addressLine', 'Adresse du dépôt')->setHelp('Obligatoire pour un retrait en dépôt.')->hideOnIndex();
        yield IntegerField::new('amountMinor', 'Montant en unité minimale')
            ->setHelp('MRU : saisissez 15000 pour afficher 150,00 MRU. XOF/GNF : saisissez directement 2500 pour afficher 2 500.');
        yield IntegerField::new('minimumDays', 'Délai minimum');
        yield IntegerField::new('maximumDays', 'Délai maximum');
        yield BooleanField::new('active', 'Actif');
    }
}
