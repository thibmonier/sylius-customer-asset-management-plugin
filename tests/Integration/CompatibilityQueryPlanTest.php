<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Integration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

/**
 * The EXISTS sub-select of the "compatible parts" filter, in plain SQL: it runs on the database of
 * the matrix (MySQL, MariaDB, PostgreSQL) and the compatibility table offers an index to join on.
 */
final class CompatibilityQueryPlanTest extends KernelTestCase
{
    use EquipmentTestTrait;

    private const SUBQUERY = 'SELECT 1 FROM customer_asset a
        INNER JOIN equipment_model m ON m.id = a.equipment_model_id
        INNER JOIN equipment_model_variant emv ON emv.equipment_model_id = m.id
        INNER JOIN sylius_product_variant v ON v.id = emv.variant_id
        WHERE v.product_id = ? AND v.enabled = ? AND a.customer_id IN (?, ?)';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->beginTransaction();
    }

    public function testTheSubSelectIsExplainedByTheDatabase(): void
    {
        $plan = $this->connection()->fetchAllAssociative('EXPLAIN ' . self::SUBQUERY, [1, true, 1, 2], [1 => \Doctrine\DBAL\ParameterType::BOOLEAN]);

        self::assertNotSame([], $plan);
    }

    public function testTheCompatibilityTableCanBeJoinedThroughAnIndex(): void
    {
        if (!$this->connection()->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            self::markTestSkipped('The plan is read on MySQL and MariaDB; PostgreSQL only has to explain the query.');
        }

        $plan = $this->connection()->fetchAllAssociative('EXPLAIN ' . self::SUBQUERY, [1, 1, 1, 2]);
        $rows = array_values(array_filter($plan, static fn (array $row): bool => 'emv' === $row['table']));

        self::assertCount(1, $rows);
        self::assertNotNull($rows[0]['possible_keys']);
    }
}
