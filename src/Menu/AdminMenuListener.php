<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;

final readonly class AdminMenuListener
{
    public function __construct(private EquipmentAdminAccess $access)
    {
    }

    public function __invoke(MenuBuilderEvent $event): void
    {
        $catalog = $event->getMenu()->getChild('catalog');
        if (!$catalog instanceof ItemInterface || !$this->access->canManageModels()) {
            return;
        }

        $catalog
            ->addChild('tmonier_equipment_models', ['route' => 'tmonier_equipment_admin_equipment_model_index', 'extras' => ['routes' => [
                ['route' => 'tmonier_equipment_admin_equipment_model_create'],
                ['route' => 'tmonier_equipment_admin_equipment_model_update'],
            ]]])
            ->setLabel('tmonier_equipment.ui.equipment_models')
            ->setLabelAttribute('icon', 'tabler:truck')
        ;
    }
}
