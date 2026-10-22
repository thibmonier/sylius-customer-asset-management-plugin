<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tmonier\SyliusCustomerAssetManagementPlugin\Migrations\Version20261022110000;

final class Version20261022110000Test extends TestCase
{
    public function testTheAssetsOfACustomerAreRemovedWithTheCustomerAndTheModelIsNotCascaded(): void
    {
        $actions = [];
        foreach ($this->migrated()->getTable('customer_asset')->getForeignKeys() as $foreignKey) {
            $actions[$foreignKey->getForeignTableName()] = strtoupper((string) $foreignKey->onDelete());
        }

        self::assertSame('CASCADE', $actions['sylius_customer']);
        // The deletion of a model in use is refused by the database (checked against the real database in the integration tests).
        self::assertNotContains($actions['equipment_model'], ['CASCADE', 'SET NULL']);
    }

    public function testTheIdentifierOfTheAssetIsAnUuidAndTheFleetIsIndexedByCustomer(): void
    {
        $table = $this->migrated()->getTable('customer_asset');

        self::assertInstanceOf(GuidType::class, $table->getColumn('id')->getType());
        $indexed = array_map(static fn (\Doctrine\DBAL\Schema\Index $index): string => implode(',', array_map(strtolower(...), $index->getColumns())), $table->getIndexes());
        self::assertContains('customer_id', $indexed);
        self::assertSame(255, $table->getColumn('label')->getLength());
        self::assertSame(40, $table->getColumn('identifier')->getLength());
    }

    public function testDownDropsTheTable(): void
    {
        $schema = $this->migrated();

        $this->migration()->down($schema);

        self::assertFalse($schema->hasTable('customer_asset'));
    }

    private function migrated(): Schema
    {
        $schema = new Schema();
        foreach (['sylius_customer', 'equipment_model'] as $name) {
            $table = $schema->createTable($name);
            $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
            $table->setPrimaryKey(['id']);
        }

        $this->migration()->up($schema);

        return $schema;
    }

    private function migration(): Version20261022110000
    {
        return new Version20261022110000($this->createStub(Connection::class), new NullLogger());
    }
}
