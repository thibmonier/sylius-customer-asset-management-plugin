<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class TmonierSyliusCustomerAssetManagementExtension extends Extension
{
    /** @param array<array-key, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Skeleton only: services, entities and migrations arrive with US-056 onwards.
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_customer_asset_management';
    }
}
