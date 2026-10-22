<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tmonier\SyliusCustomerAssetManagementPlugin\Migrations\Version20261022100000;

/**
 * The migration is written against the schema API, so what it describes is the same on MySQL,
 * MariaDB and PostgreSQL: these tests read the schema it builds, without a database.
 */
final class Version20261022100000Test extends TestCase
{
    public function testItCreatesTheThreeTablesWithTheCompatibilityIndex(): void
    {
        $schema = $this->migrated();

        foreach (['equipment_model', 'equipment_model_translation', 'equipment_model_variant'] as $table) {
            self::assertTrue($schema->hasTable($table), $table);
        }

        $primary = $schema->getTable('equipment_model_variant')->getPrimaryKey();
        self::assertNotNull($primary);
        self::assertSame(['equipment_model_id', 'variant_id'], array_map(strtolower(...), $primary->getColumns()));
    }

    public function testRemovingAModelOrAVariantRemovesTheirLinksOnly(): void
    {
        $table = $this->migrated()->getTable('equipment_model_variant');

        $actions = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $actions[$foreignKey->getForeignTableName()] = strtoupper((string) $foreignKey->onDelete());
        }

        self::assertSame(['equipment_model' => 'CASCADE', 'sylius_product_variant' => 'CASCADE'], $actions);
    }

    public function testTheCodeIsUnique(): void
    {
        $unique = array_filter(
            $this->migrated()->getTable('equipment_model')->getIndexes(),
            static fn (\Doctrine\DBAL\Schema\Index $index): bool => $index->isUnique() && !$index->isPrimary(),
        );

        self::assertCount(1, $unique);
        self::assertSame(['code'], array_values($unique)[0]->getColumns());
    }

    public function testDownDropsEverythingUp(): void
    {
        $schema = $this->migrated();
        $migration = $this->migration();

        $migration->down($schema);

        self::assertFalse($schema->hasTable('equipment_model'));
        self::assertFalse($schema->hasTable('equipment_model_translation'));
        self::assertFalse($schema->hasTable('equipment_model_variant'));
        self::assertTrue($schema->hasTable('sylius_product_variant'));
    }

    private function migrated(): Schema
    {
        $schema = new Schema();
        $variants = $schema->createTable('sylius_product_variant');
        $variants->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $variants->setPrimaryKey(['id']);

        $this->migration()->up($schema);

        return $schema;
    }

    private function migration(): Version20261022100000
    {
        return new Version20261022100000($this->createStub(Connection::class), new NullLogger());
    }
}
