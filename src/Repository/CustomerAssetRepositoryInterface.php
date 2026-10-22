<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Repository;

use Sylius\Component\Customer\Model\CustomerInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;

interface CustomerAssetRepositoryInterface
{
    public function findOneById(string $id): ?CustomerAssetInterface;

    /**
     * The assets owned by one of the customers, with their model and its translations (no N+1).
     *
     * @param list<int> $customerIds
     *
     * @return list<CustomerAssetInterface>
     */
    public function findByOwners(array $customerIds): array;

    /** @param list<int> $customerIds */
    public function findOneByIdAndOwners(string $id, array $customerIds): ?CustomerAssetInterface;

    public function countByOwner(CustomerInterface $customer): int;

    public function countByEquipmentModel(EquipmentModelInterface $model): int;

    public function removeByOwner(CustomerInterface $customer): void;
}
