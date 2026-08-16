<?php

namespace App\Controller\Admin;

use App\Entity\Warehouse;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class WarehouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Warehouse::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Entrepôt')
            ->setEntityLabelInPlural('Entrepôts')
            ->setDefaultSort(['market.name' => 'ASC']);
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield TextField::new('code', 'Code interne');
        yield AssociationField::new('market', 'Marché / pays')->setRequired(true);
        yield BooleanField::new('central', 'Stock central');
        yield BooleanField::new('active', 'Actif');
    }
}
