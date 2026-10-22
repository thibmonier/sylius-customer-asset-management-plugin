<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;

final class CustomerAssetPersistenceTest extends KernelTestCase
{
    use EquipmentTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->beginTransaction();
    }

    public function testTheAssetsAreRemovedWithTheCustomer(): void
    {
        $customer = $this->catalog()->customer();
        $other = $this->catalog()->customer('other');
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck']);
        foreach (['A', 'B', 'C'] as $label) {
            $this->catalog()->asset($customer, $model, $label, $label . '-1');
        }
        $this->catalog()->asset($other, $model, 'Kept', 'KEPT-1');

        $this->entityManager()->remove($customer);
        $this->entityManager()->flush();

        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM customer_asset WHERE label IN (?, ?, ?)', ['A', 'B', 'C']));
        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM customer_asset WHERE label = ?', ['Kept']));
    }

    public function testTheDatabaseRemovesTheAssetsOfACustomerOnItsOwn(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck']);
        $this->catalog()->asset($customer, $model, 'A', 'A-1');

        $this->connection()->executeStatement('DELETE FROM sylius_shop_user WHERE customer_id = ?', [$this->idOf($customer)]);
        $this->connection()->executeStatement('DELETE FROM sylius_customer WHERE id = ?', [$this->idOf($customer)]);

        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM customer_asset WHERE label = ?', ['A']));
    }

    public function testAModelUsedByAnAssetCannotBeRemovedFromTheDatabase(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck']);
        $this->catalog()->asset($customer, $model, 'A', 'A-1');

        $this->expectException(\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException::class);

        $this->connection()->executeStatement('DELETE FROM equipment_model WHERE id = ?', [$this->idOf($model)]);
    }

    public function testTheFleetOfSeveralOwnersIsReadWithTheNamesOfTheirModels(): void
    {
        $first = $this->catalog()->customer();
        $second = $this->catalog()->customer('second');
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck', 'fr_FR' => 'Camion']);
        $this->catalog()->asset($first, $model, 'B truck', 'B-1');
        $this->catalog()->asset($second, $model, 'A truck', 'A-1');
        $this->catalog()->asset($this->catalog()->customer('stranger'), $model, 'Stranger', 'S-1');
        $this->entityManager()->clear();

        $assets = $this->assets()->findByOwners([$this->idOf($first), $this->idOf($second)]);

        self::assertSame(['A truck', 'B truck'], array_map(static fn (\Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface $asset): ?string => $asset->getLabel(), $assets));
        self::assertSame(['Truck', 'Truck'], array_map(static fn (\Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface $asset): ?string => $asset->getEquipmentModel()?->getName(), $assets));
    }

    public function testAnIdThatIsNotAnUuidFindsNothing(): void
    {
        self::assertNull($this->assets()->findOneById("1' OR '1'='1"));
        self::assertNull($this->assets()->findOneByIdAndOwners('12', [1]));
        self::assertNull($this->assets()->findOneById('00000000-0000-4000-8000-000000000000'));
    }

    private function assets(): CustomerAssetRepositoryInterface
    {
        $repository = self::getContainer()->get('tmonier_equipment.repository.customer_asset');
        self::assertInstanceOf(CustomerAssetRepositoryInterface::class, $repository);

        return $repository;
    }
}
