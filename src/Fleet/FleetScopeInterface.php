<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Fleet;

use Sylius\Component\Customer\Model\CustomerInterface;

/** Whose machines a customer sees and changes: their own, and those of their company when there is one. */
interface FleetScopeInterface
{
    /** @return list<int> ids of the customers whose assets belong to the fleet of the customer (the customer first) */
    public function ownerIds(CustomerInterface $customer): array;
}
