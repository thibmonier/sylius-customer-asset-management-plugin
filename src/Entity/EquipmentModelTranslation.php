<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Entity;

use Sylius\Resource\Model\AbstractTranslation;

class EquipmentModelTranslation extends AbstractTranslation implements EquipmentModelTranslationInterface
{
    protected ?int $id = null;

    protected ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
