<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CompanyFleetScope;

// Loaded only when tmonier/sylius-company-accounts-plugin is installed.
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('tmonier_equipment.fleet_scope.company', CompanyFleetScope::class)
        ->args([
            service('tmonier_equipment.fleet_scope.customer'),
            service('tmonier_company_accounts.repository.company_member'),
        ])
        ->tag('kernel.reset', ['method' => 'reset'])
    ;
    $services->alias('tmonier_equipment.fleet_scope', 'tmonier_equipment.fleet_scope.company');
};
