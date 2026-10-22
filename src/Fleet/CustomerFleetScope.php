<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Fleet;

use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerFleetScope implements FleetScopeInterface
{
    public function ownerIds(CustomerInterface $customer): array
    {
        $id = $customer->getId();

        return \is_int($id) ? [$id] : [];
    }
}
