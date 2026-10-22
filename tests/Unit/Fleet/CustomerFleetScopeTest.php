<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Fleet;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Customer;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CustomerFleetScope;

final class CustomerFleetScopeTest extends TestCase
{
    public function testWithoutCompanyTheFleetIsTheOneOfTheCustomerOnly(): void
    {
        self::assertSame([12], (new CustomerFleetScope())->ownerIds($this->customer(12)));
    }

    private function customer(int $id): Customer
    {
        $customer = new Customer();
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, $id);

        return $customer;
    }
}
