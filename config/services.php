<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusCustomerAssetManagementPlugin\Controller\Shop\FleetController;
use Tmonier\SyliusCustomerAssetManagementPlugin\EventListener\AdminAccessListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\EventListener\CustomerRemovalListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\EventListener\EquipmentModelDeletionGuard;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CustomerFleetScope;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\FleetScopeInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Extension\ProductVariantTypeExtension;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\CustomerAssetType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelTranslationType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Menu\AccountMenuListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\Menu\AdminMenuListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepository;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\CustomerAssetVoter;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;
use Tmonier\SyliusCustomerAssetManagementPlugin\Twig\EquipmentModelExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // The equipment model repository is registered by the Sylius resource configuration.
    $services
        ->set('tmonier_equipment.repository.customer_asset', CustomerAssetRepository::class)
        ->args([service('doctrine')])
        ->tag('doctrine.repository_service')
    ;
    $services->alias(CustomerAssetRepositoryInterface::class, 'tmonier_equipment.repository.customer_asset');

    // Whose machines a customer reaches: their own; with the company accounts plugin, also those of their company.
    $services->set('tmonier_equipment.fleet_scope.customer', CustomerFleetScope::class);
    $services->alias('tmonier_equipment.fleet_scope', 'tmonier_equipment.fleet_scope.customer');
    $services->alias(FleetScopeInterface::class, 'tmonier_equipment.fleet_scope');
    $services
        ->set('tmonier_equipment.voter.customer_asset', CustomerAssetVoter::class)
        ->args([service('tmonier_equipment.fleet_scope')])
        ->tag('security.voter')
    ;

    // Customer account: "My equipment".
    $services
        ->set('tmonier_equipment.form.type.customer_asset', CustomerAssetType::class)
        ->args([param('tmonier_equipment.model.equipment_model.class')])
        ->tag('form.type')
    ;
    $services
        ->set('tmonier_equipment.controller.shop.fleet', FleetController::class)
        ->args([
            service('tmonier_equipment.fleet_scope'),
            service('tmonier_equipment.repository.customer_asset'),
            service('doctrine.orm.entity_manager'),
            service('translator'),
            param('tmonier_equipment.max_assets_per_customer'),
        ])
        ->call('setContainer', [service(\Psr\Container\ContainerInterface::class)])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber')
        ->public()
    ;
    $services
        ->set('tmonier_equipment.listener.account_menu', AccountMenuListener::class)
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.shop.account'])
    ;

    // Data protection: the machines go with their customer; a model in use is not deleted.
    $services
        ->set('tmonier_equipment.listener.customer_removal', CustomerRemovalListener::class)
        ->args([service('tmonier_equipment.repository.customer_asset')])
        ->tag('doctrine.event_listener', ['event' => 'preRemove'])
    ;
    $services
        ->set('tmonier_equipment.listener.equipment_model_deletion_guard', EquipmentModelDeletionGuard::class)
        ->args([service('tmonier_equipment.repository.customer_asset')])
        ->tag('kernel.event_listener', ['event' => 'tmonier_equipment.equipment_model.pre_delete'])
    ;
    $services->alias(EquipmentModelRepositoryInterface::class, 'tmonier_equipment.repository.equipment_model');

    // Administration: access, menu, forms.
    $services
        ->set('tmonier_equipment.admin_access', EquipmentAdminAccess::class)
        ->args([
            service('security.authorization_checker'),
            service('security.token_storage'),
            param('tmonier_equipment.admin_role'),
        ])
    ;
    $services
        ->set('tmonier_equipment.listener.admin_access', AdminAccessListener::class)
        ->args([service('tmonier_equipment.admin_access')])
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'priority' => 6])
    ;
    $services
        ->set('tmonier_equipment.listener.admin_menu', AdminMenuListener::class)
        ->args([service('tmonier_equipment.admin_access')])
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.admin.main'])
    ;
    $services
        ->set('tmonier_equipment.form.type.equipment_model', EquipmentModelType::class)
        ->args([
            param('tmonier_equipment.model.equipment_model.class'),
            ['Default'],
            EquipmentModelTranslationType::class,
        ])
        ->tag('form.type')
    ;
    $services
        ->set('tmonier_equipment.form.type.equipment_model_translation', EquipmentModelTranslationType::class)
        ->args([param('tmonier_equipment.model.equipment_model_translation.class'), ['Default']])
        ->tag('form.type')
    ;
    $services
        ->set('tmonier_equipment.form.extension.product_variant', ProductVariantTypeExtension::class)
        ->args([
            service('tmonier_equipment.repository.equipment_model'),
            service('tmonier_equipment.admin_access'),
            param('tmonier_equipment.model.equipment_model.class'),
        ])
        ->tag('form.type_extension')
    ;
    $services
        ->set('tmonier_equipment.twig.equipment_model', EquipmentModelExtension::class)
        ->args([service('tmonier_equipment.repository.equipment_model')])
        ->tag('twig.extension')
    ;
};
