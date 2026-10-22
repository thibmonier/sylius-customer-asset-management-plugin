<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\EventListener;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;

/** A model used by the fleet of a customer cannot be deleted: the customers' machines would lose their model. */
final readonly class EquipmentModelDeletionGuard
{
    public function __construct(private CustomerAssetRepositoryInterface $assets)
    {
    }

    public function __invoke(ResourceControllerEvent $event): void
    {
        $model = $event->getSubject();
        if (!$model instanceof EquipmentModelInterface) {
            return;
        }

        $count = $this->assets->countByEquipmentModel($model);
        if ($count > 0) {
            $event->stop('tmonier_equipment.equipment_model.in_use', ResourceControllerEvent::TYPE_ERROR, ['%count%' => $count]);
        }
    }
}
