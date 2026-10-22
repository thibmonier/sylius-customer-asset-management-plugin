<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Filter;

use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;

/**
 * The "compatible parts" filter offered to a signed-in customer with a non-empty fleet: the
 * machines they can choose from, and what is selected ("all" the equipment, one machine, or
 * nothing). Only a selected filter restricts the listing.
 */
final readonly class CompatibilityFilter
{
    public const ALL = 'all';

    /**
     * @param list<CustomerAssetInterface> $assets the machines of the fleet
     * @param list<int> $ownerIds the customers whose machines make the fleet
     * @param string|null $selection "all", the UUID of one machine of the fleet, or null (filter off)
     */
    public function __construct(
        private array $assets,
        private array $ownerIds,
        private ?string $selection,
    ) {
    }

    /** @return list<CustomerAssetInterface> */
    public function assets(): array
    {
        return $this->assets;
    }

    /** @return list<int> */
    public function ownerIds(): array
    {
        return $this->ownerIds;
    }

    public function selection(): ?string
    {
        return $this->selection;
    }

    public function isActive(): bool
    {
        return null !== $this->selection;
    }

    /** The UUID of the chosen machine, null for "all the equipment" (or no filter). */
    public function assetId(): ?string
    {
        return self::ALL === $this->selection ? null : $this->selection;
    }
}
