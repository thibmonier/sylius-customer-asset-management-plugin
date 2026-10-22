<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Equipment models, their translations and the compatibility with product variants (US-056).
 *
 * Written against the schema API, not raw SQL, so the same migration runs on MySQL, MariaDB and
 * PostgreSQL. The primary key (equipment_model_id, variant_id) of the compatibility table is the
 * index the "compatible parts" filter uses.
 */
final class Version20261022100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Equipment models, translations and variant compatibility.';
    }

    public function up(Schema $schema): void
    {
        $model = $schema->createTable('equipment_model');
        $model->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $model->addColumn('code', Types::STRING, ['length' => 64]);
        $model->setPrimaryKey(['id']);
        $model->addUniqueIndex(['code']);

        $translation = $schema->createTable('equipment_model_translation');
        $translation->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $translation->addColumn('translatable_id', Types::INTEGER);
        $translation->addColumn('name', Types::STRING, ['length' => 255, 'notnull' => false]);
        $translation->addColumn('locale', Types::STRING, ['length' => 255]);
        $translation->setPrimaryKey(['id']);
        $translation->addIndex(['translatable_id']);
        $translation->addUniqueIndex(['translatable_id', 'locale'], 'equipment_model_translation_uniq_trans');
        $translation->addForeignKeyConstraint('equipment_model', ['translatable_id'], ['id'], ['onDelete' => 'CASCADE']);

        $compatibility = $schema->createTable('equipment_model_variant');
        $compatibility->addColumn('equipment_model_id', Types::INTEGER);
        $compatibility->addColumn('variant_id', Types::INTEGER);
        $compatibility->setPrimaryKey(['equipment_model_id', 'variant_id']);
        $compatibility->addIndex(['equipment_model_id']);
        $compatibility->addIndex(['variant_id']);
        $compatibility->addForeignKeyConstraint('equipment_model', ['equipment_model_id'], ['id'], ['onDelete' => 'CASCADE']);
        $compatibility->addForeignKeyConstraint('sylius_product_variant', ['variant_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('equipment_model_variant');
        $schema->dropTable('equipment_model_translation');
        $schema->dropTable('equipment_model');
    }
}
