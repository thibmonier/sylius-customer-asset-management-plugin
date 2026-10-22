<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Entity;

use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\Uid\Uuid;

class CustomerAsset implements CustomerAssetInterface
{
    protected string $id;

    protected ?CustomerInterface $customer = null;

    protected ?EquipmentModelInterface $equipmentModel = null;

    protected ?string $label = null;

    protected ?string $identifier = null;

    protected \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerInterface $customer): void
    {
        $this->customer = $customer;
    }

    public function getEquipmentModel(): ?EquipmentModelInterface
    {
        return $this->equipmentModel;
    }

    public function setEquipmentModel(?EquipmentModelInterface $equipmentModel): void
    {
        $this->equipmentModel = $equipmentModel;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
