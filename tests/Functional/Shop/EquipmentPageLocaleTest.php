<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional\Shop;

use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

/**
 * T-TECH-39-01: every shop page of the plugin renders correctly in each locale,
 * with no raw translation key visible in the rendered text.
 */
final class EquipmentPageLocaleTest extends WebTestCase
{
    use EquipmentTestTrait;

    /**
     * @dataProvider localeAndExpectedTextsProvider
     *
     * @param list<string> $expectedTexts
     */
    public function testEquipmentListPageRendersWithLocalizedTexts(
        string $locale,
        array $expectedTexts,
    ): void {
        $customer = $this->catalog()->customer();
        $this->catalog()->asset(
            $customer,
            $this->catalog()->model('TRUCK', ['en_US' => 'Truck Model X', 'fr_FR' => 'Camion Modèle X']),
            'Lyon Truck',
        );
        $this->enableLocale($locale);
        $this->loginShopUser($customer);
        $this->entityManager()->clear();

        $this->client->request('GET', '/' . $locale . '/account/equipment');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        foreach ($expectedTexts as $text) {
            self::assertStringContainsString($text, $content, sprintf('Expected "%s" in the %s page.', $text, $locale));
        }

        $this->assertNoRawTranslationKey($content, $locale . ' equipment list');
    }

    /**
     * @dataProvider localeAndExpectedTextsProvider
     *
     * @param list<string> $expectedTexts
     */
    public function testEquipmentDetailPageRendersWithLocalizedTexts(
        string $locale,
        array $expectedTexts,
    ): void {
        $customer = $this->catalog()->customer();
        $asset = $this->catalog()->asset(
            $customer,
            $this->catalog()->model('TRUCK', ['en_US' => 'Truck Model X', 'fr_FR' => 'Camion Modèle X']),
            'Lyon Truck',
        );
        $assetId = $asset->getId();
        $this->enableLocale($locale);
        $this->loginShopUser($customer);
        $this->entityManager()->clear();

        $this->client->request('GET', '/' . $locale . '/account/equipment/' . $assetId);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        foreach ($expectedTexts as $text) {
            self::assertStringContainsString($text, $content, sprintf('Expected "%s" in the %s detail page.', $text, $locale));
        }

        $this->assertNoRawTranslationKey($content, $locale . ' equipment detail');
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function localeAndExpectedTextsProvider(): iterable
    {
        yield 'en_US' => ['en_US', ['My equipment', 'Lyon Truck', 'Truck Model X']];
        yield 'fr_FR' => ['fr_FR', ['Mon matériel', 'Lyon Truck', 'Camion Modèle X']];
    }

    private function enableLocale(string $localeCode): void
    {
        if ('en_US' === $localeCode) {
            return;
        }

        $locale = $this->entityManager()->getRepository(LocaleInterface::class)->findOneBy(['code' => $localeCode]);
        self::assertInstanceOf(LocaleInterface::class, $locale, sprintf('Locale %s not found in the test database.', $localeCode));
        $this->catalog()->channel()->addLocale($locale);
        $this->entityManager()->flush();
    }

    private function assertNoRawTranslationKey(string $html, string $context): void
    {
        $text = strip_tags($html);

        self::assertDoesNotMatchRegularExpression(
            '/\b[a-z_]{2,}\.[a-z_.]{2,}\b/',
            $text,
            sprintf('Raw translation key found in rendered text (%s).', $context),
        );
    }
}
