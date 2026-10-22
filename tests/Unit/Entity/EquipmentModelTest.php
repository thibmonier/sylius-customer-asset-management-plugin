<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariant;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModel;

final class EquipmentModelTest extends TestCase
{
    public function testItIsNamedInTheCurrentLocale(): void
    {
        $model = $this->model('TRUCK-X');
        $model->setCurrentLocale('fr_FR');
        $model->setName('Camion Modèle X');
        $model->setCurrentLocale('en_US');
        $model->setName('Truck model X');

        self::assertSame('Truck model X', $model->getName());
        $model->setCurrentLocale('fr_FR');
        self::assertSame('Camion Modèle X', $model->getName());
    }

    public function testNamingTheModelInAnotherLocaleKeepsTheNameOfTheDefaultLocale(): void
    {
        $model = $this->model('TRUCK-X');
        $model->setCurrentLocale('en_US');
        $model->setName('Truck model X');
        $model->setCurrentLocale('fr_FR');
        $model->setName('Camion Modèle X');

        $model->setCurrentLocale('en_US');
        self::assertSame('Truck model X', $model->getName());
        self::assertCount(2, $model->getTranslations());
    }

    public function testWithoutATranslationInTheCurrentLocaleThereIsNoNameAndNothingIsCreated(): void
    {
        $model = $this->model('TRUCK-X');
        $model->setCurrentLocale('fr_FR');
        $model->setFallbackLocale('fr_FR');
        $model->setName('Camion Modèle X');

        $model->setCurrentLocale('en_US');

        self::assertNull($model->getName());
        self::assertSame('TRUCK-X', (string) $model);
        self::assertCount(1, $model->getTranslations());
    }

    public function testAnEmptyNameIsNoName(): void
    {
        $model = $this->model('TRUCK-X');
        $model->setCurrentLocale('en_US');
        $model->setName('');

        self::assertNull($model->getName());
    }

    public function testVariantsAreAddedOnceAndRemoved(): void
    {
        $model = $this->model('TRUCK-X');
        $variant = new ProductVariant();

        $model->addCompatibleVariant($variant);
        $model->addCompatibleVariant($variant);

        self::assertTrue($model->isCompatibleWith($variant));
        self::assertCount(1, $model->getCompatibleVariants());

        $model->removeCompatibleVariant($variant);

        self::assertFalse($model->isCompatibleWith($variant));
        self::assertCount(0, $model->getCompatibleVariants());
    }

    private function model(string $code): EquipmentModel
    {
        $model = new EquipmentModel();
        $model->setCode($code);
        // As the translatable factory does for every model it creates.
        $model->setFallbackLocale('en_US');

        return $model;
    }
}
