# AURIM

[![Qualité AURIM](https://github.com/Adjanouhoun/aurim/actions/workflows/quality.yml/badge.svg)](https://github.com/Adjanouhoun/aurim/actions/workflows/quality.yml)

Boutique e-commerce Symfony dédiée aux produits cosmétiques AURIM.

La plateforme gère notamment :

- le catalogue, les catégories et les images produits ;
- les marchés Mauritanie, Sénégal, Mali et Guinée ;
- les prix et les stocks par entrepôt ;
- le retrait en dépôt ou la livraison locale ;
- le paiement Mobile Money à validation manuelle et le paiement en espèces au retrait ;
- le suivi des commandes, les notifications par e-mail et l'administration.

## Prérequis

- PHP 8.2 ou supérieur ;
- Composer ;
- Docker et Docker Compose ;
- Symfony CLI, recommandé pour le serveur local.

## Installation locale

```bash
composer install
cp .env.example .env.local
docker compose up -d
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:catalog:seed
php bin/console app:admin:create adresse@example.com --super-admin
symfony server:start
```

La boutique est ensuite accessible sur `http://localhost:8000/` et l'administration sur `http://localhost:8000/admin`.

Les paramètres propres à l'environnement (base de données, messagerie et secrets) doivent être renseignés dans `.env.local`. Ce fichier n'est jamais versionné.

Pour créer un administrateur limité à un pays, indiquez le code de son marché :

```bash
php bin/console app:admin:create responsable-senegal@example.com --market=SN
```

## Données de démonstration facultatives

Pour ajouter les moyens de paiement, les prix, les stocks et les tarifs de livraison de démonstration :

```bash
php bin/console doctrine:fixtures:load \
  --group=payment-methods \
  --group=demo-prices \
  --group=demo-stocks \
  --group=demo-shipping \
  --append
```

## Tests et contrôles

La suite de tests utilise une base SQLite en mémoire et désactive l’envoi réel des e-mails. Elle ne modifie donc pas les commandes, les stocks ou les utilisateurs de votre base locale.

GitHub exécute automatiquement ces contrôles à chaque Pull Request et à chaque modification de `main`. Dependabot surveille chaque semaine les dépendances Composer et GitHub Actions.

```bash
php bin/phpunit
php bin/console lint:container
php bin/console lint:twig templates
```
