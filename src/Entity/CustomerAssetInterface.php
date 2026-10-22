<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Entity;

use Sylius\Component\Customer\Model\CustomerInterface;

/**
 * One machine of the fleet of a customer. The identifier (plate or serial number) can identify a
 * person: it is personal data, never logged and never put in a URL.
 */
interface CustomerAssetInterface
{
    public const LABEL_MAX_LENGTH = 255;

    public const IDENTIFIER_MAX_LENGTH = 40;

    /** Unguessable id (UUID, RFC 4122), the only reference to the asset in URLs. */
    public function getId(): string;

    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function getEquipmentModel(): ?EquipmentModelInterface;

    public function setEquipmentModel(?EquipmentModelInterface $equipmentModel): void;

    public function getLabel(): ?string;

    public function setLabel(?string $label): void;

    public function getIdentifier(): ?string;

    public function setIdentifier(?string $identifier): void;

    public function getCreatedAt(): \DateTimeImmutable;
}
