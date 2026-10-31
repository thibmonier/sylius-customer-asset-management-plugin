<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepository;

final class RepositoryAsServiceTest extends KernelTestCase
{
    public function testGetRepositoryReturnsThePluginRepository(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(CustomerAsset::class);

        self::assertInstanceOf(CustomerAssetRepository::class, $repository);
    }
}
