<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tmonier\SyliusCompanyAccountsPlugin\DependencyInjection\TmonierSyliusCompanyAccountsExtension;
use Tmonier\SyliusCustomerAssetManagementPlugin\DependencyInjection\TmonierSyliusCustomerAssetManagementExtension;

final class TmonierSyliusCustomerAssetManagementExtensionTest extends TestCase
{
    public function testAliasIsStable(): void
    {
        self::assertSame('tmonier_sylius_customer_asset_management', (new TmonierSyliusCustomerAssetManagementExtension())->getAlias());
    }

    public function testDefaultsGiveTheAdministrationRoleAndFiftyMachinesPerCustomer(): void
    {
        $container = new ContainerBuilder();

        (new TmonierSyliusCustomerAssetManagementExtension())->load([], $container);

        self::assertSame('ROLE_ADMINISTRATION_ACCESS', $container->getParameter('tmonier_equipment.admin_role'));
        self::assertSame(50, $container->getParameter('tmonier_equipment.max_assets_per_customer'));
    }

    public function testTheLimitAndTheRoleAreConfigurable(): void
    {
        $container = new ContainerBuilder();

        (new TmonierSyliusCustomerAssetManagementExtension())->load([['admin_role' => 'ROLE_FLEET', 'max_assets_per_customer' => 10]], $container);

        self::assertSame('ROLE_FLEET', $container->getParameter('tmonier_equipment.admin_role'));
        self::assertSame(10, $container->getParameter('tmonier_equipment.max_assets_per_customer'));
    }

    public function testTheEquipmentModelResourceIsRegisteredWithItsTranslation(): void
    {
        $container = new ContainerBuilder();

        (new TmonierSyliusCustomerAssetManagementExtension())->load([], $container);

        self::assertTrue($container->hasDefinition('tmonier_equipment.repository.equipment_model'));
        self::assertTrue($container->hasDefinition('tmonier_equipment.factory.equipment_model'));
        self::assertTrue($container->hasParameter('tmonier_equipment.model.equipment_model_translation.class'));
    }

    public function testWithoutTheCompanyAccountsPluginTheFleetIsTheOneOfTheCustomer(): void
    {
        $container = new ContainerBuilder();
        $extension = new TmonierSyliusCustomerAssetManagementExtension();

        $extension->prepend($container);
        $extension->load([], $container);

        self::assertFalse($container->getParameter('tmonier_equipment.company_accounts_bridge'));
        self::assertSame('tmonier_equipment.fleet_scope.customer', (string) $container->getAlias('tmonier_equipment.fleet_scope'));
    }

    public function testWithTheCompanyAccountsPluginTheFleetIsSharedWithTheCompany(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TmonierSyliusCompanyAccountsExtension());
        $extension = new TmonierSyliusCustomerAssetManagementExtension();

        $extension->prepend($container);
        $extension->load([], $container);

        self::assertTrue($container->getParameter('tmonier_equipment.company_accounts_bridge'));
        self::assertSame('tmonier_equipment.fleet_scope.company', (string) $container->getAlias('tmonier_equipment.fleet_scope'));
        $reset = $container->getDefinition('tmonier_equipment.fleet_scope.company')->getTag('kernel.reset');
        self::assertSame([['method' => 'reset']], $reset);
    }
}
