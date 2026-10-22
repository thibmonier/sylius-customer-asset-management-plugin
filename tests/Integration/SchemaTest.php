<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Integration;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

/** The database built by the migrations of the plugin is the one the mapping describes. */
final class SchemaTest extends KernelTestCase
{
    use EquipmentTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->beginTransaction();
    }

    public function testTheMigrationsBuildExactlyTheMappedSchema(): void
    {
        $entityManager = $this->entityManager();
        $metadata = array_map(
            $entityManager->getClassMetadata(...),
            [
                \Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface::class,
                \Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelTranslationInterface::class,
                \Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset::class,
            ],
        );

        $statements = (new SchemaTool($entityManager))->getUpdateSchemaSql($metadata);

        // Only what the migrations fail to create or to align: the DROP statements come from the partial
        // set of classes (the foreign keys to the tables of Sylius, the identity columns of PostgreSQL).
        $own = array_filter($statements, static fn (string $sql): bool => 1 === preg_match('/\b(equipment_model\w*|customer_asset)\b/', $sql) && 1 !== preg_match('/\bDROP\b/', $sql));
        self::assertSame([], array_values($own));
    }

    public function testTheCompatibilityIsIndexedOnTheModelAndTheVariant(): void
    {
        $table = $this->connection()->createSchemaManager()->introspectTable('equipment_model_variant');

        $primary = $table->getPrimaryKey();
        self::assertNotNull($primary);
        self::assertSame(['equipment_model_id', 'variant_id'], array_map(strtolower(...), $primary->getColumns()));
        $indexed = array_map(static fn (\Doctrine\DBAL\Schema\Index $index): string => implode(',', array_map(strtolower(...), $index->getColumns())), $table->getIndexes());
        self::assertContains('variant_id', $indexed);
    }

    public function testDeletingAVariantRemovesItsCompatibilityLinksOnly(): void
    {
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck']);
        $variant = $this->catalog()->product('Oil filter');
        $kept = $this->catalog()->product('Air filter');
        $model->addCompatibleVariant($variant);
        $model->addCompatibleVariant($kept);
        $this->entityManager()->flush();

        $this->connection()->executeStatement('DELETE FROM equipment_model_variant WHERE variant_id = ?', [$this->idOf($variant)]);

        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM equipment_model_variant WHERE equipment_model_id = ?', [$this->idOf($model)]));
    }
}
