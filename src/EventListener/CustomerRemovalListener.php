<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\EventListener;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use Sylius\Component\Customer\Model\CustomerInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;

/**
 * The machines are removed with their customer (personal data). The database does it too
 * (ON DELETE CASCADE); this keeps the identity map and the unit of work consistent.
 */
final readonly class CustomerRemovalListener
{
    public function __construct(private CustomerAssetRepositoryInterface $assets)
    {
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $customer = $event->getObject();
        if ($customer instanceof CustomerInterface) {
            $this->assets->removeByOwner($customer);
        }
    }
}
