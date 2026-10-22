<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\TranslatableTrait;

class EquipmentModel implements EquipmentModelInterface, \Stringable
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    /** @var Collection<array-key, ProductVariantInterface> */
    protected Collection $compatibleVariants;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->compatibleVariants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        // Read only, and no fallback to another locale: a model without a name in the current
        // locale is shown by its code. (getTranslation() would create an empty translation.)
        $translation = null === $this->currentLocale ? null : $this->translations->get($this->currentLocale);
        $name = $translation instanceof EquipmentModelTranslationInterface ? $translation->getName() : null;

        return null !== $name && '' !== $name ? $name : null;
    }

    public function setName(?string $name): void
    {
        if (null === $this->currentLocale) {
            throw new \LogicException('The locale of the name is not set.');
        }

        // Not getTranslation(): it falls back to the translation of the default locale, which
        // would be renamed instead of getting its own.
        $translation = $this->translations->get($this->currentLocale);
        if (!$translation instanceof EquipmentModelTranslationInterface) {
            $translation = $this->createTranslation();
            $translation->setLocale($this->currentLocale);
            $this->addTranslation($translation);
        }

        $translation->setName($name);
    }

    public function getCompatibleVariants(): Collection
    {
        return $this->compatibleVariants;
    }

    public function isCompatibleWith(ProductVariantInterface $variant): bool
    {
        return $this->compatibleVariants->contains($variant);
    }

    public function addCompatibleVariant(ProductVariantInterface $variant): void
    {
        if (!$this->isCompatibleWith($variant)) {
            $this->compatibleVariants->add($variant);
        }
    }

    public function removeCompatibleVariant(ProductVariantInterface $variant): void
    {
        $this->compatibleVariants->removeElement($variant);
    }

    public function __toString(): string
    {
        return $this->getName() ?? (string) $this->code;
    }

    public function getTranslation(?string $locale = null): EquipmentModelTranslationInterface
    {
        $translation = $this->doGetTranslation($locale);
        \assert($translation instanceof EquipmentModelTranslationInterface);

        return $translation;
    }

    protected function createTranslation(): EquipmentModelTranslationInterface
    {
        return new EquipmentModelTranslation();
    }
}
