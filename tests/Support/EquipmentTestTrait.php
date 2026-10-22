<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Webmozart\Assert\Assert;

/**
 * Every test runs in a transaction rolled back at the end (the kernel is not rebooted between the
 * requests of a functional test). Data are created by the tests through TestCatalog.
 */
trait EquipmentTestTrait
{
    private ?TestCatalog $testCatalog = null;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->connection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection()->isTransactionActive()) {
            $this->connection()->rollBack();
        }
        $this->testCatalog = null;
        parent::tearDown();
    }

    private function catalog(): TestCatalog
    {
        return $this->testCatalog ??= new TestCatalog(self::getContainer());
    }

    /** The fixture administrator, with (or without) the role that manages the equipment models. */
    private function loginAdmin(bool $withModelsRole = true): AdminUserInterface
    {
        $admin = $this->entityManager()->getRepository(AdminUserInterface::class)->findOneBy(['username' => 'sylius']);
        self::assertInstanceOf(AdminUserInterface::class, $admin);
        $admin->addRole('ROLE_EQUIPMENT_MANAGER');
        if (!$withModelsRole) {
            $admin->removeRole('ROLE_EQUIPMENT_MANAGER');
        }
        $this->client->loginUser($admin, 'admin');

        return $admin;
    }

    private function loginShopUser(CustomerInterface $customer): void
    {
        $user = $customer->getUser();
        self::assertInstanceOf(ShopUserInterface::class, $user);
        $this->client->loginUser($user, 'shop');
    }

    private function shopUrl(string $path): string
    {
        return sprintf('/%s%s', $this->catalog()->localeCode(), $path);
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager;
    }

    private function idOf(EquipmentModelInterface|ProductVariantInterface $resource): int
    {
        $id = $resource->getId();
        Assert::integer($id);

        return $id;
    }

    /** @param list<int|string> $parameters */
    private function fetchInt(string $sql, array $parameters = []): int
    {
        $value = $this->connection()->fetchOne($sql, $parameters);
        Assert::numeric($value);

        return (int) $value;
    }

    /**
     * @param list<int|string> $parameters
     *
     * @return list<int>
     */
    private function fetchInts(string $sql, array $parameters = []): array
    {
        $values = $this->connection()->fetchFirstColumn($sql, $parameters);
        Assert::allNumeric($values);

        return array_map(static fn ($value): int => (int) $value, $values);
    }

    private function connection(): Connection
    {
        return $this->entityManager()->getConnection();
    }
}
