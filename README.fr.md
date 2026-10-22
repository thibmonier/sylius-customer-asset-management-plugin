# Sylius Customer Asset Management Plugin

> English: [README.md](README.md) <!-- vitrine:lang -->

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Un client B2B déclare l'équipement qu'il possède (« Camion modèle X, plaque AB-123-CD ») dans son
compte, et le catalogue peut être filtré sur les pièces détachées compatibles. Comportement :
[`SPEC.md`](SPEC.md) ; décisions : [`docs/adr/`](docs/adr/).

**Statut : 0.1.0 en préparation (pas encore publiée).**

## Prérequis

| | Versions prises en charge |
|---|---|
| PHP | 8.2, 8.3, 8.4, 8.5 |
| Sylius | 2.2 (2.2.9+) et 2.3 |
| Symfony | 6.4, 7.4, 8.0 (8.0 avec Sylius 2.3) |
| Base de données | MySQL 8.4, MariaDB 11.4, PostgreSQL 17 |

## Installation

```bash
composer require tmonier/sylius-customer-asset-management-plugin
```

Déclarez le bundle dans `config/bundles.php` :

```php
Tmonier\SyliusCustomerAssetManagementPlugin\TmonierSyliusCustomerAssetManagementPlugin::class => ['all' => true],
```

Importez la configuration dans `config/packages/_sylius.yaml` et les routes dans `config/routes.yaml` :

```yaml
# config/packages/_sylius.yaml
imports:
    - { resource: "@TmonierSyliusCustomerAssetManagementPlugin/config/config.yaml" }

# config/routes.yaml
tmonier_sylius_customer_asset_management:
    resource: "@TmonierSyliusCustomerAssetManagementPlugin/config/routes.yaml"
```

Lancez les migrations (le plugin ajoute les siennes à la configuration des migrations Doctrine) :

```bash
bin/console doctrine:migrations:migrate
```

## Configuration

```yaml
tmonier_sylius_customer_asset_management:
    # Rôle nécessaire à un administrateur pour gérer les modèles d'équipement (par défaut : tout administrateur).
    admin_role: ROLE_ADMINISTRATION_ACCESS
    # Nombre de machines qu'un client peut déclarer.
    max_assets_per_customer: 50
```

## Administration : modèles d'équipement et compatibilité

*Catalogue > Modèles d'équipement* liste, crée, modifie et supprime les modèles d'équipement (un code
unique de 64 caractères au plus, et un nom par langue). Le code n'est plus modifiable une fois le
modèle créé. Un modèle sans nom dans la langue de l'administration est affiché par son code.

Sur le formulaire d'une variante, *Modèles d'équipement compatibles* coche les modèles sur lesquels
la variante se monte. La compatibilité est par variante, pas par produit. Supprimer un modèle retire
ses liens de compatibilité, jamais les variantes. La liste indique le nombre de variantes de chaque modèle.

Pour restreindre les écrans, donnez à `admin_role` un rôle à vous (par exemple `ROLE_EQUIPMENT_MANAGER`)
et attribuez-le aux administrateurs concernés : les autres reçoivent un 403 sur les écrans des modèles
et ne voient pas le champ du formulaire de variante.

## Développement

Prérequis : PHP 8.2+, Composer, une base MySQL (voir `tests/TestApplication/.env`).

```bash
composer install
php -d memory_limit=2G vendor/bin/ecs check
php -d memory_limit=2G vendor/bin/phpstan analyse
vendor/bin/rector process --dry-run
php -d memory_limit=2G vendor/bin/phpunit
```

<!-- vitrine:demo-b2b:start -->
## Démo B2B

Statut : **non présent dans la démo** pour l'instant. La [démo B2B Sylius](https://github.com/thibmonier/sylius-b2b-demo) n'intègre pas ce plugin, et aucune page ni donnée de démo n'existe pour lui.

- Auteur : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)
<!-- vitrine:demo-b2b:end -->

## Licence

MIT, voir [LICENSE](LICENSE).
