<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Twig;

use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EquipmentModelExtension extends AbstractExtension
{
    /** @var array<int, int>|null */
    private ?array $counts = null;

    public function __construct(private readonly EquipmentModelRepositoryInterface $repository)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('tmonier_equipment_variant_count', $this->variantCount(...))];
    }

    /** One grouped query for the whole page of the grid, not one per row. */
    public function variantCount(EquipmentModelInterface $model): int
    {
        $this->counts ??= $this->repository->countCompatibleVariants();

        return $this->counts[$model->getId() ?? 0] ?? 0;
    }
}
