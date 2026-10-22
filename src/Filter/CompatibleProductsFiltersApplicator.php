<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Filter;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Component\HttpFoundation\RequestStack;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;

/**
 * Restricts the product listing of the shop (categories and their search) to the products that
 * have a variant compatible with the machines of the customer, when the customer selected the
 * filter. The listing query of Sylius is extended with an EXISTS sub-select: no extra query, no
 * search engine. Any other listing, or an unselected filter, goes through untouched.
 */
final readonly class CompatibleProductsFiltersApplicator implements FiltersApplicatorInterface
{
    public const GRID = 'sylius_shop_product';

    public function __construct(
        private FiltersApplicatorInterface $inner,
        private RequestStack $requestStack,
        private CompatibilityFilterResolver $resolver,
    ) {
    }

    public function apply(DataSourceInterface $dataSource, Grid $grid, Parameters $parameters): void
    {
        $this->inner->apply($dataSource, $grid, $parameters);

        $request = $this->requestStack->getMainRequest();
        if (self::GRID !== $grid->getCode() || !$request instanceof \Symfony\Component\HttpFoundation\Request || !$dataSource instanceof DataSource) {
            return;
        }

        $filter = $this->resolver->resolve($request);
        if ($filter instanceof CompatibilityFilter && $filter->isActive()) {
            $this->restrict($dataSource->getQueryBuilder(), $filter);
        }
    }

    private function restrict(QueryBuilder $queryBuilder, CompatibilityFilter $filter): void
    {
        $root = $queryBuilder->getRootAliases()[0];
        $compatible = $queryBuilder->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(CustomerAsset::class, 'tmonier_asset')
            ->innerJoin('tmonier_asset.equipmentModel', 'tmonier_model')
            ->innerJoin('tmonier_model.compatibleVariants', 'tmonier_variant')
            ->andWhere('tmonier_variant.product = ' . $root)
            ->andWhere('tmonier_variant.enabled = true')
            ->andWhere('IDENTITY(tmonier_asset.customer) IN (:tmonier_owner_ids)')
        ;
        $queryBuilder->setParameter('tmonier_owner_ids', $filter->ownerIds());

        if (null !== $filter->assetId()) {
            $compatible->andWhere('tmonier_asset.id = :tmonier_asset_id');
            $queryBuilder->setParameter('tmonier_asset_id', $filter->assetId());
        }

        $queryBuilder->andWhere($queryBuilder->expr()->exists($compatible->getDQL()));
    }
}
