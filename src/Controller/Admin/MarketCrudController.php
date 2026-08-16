<?php

namespace App\Controller\Admin;

use App\Entity\Market;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class MarketCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Market::class; }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Pays');
        yield TextField::new('countryCode', 'Code pays')->setHelp('Code ISO sur deux lettres, par exemple SN.');
        yield TextField::new('currencyCode', 'Devise')->setHelp('Code ISO sur trois lettres, par exemple XOF.');
        yield AssociationField::new('warehouse', 'Entrepôt associé')->hideOnForm();
        yield BooleanField::new('active', 'Actif');
    }
}
