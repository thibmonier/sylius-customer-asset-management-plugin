# Sylius Customer Asset Management Plugin

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A B2B customer declares the equipment they own ("Truck model X, plate AB-123-CD") in their account,
and the catalogue can be filtered to the spare parts compatible with it. Behaviour: [`SPEC.md`](SPEC.md);
decisions: [`docs/adr/`](docs/adr/).

**Status: 0.1.0 in preparation (not released yet).**

## Requirements

| | Supported |
|---|---|
| PHP | 8.2, 8.3, 8.4, 8.5 |
| Sylius | 2.2 (2.2.9+) and 2.3 |
| Symfony | 6.4, 7.4, 8.0 (8.0 with Sylius 2.3) |
| Database | MySQL 8.4, MariaDB 11.4, PostgreSQL 17 |

## Installation

```bash
composer require tmonier/sylius-customer-asset-management-plugin
```

Register the bundle in `config/bundles.php`:

```php
Tmonier\SyliusCustomerAssetManagementPlugin\TmonierSyliusCustomerAssetManagementPlugin::class => ['all' => true],
```

Import the configuration in `config/packages/_sylius.yaml` and the routes in `config/routes.yaml`:

```yaml
# config/packages/_sylius.yaml
imports:
    - { resource: "@TmonierSyliusCustomerAssetManagementPlugin/config/config.yaml" }

# config/routes.yaml
tmonier_sylius_customer_asset_management:
    resource: "@TmonierSyliusCustomerAssetManagementPlugin/config/routes.yaml"
```

Run the migrations (the plugin prepends its own to the Doctrine migrations configuration):

```bash
bin/console doctrine:migrations:migrate
```

## Configuration

```yaml
tmonier_sylius_customer_asset_management:
    # Role an administrator needs to manage the equipment models (default: any administrator).
    admin_role: ROLE_ADMINISTRATION_ACCESS
    # Number of machines a customer can declare.
    max_assets_per_customer: 50
```

## Administration: equipment models and compatibility

*Catalog > Equipment models* lists, creates, edits and deletes the equipment models (a code, up to 64
characters and unique, and a name per language). The code cannot be changed once the model exists.
A model without a name in the language of the administration is shown by its code.

On the form of a product variant, *Compatible equipment models* ticks the models the variant fits.
Compatibility is per variant, not per product. Deleting a model removes its compatibility links and
never the variants. The list shows how many variants each model has.

To restrict the screens, set `admin_role` to a role of your own (for example `ROLE_EQUIPMENT_MANAGER`)
and give it to the administrators concerned: the others get a 403 on the model screens and do not
see the field on the variant form.

## Development

Requirements: PHP 8.2+, Composer, a MySQL database (see `tests/TestApplication/.env`).

```bash
composer install
php -d memory_limit=2G vendor/bin/ecs check
php -d memory_limit=2G vendor/bin/phpstan analyse
vendor/bin/rector process --dry-run
php -d memory_limit=2G vendor/bin/phpunit
```

<!-- vitrine:demo-b2b:start -->
## B2B demo

Status: **not present in the demo** yet. The [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo) does not include this plugin, and no demo page or data exists for it.

- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)
<!-- vitrine:demo-b2b:end -->

## License

MIT, see [LICENSE](LICENSE).
