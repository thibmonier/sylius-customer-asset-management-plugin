<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Fleet;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\Company;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyMember;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyMemberInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Repository\CompanyMemberRepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CompanyFleetScope;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CustomerFleetScope;

final class CompanyFleetScopeTest extends TestCase
{
    public function testTheFleetIsSharedWithTheActiveMembersOfTheCompany(): void
    {
        $claire = $this->customer(1);
        $paul = $this->customer(2);
        $left = $this->customer(3);
        $company = $this->company(true);
        $members = [$this->member($company, $claire, true), $this->member($company, $paul, true), $this->member($company, $left, false)];

        self::assertSame([1, 2], $this->scope($members, $claire)->ownerIds($claire));
        self::assertSame([2, 1], $this->scope($members, $paul)->ownerIds($paul));
    }

    public function testACustomerWithoutCompanyKeepsTheirOwnFleet(): void
    {
        $customer = $this->customer(7);

        self::assertSame([7], $this->scope([], $customer)->ownerIds($customer));
    }

    public function testAMemberOfADisabledCompanyOrDisabledMemberKeepsTheirOwnFleet(): void
    {
        $customer = $this->customer(7);
        $colleague = $this->customer(8);
        $disabledCompany = $this->company(false);
        $members = [$this->member($disabledCompany, $customer, true), $this->member($disabledCompany, $colleague, true)];

        self::assertSame([7], $this->scope($members, $customer)->ownerIds($customer));
    }

    public function testTheMembershipIsLookedUpOncePerCustomer(): void
    {
        $customer = $this->customer(1);
        $company = $this->company(true);
        $member = $this->member($company, $customer, true);
        $repository = $this->createMock(CompanyMemberRepositoryInterface::class);
        $repository->expects(self::once())->method('findOneByCustomer')->willReturn($member);
        $repository->expects(self::once())->method('findByCompany')->willReturn([$member]);
        $scope = new CompanyFleetScope(new CustomerFleetScope(), $repository);

        $scope->ownerIds($customer);
        $scope->ownerIds($customer);
    }

    /** @param list<CompanyMemberInterface> $members */
    private function scope(array $members, CustomerInterface $current): CompanyFleetScope
    {
        $repository = $this->createStub(CompanyMemberRepositoryInterface::class);
        $own = null;
        foreach ($members as $member) {
            if ($member->getCustomer() === $current) {
                $own = $member;
            }
        }
        $repository->method('findOneByCustomer')->willReturn($own);
        $repository->method('findByCompany')->willReturn($members);

        return new CompanyFleetScope(new CustomerFleetScope(), $repository);
    }

    private function company(bool $enabled): Company
    {
        $company = new Company();
        $company->setEnabled($enabled);

        return $company;
    }

    private function member(Company $company, CustomerInterface $customer, bool $enabled): CompanyMember
    {
        $member = new CompanyMember();
        $member->setCompany($company);
        $member->setCustomer($customer);
        $member->setEnabled($enabled);

        return $member;
    }

    private function customer(int $id): Customer
    {
        $customer = new Customer();
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, $id);

        return $customer;
    }
}
