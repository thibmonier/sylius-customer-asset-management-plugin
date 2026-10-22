<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\CodeAwareInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

/** A kind of machine of the catalogue ("Truck model X"), compatible with a set of product variants. */
interface EquipmentModelInterface extends CodeAwareInterface, ResourceInterface, TranslatableInterface
{
    public const CODE_MAX_LENGTH = 64;

    public const NAME_MAX_LENGTH = 255;

    public function getId(): ?int;

    public function getTranslation(?string $locale = null): EquipmentModelTranslationInterface;

    public function getName(): ?string;

    public function setName(?string $name): void;

    /** @return Collection<array-key, ProductVariantInterface> */
    public function getCompatibleVariants(): Collection;

    public function isCompatibleWith(ProductVariantInterface $variant): bool;

    public function addCompatibleVariant(ProductVariantInterface $variant): void;

    public function removeCompatibleVariant(ProductVariantInterface $variant): void;
}
