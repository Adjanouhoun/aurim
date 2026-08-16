<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\Product;
use App\Entity\Warehouse;
use App\Form\ProductIngredientType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/products')]
        private readonly string $productImagesDirectory,
    ) {}

    public static function getEntityFqcn(): string { return Product::class; }

    public function createEntity(string $entityFqcn): Product
    {
        return (new Product())->setActive(false);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Produit')->setEntityLabelInPlural('Produits')->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identité du produit')->setHelp('Ces informations apparaissent en haut de la fiche client.');
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom commercial')->setRequired(true);
        yield TextField::new('sku', 'Référence SKU')
            ->setHelp('Référence unique indiquée dans le catalogue fournisseur, par exemple TVCNBC150.')
            ->setRequired(true);
        yield SlugField::new('slug')->setTargetFieldName('name');
        yield AssociationField::new('category', 'Catégorie')->setRequired(true);
        yield TextField::new('type', 'Type de soin')->setHelp('Exemple : Gommage corporel illuminateur.')->setRequired(true);
        yield TextField::new('size', 'Contenance')->setHelp('Exemple : 450 g ou 50 ml.')->setRequired(true);

        yield FormField::addFieldset('Textes de la fiche client');
        yield TextareaField::new('shortDescription', 'Résumé produit')
            ->setHelp('Texte affiché sous le nom et dans les cartes produit.')
            ->setFormTypeOption('attr.rows', 4)
            ->setRequired(true)
            ->hideOnIndex();
        yield TextareaField::new('description', 'Description complète')
            ->setHelp('Contenu de l’accordéon « Description ».')
            ->setFormTypeOption('attr.rows', 7)
            ->setRequired(true)
            ->hideOnIndex();
        yield CollectionField::new('benefits', 'Bénéfices visibles')
            ->setHelp('Ajoutez les bénéfices courts affichés sous forme de pastilles et dans l’accordéon.')
            ->setEntryType(TextType::class)
            ->setFormTypeOption('entry_options', [
                'label' => false,
                'constraints' => [new NotBlank(), new Length(max: 120)],
                'attr' => ['placeholder' => 'Exemple : Exfolie en douceur'],
            ])
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield CollectionField::new('ingredients', 'Actifs & ingrédients')
            ->setHelp('Chaque ligne alimente directement l’accordéon « Actifs & ingrédients ».')
            ->setEntryType(ProductIngredientType::class)
            ->setEntryIsComplex()
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield TextareaField::new('usageInstructions', 'Conseils d’utilisation')
            ->setHelp('Contenu de l’accordéon « Conseils d’utilisation ».')
            ->setFormTypeOption('attr.rows', 5)
            ->setRequired(true)
            ->hideOnIndex();

        yield FormField::addFieldset('Traduction anglaise')->setHelp('Les champs laissés vides utilisent automatiquement le contenu français.');
        yield TextField::new('nameEn', 'Nom commercial (EN)')->setRequired(false)->hideOnIndex();
        yield TextField::new('typeEn', 'Type de soin (EN)')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('shortDescriptionEn', 'Résumé produit (EN)')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('descriptionEn', 'Description complète (EN)')->setRequired(false)->hideOnIndex();
        yield CollectionField::new('benefitsEn', 'Bénéfices (EN)')
            ->setEntryType(TextType::class)
            ->setFormTypeOption('entry_options', ['label' => false])
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield CollectionField::new('ingredientsEn', 'Actifs & ingrédients (EN)')
            ->setEntryType(ProductIngredientType::class)->setEntryIsComplex()
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield TextareaField::new('usageInstructionsEn', 'Conseils d’utilisation (EN)')->setRequired(false)->hideOnIndex();

        yield FormField::addFieldset('Traduction arabe')->setHelp('Les champs laissés vides utilisent automatiquement le contenu français.');
        yield TextField::new('nameAr', 'Nom commercial (AR)')->setRequired(false)->hideOnIndex()->setFormTypeOption('attr.dir', 'rtl');
        yield TextField::new('typeAr', 'Type de soin (AR)')->setRequired(false)->hideOnIndex()->setFormTypeOption('attr.dir', 'rtl');
        yield TextareaField::new('shortDescriptionAr', 'Résumé produit (AR)')->setRequired(false)->hideOnIndex()->setFormTypeOption('attr.dir', 'rtl');
        yield TextareaField::new('descriptionAr', 'Description complète (AR)')->setRequired(false)->hideOnIndex()->setFormTypeOption('attr.dir', 'rtl');
        yield CollectionField::new('benefitsAr', 'Bénéfices (AR)')
            ->setEntryType(TextType::class)
            ->setFormTypeOption('entry_options', ['label' => false, 'attr' => ['dir' => 'rtl']])
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield CollectionField::new('ingredientsAr', 'Actifs & ingrédients (AR)')
            ->setEntryType(ProductIngredientType::class)->setEntryIsComplex()
            ->allowAdd()->allowDelete()->onlyOnForms();
        yield TextareaField::new('usageInstructionsAr', 'Conseils d’utilisation (AR)')->setRequired(false)->hideOnIndex()->setFormTypeOption('attr.dir', 'rtl');

        yield FormField::addFieldset('Présentation visuelle');
        yield ImageField::new('imagePath', 'Photo')->setBasePath('/')->hideOnForm();
        yield Field::new('imageFile', 'Charger une photo')
            ->setFormType(FileType::class)
            ->setFormTypeOptions([
                'required' => Crud::PAGE_NEW === $pageName,
                'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])],
            ])
            ->onlyOnForms();
        yield ChoiceField::new('imagePosition', 'Position de la photo')
            ->setChoices(['Centrée' => 'center', 'À gauche' => 'left', 'À droite' => 'right', 'En haut' => 'top', 'En bas' => 'bottom'])
            ->renderExpanded(false);
        yield BooleanField::new('active', 'Publié sur le site')
            ->setHelp('Publiez seulement après avoir renseigné les prix et le stock par marché.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $this->storeUploadedImage($entityInstance);
        }
        parent::persistEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof Product) {
            $this->initializeMarketData($entityManager, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $this->storeUploadedImage($entityInstance);
        }
        parent::updateEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof Product) {
            $this->initializeMarketData($entityManager, $entityInstance);
        }
    }

    private function storeUploadedImage(Product $product): void
    {
        $file = $product->getImageFile();
        if (null === $file) {
            return;
        }

        $baseName = $this->slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->lower();
        $fileName = sprintf('%s-%s.%s', $baseName ?: 'produit-aurim', bin2hex(random_bytes(5)), $file->guessExtension() ?: 'jpg');
        $file->move($this->productImagesDirectory, $fileName);
        $product->setImagePath('uploads/products/'.$fileName)->setImageFile(null);
    }

    private function initializeMarketData(EntityManagerInterface $entityManager, Product $product): void
    {
        foreach ($entityManager->getRepository(Warehouse::class)->findAll() as $warehouse) {
            $inventory = $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $product, 'warehouse' => $warehouse]);
            if (!$inventory instanceof Inventory) {
                $entityManager->persist((new Inventory())->setProduct($product)->setWarehouse($warehouse));
            }
        }

        foreach ($entityManager->getRepository(Market::class)->findAll() as $market) {
            if ('US' === $market->getCountryCode()) {
                continue;
            }
            $price = $entityManager->getRepository(MarketPrice::class)->findOneBy(['product' => $product, 'market' => $market]);
            if (!$price instanceof MarketPrice) {
                $entityManager->persist((new MarketPrice())->setProduct($product)->setMarket($market)->setPublished(false));
            }
        }
        $entityManager->flush();
    }
}
