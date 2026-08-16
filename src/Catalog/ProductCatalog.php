<?php

namespace App\Catalog;

final class ProductCatalog
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return array_values($this->products());
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        return $this->products()[$slug] ?? null;
    }

    /** @return array<string, array<string, mixed>> */
    private function products(): array
    {
        return [
            'brightening-cleanser' => [
                'sku' => 'TVCNBC150',
                'slug' => 'brightening-cleanser',
                'name' => 'Brightening Cleanser',
                'category' => ['slug' => 'nettoyants', 'name' => 'Nettoyants', 'position' => 10],
                'type' => 'Nettoyant visage illuminateur',
                'size' => '150 ml',
                'shortDescription' => 'Un nettoyant doux qui élimine les impuretés et révèle une peau fraîche, souple et lumineuse.',
                'description' => 'Ce gel nettoyant associe le curcuma, la vitamine C et la niacinamide pour débarrasser la peau des impuretés, de l’excès de sébum et du maquillage. Sa mousse équilibrée nettoie sans dessécher et aide à préserver une peau douce, confortable et éclatante.',
                'benefits' => ['Nettoie sans dessécher', 'Aide à raviver l’éclat', 'Laisse la peau douce et fraîche'],
                'ingredients' => [
                    ['name' => 'Curcuma (curcumine)', 'text' => 'Un actif antioxydant qui aide à apaiser la peau et à estomper l’apparence des zones irrégulières.'],
                    ['name' => 'Vitamine C stabilisée', 'text' => 'Le 3-O-Ethyl Ascorbic Acid aide à soutenir l’éclat et la fermeté de la peau.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à affiner l’apparence des pores, à réduire les rougeurs visibles et à renforcer la barrière cutanée.'],
                ],
                'usage' => 'Matin et soir, appliquez une petite quantité sur le visage humide. Massez délicatement, puis rincez abondamment. Évitez le contour des yeux.',
                'imagePath' => 'images/products/catalogue/brightening-cleanser.jpg',
                'imagePosition' => 'center',
            ],
            'glowing-toner' => [
                'sku' => 'TVCNGT120',
                'slug' => 'glowing-toner',
                'name' => 'Glowing Toner',
                'category' => ['slug' => 'toniques', 'name' => 'Toniques', 'position' => 20],
                'type' => 'Lotion tonique éclat',
                'size' => '120 ml',
                'shortDescription' => 'Une lotion tonique apaisante qui hydrate, rafraîchit et aide à uniformiser visiblement le teint.',
                'description' => 'Infusée de curcuma, de vitamine C et de niacinamide, cette lotion tonique aide à réveiller les teints ternes, à atténuer l’apparence des rougeurs et à soutenir l’éclat naturel. Sa texture légère hydrate et laisse la peau fraîche, souple et lumineuse.',
                'benefits' => ['Hydrate et rafraîchit', 'Aide à uniformiser le teint', 'Soutient la barrière cutanée'],
                'ingredients' => [
                    ['name' => 'Curcuma', 'text' => 'Aide à apaiser la peau et à préserver un teint visiblement uniforme.'],
                    ['name' => 'Vitamine C', 'text' => 'Contribue à raviver l’éclat des peaux ternes.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à réduire l’apparence des rougeurs et à renforcer la barrière cutanée.'],
                ],
                'usage' => 'Après le nettoyage, appliquez matin et soir sur le visage et le cou à l’aide des mains ou d’un coton. Ne rincez pas.',
                'imagePath' => 'images/products/catalogue/glowing-toner.jpg',
                'imagePosition' => 'center',
            ],
            'radiance-boosting-serum' => [
                'sku' => 'TVCNRBS30',
                'slug' => 'radiance-boosting-serum',
                'name' => 'Radiance Boosting Serum',
                'category' => ['slug' => 'serums', 'name' => 'Sérums', 'position' => 30],
                'type' => 'Sérum visage éclat',
                'size' => '30 ml',
                'shortDescription' => 'Un sérum concentré qui hydrate, affine visiblement le grain de peau et révèle un teint rayonnant.',
                'description' => 'Ce sérum avancé associe un curcuma riche en antioxydants, une vitamine C stabilisée et de la niacinamide pour aider à révéler une peau lumineuse et d’apparence uniforme. Le sodium hyaluronate retient l’hydratation et contribue à une peau plus souple et rebondie.',
                'benefits' => ['Booste l’éclat', 'Hydrate intensément', 'Aide à lisser et uniformiser'],
                'ingredients' => [
                    ['name' => 'Tétrahydrocurcumine', 'text' => 'Une forme de curcuma antioxydante qui aide à apaiser la peau et à estomper l’apparence des taches.'],
                    ['name' => 'Vitamine C stabilisée', 'text' => 'Le 3-O-Ethyl Ascorbic Acid aide à soutenir l’éclat et la protection antioxydante.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à affiner la texture, calmer les rougeurs visibles et soutenir l’élasticité.'],
                    ['name' => 'Sodium hyaluronate', 'text' => 'Aide à retenir l’eau pour une peau visiblement plus souple et rebondie.'],
                ],
                'usage' => 'Appliquez deux à trois gouttes sur le visage et le cou propres, matin et/ou soir, avant la crème. Le matin, terminez par une protection solaire.',
                'imagePath' => 'images/products/catalogue/radiance-boosting-serum.jpg',
                'imagePosition' => 'center',
            ],
            'brightening-cream' => [
                'sku' => 'TVCNBC60',
                'slug' => 'brightening-cream',
                'name' => 'Brightening Cream',
                'category' => ['slug' => 'hydratants', 'name' => 'Hydratants', 'position' => 40],
                'type' => 'Crème visage illuminatrice',
                'size' => '60 g',
                'shortDescription' => 'Une crème nourrissante légère qui hydrate, adoucit et aide à révéler un teint plus lumineux.',
                'description' => 'Cette crème associe le curcuma, la vitamine C stabilisée et la niacinamide à du beurre de karité et à des agents hydratants. Sa texture légère, non grasse et rapidement absorbée aide à préserver l’hydratation tout en laissant la peau douce, souple et rayonnante.',
                'benefits' => ['Nourrit sans effet gras', 'Aide à estomper l’apparence des taches', 'Laisse la peau souple et lumineuse'],
                'ingredients' => [
                    ['name' => 'Curcuma (curcumine)', 'text' => 'Aide à apaiser et à améliorer visiblement l’uniformité du teint.'],
                    ['name' => 'Vitamine C stabilisée', 'text' => 'Le 3-O-Ethyl Ascorbic Acid contribue à l’éclat et au soutien du collagène.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à affiner l’apparence des pores, à réduire les rougeurs visibles et à améliorer l’élasticité.'],
                    ['name' => 'Beurre de karité et humectants', 'text' => 'La glycérine, la bétaïne et le sodium hyaluronate aident à nourrir et à retenir l’hydratation.'],
                ],
                'usage' => 'Appliquez matin et soir sur le visage et le cou, après le sérum. Massez délicatement jusqu’à absorption. Le matin, complétez avec une protection solaire.',
                'imagePath' => 'images/products/catalogue/brightening-cream.jpg',
                'imagePosition' => 'center',
            ],
            'radiance-boosting-body-wash' => [
                'sku' => 'TVCNRBBW300',
                'slug' => 'radiance-boosting-body-wash',
                'name' => 'Radiance Boosting Body Wash',
                'category' => ['slug' => 'nettoyants', 'name' => 'Nettoyants', 'position' => 10],
                'type' => 'Gel douche illuminateur',
                'size' => '300 ml',
                'shortDescription' => 'Un gel douche doux qui nettoie sans dessécher et laisse la peau souple, hydratée et éclatante.',
                'description' => 'Ce gel douche associe curcuma, vitamine C stabilisée et niacinamide à des tensioactifs doux d’origine coco. Il nettoie la peau sans la dessécher, tandis que l’aloe vera et la glycérine contribuent à maintenir sa douceur et son hydratation.',
                'benefits' => ['Nettoie en douceur', 'Aide à raviver l’éclat', 'Laisse la peau souple et hydratée'],
                'ingredients' => [
                    ['name' => 'Curcuma (curcumine)', 'text' => 'Aide à apaiser et à améliorer visiblement l’éclat de la peau.'],
                    ['name' => 'Vitamine C stabilisée', 'text' => 'Le 3-O-Ethyl Ascorbic Acid soutient l’éclat et la protection antioxydante.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à affiner la texture et à renforcer la barrière cutanée.'],
                    ['name' => 'Aloe vera et glycérine', 'text' => 'Aident à préserver la douceur et l’hydratation après le rinçage.'],
                ],
                'usage' => 'Appliquez sur peau humide, faites mousser par mouvements circulaires, puis rincez abondamment. Convient à un usage quotidien.',
                'imagePath' => 'images/products/catalogue/radiance-boosting-body-wash.jpg',
                'imagePosition' => 'center',
            ],
            'radiance-body-lotion' => [
                'sku' => 'TVCNRBL300',
                'slug' => 'radiance-body-lotion',
                'name' => 'Radiance Body Lotion',
                'category' => ['slug' => 'hydratants', 'name' => 'Hydratants', 'position' => 40],
                'type' => 'Lait corporel illuminateur',
                'size' => '300 ml',
                'shortDescription' => 'Un lait corporel léger qui hydrate, adoucit et aide à révéler une peau plus lisse et uniforme.',
                'description' => 'Cette lotion corporelle à absorption rapide associe curcuma antioxydant, vitamine C stabilisée et niacinamide. Elle aide à apaiser, à améliorer l’apparence des zones irrégulières et à renforcer la barrière cutanée tout en laissant la peau douce et affinée.',
                'benefits' => ['Hydrate sans effet collant', 'Aide à unifier l’apparence de la peau', 'Améliore souplesse et confort'],
                'ingredients' => [
                    ['name' => 'Curcuma (curcumine)', 'text' => 'Aide à apaiser et à estomper l’apparence des zones irrégulières.'],
                    ['name' => 'Vitamine C liposoluble', 'text' => 'L’Ascorbyl Tetraisopalmitate soutient l’éclat et l’apparence de la fermeté.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à renforcer la barrière cutanée et à retenir l’hydratation.'],
                ],
                'usage' => 'Appliquez quotidiennement sur peau propre et sèche, puis massez jusqu’à absorption. Insistez sur les zones qui manquent d’uniformité.',
                'imagePath' => 'images/products/catalogue/radiance-body-lotion.jpg',
                'imagePosition' => 'center',
            ],
            'radiance-boosting-body-scrub' => [
                'sku' => 'TVCNRBBS450',
                'slug' => 'radiance-boosting-body-scrub',
                'name' => 'Radiance-Boosting Body Scrub',
                'category' => ['slug' => 'gommages', 'name' => 'Gommages', 'position' => 50],
                'type' => 'Gommage corporel illuminateur',
                'size' => '450 g',
                'shortDescription' => 'Un gommage au sel marin qui exfolie, lisse et révèle l’éclat naturel de la peau.',
                'description' => 'Ce gommage généreux associe des grains fins de sel marin au curcuma, à la vitamine C et à la niacinamide. Il aide à éliminer les cellules mortes, à lisser la texture et à raviver l’éclat. L’huile de coco et le beurre de karité contribuent à préserver le confort après l’exfoliation.',
                'benefits' => ['Exfolie les cellules mortes', 'Aide à lisser le grain de peau', 'Nourrit et ravive l’éclat'],
                'ingredients' => [
                    ['name' => 'Sel marin à grains fins', 'text' => 'Exfolie mécaniquement pour une peau visiblement plus lisse.'],
                    ['name' => 'Curcuma, vitamine C et niacinamide', 'text' => 'Aident à soutenir l’éclat, à uniformiser l’apparence de la peau et à renforcer sa barrière.'],
                    ['name' => 'Huile de coco et beurre de karité', 'text' => 'Aident à nourrir la peau et à limiter la sensation de sécheresse après le gommage.'],
                ],
                'usage' => 'Appliquez sur peau humide, massez délicatement par mouvements circulaires puis rincez. Utilisez une à deux fois par semaine.',
                'imagePath' => 'images/products/catalogue/radiance-boosting-body-scrub.jpg',
                'imagePosition' => 'center',
            ],
            'radiant-body-butter' => [
                'sku' => 'TVCNRBB350',
                'slug' => 'radiant-body-butter',
                'name' => 'Radiant Body Butter',
                'category' => ['slug' => 'hydratants', 'name' => 'Hydratants', 'position' => 40],
                'type' => 'Beurre corporel nourrissant',
                'size' => '350 g',
                'shortDescription' => 'Un beurre corporel riche qui nourrit intensément et laisse la peau douce, lisse et lumineuse.',
                'description' => 'Ce beurre corporel nourrissant associe extrait de racine de curcuma, vitamine C stabilisée et niacinamide à un riche mélange de beurres et d’huiles. Il offre une hydratation profonde et aide à laisser la peau souple, lisse et rayonnante.',
                'benefits' => ['Nourrit intensément', 'Aide à renforcer la barrière cutanée', 'Laisse la peau douce et lumineuse'],
                'ingredients' => [
                    ['name' => 'Extrait de racine de curcuma', 'text' => 'Aide à apaiser la peau et à estomper l’apparence des zones irrégulières.'],
                    ['name' => 'Vitamine C liposoluble', 'text' => 'L’Ascorbyl Tetraisopalmitate aide à soutenir l’éclat et l’apparence uniforme de la peau.'],
                    ['name' => 'Niacinamide', 'text' => 'Contribue au renforcement de la barrière cutanée.'],
                    ['name' => 'Beurres et huiles nourrissants', 'text' => 'Apportent une hydratation profonde et un confort durable.'],
                ],
                'usage' => 'Appliquez quotidiennement sur peau propre, de préférence après la douche. Massez jusqu’à absorption et insistez sur les zones sèches.',
                'imagePath' => 'images/products/catalogue/radiant-body-butter.jpg',
                'imagePosition' => 'center',
            ],
            'radiance-body-oil' => [
                'sku' => 'TVCNRBO200',
                'slug' => 'radiance-body-oil',
                'name' => 'Radiance Body Oil',
                'category' => ['slug' => 'hydratants', 'name' => 'Hydratants', 'position' => 40],
                'type' => 'Huile corporelle éclat',
                'size' => '200 ml',
                'shortDescription' => 'Une huile corporelle légère qui nourrit, assouplit et sublime la peau sans fini lourd.',
                'description' => 'Cette huile corporelle légère et rapidement absorbée associe curcuma antioxydant, vitamine C stabilisée et niacinamide à des huiles d’amande douce, de jojoba et de rose musquée. Elle nourrit intensément et aide à améliorer la souplesse tout en sublimant l’éclat naturel de la peau.',
                'benefits' => ['Nourrit en profondeur', 'Améliore la souplesse', 'Sublime l’éclat sans fini lourd'],
                'ingredients' => [
                    ['name' => 'Tétrahydrocurcumine', 'text' => 'Une forme antioxydante du curcuma qui aide à apaiser et à uniformiser visiblement.'],
                    ['name' => 'Vitamine C liposoluble', 'text' => 'L’Ascorbyl Tetraisopalmitate aide à soutenir l’éclat et l’apparence de la fermeté.'],
                    ['name' => 'Niacinamide', 'text' => 'Aide à renforcer la barrière cutanée.'],
                    ['name' => 'Amande douce, jojoba et rose musquée', 'text' => 'Un mélange d’huiles qui nourrit et contribue à une peau plus souple.'],
                ],
                'usage' => 'Appliquez quelques pressions sur peau propre, idéalement encore légèrement humide. Massez jusqu’à absorption. Utilisez seule ou après le lait corporel.',
                'imagePath' => 'images/products/catalogue/radiance-body-oil.jpg',
                'imagePosition' => 'center',
            ],
        ];
    }
}
