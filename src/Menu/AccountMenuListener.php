<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/** "My equipment" in the menu of the customer account. */
final class AccountMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('tmonier_equipment', ['route' => 'tmonier_equipment_shop_fleet_index', 'extras' => ['routes' => [
                ['route' => 'tmonier_equipment_shop_fleet_create'],
                ['route' => 'tmonier_equipment_shop_fleet_update'],
            ]]])
            ->setLabel('tmonier_equipment.ui.my_equipment')
            ->setLabelAttribute('icon', 'tabler:truck')
        ;
    }
}
