<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusCustomerAssetManagementPlugin\EventListener\AdminAccessListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Extension\ProductVariantTypeExtension;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelTranslationType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Form\Type\EquipmentModelType;
use Tmonier\SyliusCustomerAssetManagementPlugin\Menu\AdminMenuListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;
use Tmonier\SyliusCustomerAssetManagementPlugin\Twig\EquipmentModelExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // The equipment model repository is registered by the Sylius resource configuration.
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
