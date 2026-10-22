<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class TranslationsTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function domains(): iterable
    {
        yield 'messages' => ['messages'];
        yield 'validators' => ['validators'];
        yield 'flashes' => ['flashes'];
    }

    /** @dataProvider domains */
    public function testEveryKeyIsTranslatedInEnglishAndFrench(string $domain): void
    {
        $english = $this->keys(__DIR__ . sprintf('/../../translations/%s.en.yaml', $domain));
        $french = $this->keys(__DIR__ . sprintf('/../../translations/%s.fr.yaml', $domain));

        self::assertNotSame([], $english);
        self::assertSame($english, $french);
    }

    /** @return list<string> */
    private function keys(string $file): array
    {
        $keys = [];
        $walk = static function (array $node, string $prefix) use (&$walk, &$keys): void {
            foreach ($node as $key => $value) {
                $path = '' === $prefix ? (string) $key : $prefix . '.' . $key;
                if (\is_array($value)) {
                    $walk($value, $path);
                } else {
                    $keys[] = $path;
                }
            }
        };
        $walk((array) Yaml::parseFile($file), '');
        sort($keys);

        return $keys;
    }
}
