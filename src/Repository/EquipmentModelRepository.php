<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;

class EquipmentModelRepository extends EntityRepository implements EquipmentModelRepositoryInterface
{
    public function createListQueryBuilder(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->leftJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->setParameter('locale', $locale)
        ;
    }

    public function createChoiceQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->leftJoin('o.translations', 'translation')
            ->orderBy('o.code', 'ASC')
        ;
    }

    public function findOneByCode(string $code): ?EquipmentModelInterface
    {
        /** @var EquipmentModelInterface|null $model */
        $model = $this->findOneBy(['code' => $code]);

        return $model;
    }

    public function findByVariant(ProductVariantInterface $variant): array
    {
        if (null === $variant->getId()) {
            return [];
        }

        /** @var list<EquipmentModelInterface> $models */
        $models = $this->createQueryBuilder('o')
            ->innerJoin('o.compatibleVariants', 'variant')
            ->andWhere('variant = :variant')
            ->setParameter('variant', $variant)
            ->getQuery()
            ->getResult()
        ;

        return $models;
    }

    public function countCompatibleVariants(): array
    {
        /** @var list<array{modelId: int|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('o')
            ->select('o.id AS modelId', 'COUNT(variant.id) AS total')
            ->innerJoin('o.compatibleVariants', 'variant')
            ->groupBy('o.id')
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['modelId']] = (int) $row['total'];
        }

        return $counts;
    }
}
