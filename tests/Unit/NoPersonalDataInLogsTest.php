<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The plate or serial number of a machine is personal data: the plugin has no logger, so there is
 * nothing to leak. A logger would have to be added together with a decision about that data.
 */
final class NoPersonalDataInLogsTest extends TestCase
{
    public function testNoClassOfThePluginUsesALogger(): void
    {
        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/../../src', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);
            if ('php' === $file->getExtension() && 1 === preg_match('/Psr\\\\Log|LoggerInterface|\$this->logger|error_log\(/', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getFilename();
            }
        }

        self::assertSame([], $offenders);
    }

    public function testNoServiceOfThePluginReceivesALogger(): void
    {
        $services = (string) file_get_contents(__DIR__ . '/../../config/services.php');

        self::assertStringNotContainsString('logger', $services);
    }
}
