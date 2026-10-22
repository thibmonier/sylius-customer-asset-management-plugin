<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;

/** @extends RepositoryInterface<EquipmentModelInterface> */
interface EquipmentModelRepositoryInterface extends RepositoryInterface
{
    public function createListQueryBuilder(string $locale): QueryBuilder;

    public function createChoiceQueryBuilder(): QueryBuilder;

    public function findOneByCode(string $code): ?EquipmentModelInterface;

    /** @return list<EquipmentModelInterface> */
    public function findByVariant(ProductVariantInterface $variant): array;

    /** @return array<int, int> number of compatible variants, indexed by model id (models without variant are absent) */
    public function countCompatibleVariants(): array;
}
