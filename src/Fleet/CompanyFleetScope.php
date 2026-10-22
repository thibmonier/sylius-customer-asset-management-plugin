<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Fleet;

use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Repository\CompanyMemberRepositoryInterface;

/**
 * With tmonier/sylius-company-accounts-plugin: the fleet is shared with the active members of the
 * company of the customer. A customer without an active membership keeps their own fleet.
 */
final class CompanyFleetScope implements FleetScopeInterface, ResetInterface
{
    /** @var array<int, list<int>> */
    private array $cache = [];

    public function __construct(
        private readonly FleetScopeInterface $inner,
        private readonly CompanyMemberRepositoryInterface $members,
    ) {
    }

    public function ownerIds(CustomerInterface $customer): array
    {
        $key = $customer->getId();
        if (!\is_int($key)) {
            return $this->resolve($customer);
        }

        return $this->cache[$key] ??= $this->resolve($customer);
    }

    /** Between two requests of a long-running worker: memberships change. */
    public function reset(): void
    {
        $this->cache = [];
    }

    /** @return list<int> */
    private function resolve(CustomerInterface $customer): array
    {
        $own = $this->inner->ownerIds($customer);
        if (!$customer instanceof \Sylius\Component\Core\Model\CustomerInterface) {
            return $own;
        }

        $membership = $this->members->findOneByCustomer($customer);
        $company = $membership?->getCompany();
        if (!$membership instanceof \Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyMemberInterface || !$company instanceof \Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface || !$membership->isActive()) {
            return $own;
        }

        $ids = $own;
        foreach ($this->members->findByCompany($company) as $member) {
            $id = $member->getCustomer()?->getId();
            if ($member->isActive() && \is_int($id) && !\in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
