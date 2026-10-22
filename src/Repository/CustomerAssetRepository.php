<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Customer\Model\CustomerInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;

/** @extends ServiceEntityRepository<CustomerAssetInterface> */
class CustomerAssetRepository extends ServiceEntityRepository implements CustomerAssetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerAsset::class);
    }

    public function findOneById(string $id): ?CustomerAssetInterface
    {
        // Not a Uuid: nothing to look for (and a database that validates the format would raise).
        if (!self::isUuid($id)) {
            return null;
        }

        return $this->find(strtolower($id));
    }

    public function findByOwners(array $customerIds): array
    {
        if ([] === $customerIds) {
            return [];
        }

        /** @var list<CustomerAssetInterface> $assets */
        $assets = $this->createQueryBuilder('asset')
            ->addSelect('model', 'translation')
            ->innerJoin('asset.equipmentModel', 'model')
            ->leftJoin('model.translations', 'translation')
            ->andWhere('IDENTITY(asset.customer) IN (:customerIds)')
            ->setParameter('customerIds', $customerIds)
            ->orderBy('asset.label', 'ASC')
            ->addOrderBy('asset.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $assets;
    }

    public function findOneByIdAndOwners(string $id, array $customerIds): ?CustomerAssetInterface
    {
        if ([] === $customerIds || !self::isUuid($id)) {
            return null;
        }

        /** @var CustomerAssetInterface|null $asset */
        $asset = $this->createQueryBuilder('asset')
            ->andWhere('asset.id = :id')
            ->andWhere('IDENTITY(asset.customer) IN (:customerIds)')
            ->setParameter('id', strtolower($id))
            ->setParameter('customerIds', $customerIds)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $asset;
    }

    public function countByOwner(CustomerInterface $customer): int
    {
        return (int) $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)')
            ->andWhere('asset.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countByEquipmentModel(EquipmentModelInterface $model): int
    {
        return (int) $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)')
            ->andWhere('asset.equipmentModel = :model')
            ->setParameter('model', $model)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function removeByOwner(CustomerInterface $customer): void
    {
        $this->createQueryBuilder('asset')
            ->delete()
            ->andWhere('asset.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->execute()
        ;
    }

    public static function isUuid(string $value): bool
    {
        return 1 === preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $value);
    }
}
