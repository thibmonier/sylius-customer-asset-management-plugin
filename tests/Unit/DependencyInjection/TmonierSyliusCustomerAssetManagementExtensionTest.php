<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tmonier\SyliusCustomerAssetManagementPlugin\DependencyInjection\TmonierSyliusCustomerAssetManagementExtension;

final class TmonierSyliusCustomerAssetManagementExtensionTest extends TestCase
{
    public function testAliasIsStable(): void
    {
        self::assertSame('tmonier_sylius_customer_asset_management', (new TmonierSyliusCustomerAssetManagementExtension())->getAlias());
    }

    public function testLoadRegistersNothingInTheSkeleton(): void
    {
        $container = new ContainerBuilder();

        (new TmonierSyliusCustomerAssetManagementExtension())->load([], $container);

        self::assertCount(1, $container->getDefinitions());
    }
}
