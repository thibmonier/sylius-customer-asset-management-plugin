<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusCustomerAssetManagementPlugin\TmonierSyliusCustomerAssetManagementPlugin;

final class SmokeTest extends KernelTestCase
{
    public function testKernelBootsWithThePluginBundleAndTheContainerCompiles(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey('TmonierSyliusCustomerAssetManagementPlugin', $kernel->getBundles());
        self::assertInstanceOf(
            TmonierSyliusCustomerAssetManagementPlugin::class,
            $kernel->getBundle('TmonierSyliusCustomerAssetManagementPlugin'),
        );
    }
}
