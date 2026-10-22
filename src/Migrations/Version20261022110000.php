<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * The machines of the customers (US-057). The id is an UUID (never predictable in a URL). The
 * assets go with their customer (ON DELETE CASCADE) and an equipment model that a fleet uses
 * cannot be deleted (RESTRICT). Schema API: the same migration on MySQL, MariaDB and PostgreSQL.
 */
final class Version20261022110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Customer assets (fleet of the customers).';
    }

    public function up(Schema $schema): void
    {
        $asset = $schema->createTable('customer_asset');
        $asset->addColumn('id', Types::GUID);
        $asset->addColumn('customer_id', Types::INTEGER);
        $asset->addColumn('equipment_model_id', Types::INTEGER);
        $asset->addColumn('label', Types::STRING, ['length' => 255]);
        $asset->addColumn('identifier', Types::STRING, ['length' => 40]);
        $asset->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $asset->setPrimaryKey(['id']);
        $asset->addIndex(['customer_id'], 'idx_customer_asset_customer');
        $asset->addIndex(['equipment_model_id']);
        $asset->addForeignKeyConstraint('sylius_customer', ['customer_id'], ['id'], ['onDelete' => 'CASCADE']);
        $asset->addForeignKeyConstraint('equipment_model', ['equipment_model_id'], ['id'], ['onDelete' => 'RESTRICT']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('customer_asset');
    }
}
