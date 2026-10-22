<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

final class AdminVariantCompatibilityTest extends WebTestCase
{
    use EquipmentTestTrait;

    public function testTheCompatibleModelsAreTickedOnTheVariantForm(): void
    {
        $truck = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $loader = $this->catalog()->model('LOADER', ['en_US' => 'Loader']);
        $variant = $this->catalog()->product('Oil filter 1 L');
        $truck->addCompatibleVariant($variant);
        $this->entityManager()->flush();
        $this->loginAdmin();
        $this->entityManager()->clear();

        $crawler = $this->openVariant($variant);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Truck model X', $crawler->filter('[data-test-tmonier-equipment-compatible-models]')->text());
        self::assertTrue($this->isTicked($crawler, $this->idOf($truck)));
        self::assertFalse($this->isTicked($crawler, $this->idOf($loader)));
    }

    public function testTickingModelsCreatesTheCompatibilityAndUntickingRemovesItWithoutTouchingTheVariant(): void
    {
        $truck = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $loader = $this->catalog()->model('LOADER', ['en_US' => 'Loader']);
        $variant = $this->catalog()->product('Oil filter 1 L');
        $other = $this->catalog()->product('Air filter');
        $truck->addCompatibleVariant($other);
        $this->entityManager()->flush();
        $variantId = $this->idOf($variant);
        $this->loginAdmin();
        $this->entityManager()->clear();

        $this->submitTicked($this->openVariant($variant), [$this->idOf($truck), $this->idOf($loader)]);
        self::assertResponseRedirects();
        self::assertSame([$this->idOf($truck), $this->idOf($loader)], $this->modelIdsOf($variantId));

        $this->entityManager()->clear();
        $this->submitTicked($this->openVariant($variant), [$this->idOf($truck)]);
        self::assertResponseRedirects();

        self::assertSame([$this->idOf($truck)], $this->modelIdsOf($variantId));
        // The other variant of the model and the variants themselves are untouched.
        self::assertSame(2, $this->fetchInt('SELECT COUNT(*) FROM equipment_model_variant WHERE equipment_model_id = ?', [$this->idOf($truck)]));
        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM sylius_product_variant WHERE id = ?', [$variantId]));
    }

    public function testAnAdministratorWithoutTheRoleDoesNotSeeNorChangeTheCompatibility(): void
    {
        $truck = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $variant = $this->catalog()->product('Oil filter 1 L');
        $truck->addCompatibleVariant($variant);
        $this->entityManager()->flush();
        $variantId = $this->idOf($variant);
        $this->loginAdmin(withModelsRole: false);
        $this->entityManager()->clear();

        $crawler = $this->openVariant($variant);

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-test-tmonier-equipment-compatible-models]'));
        $this->client->submit($this->variantForm($crawler));
        self::assertSame([$this->idOf($truck)], $this->modelIdsOf($variantId));
    }

    private function openVariant(ProductVariantInterface $variant): Crawler
    {
        $variantId = $this->idOf($variant);
        $productId = $this->fetchInt('SELECT product_id FROM sylius_product_variant WHERE id = ?', [$variantId]);

        return $this->client->request('GET', sprintf('/admin/products/%d/variants/%d/edit', $productId, $variantId));
    }

    private function variantForm(Crawler $crawler): Form
    {
        return $crawler->filter('form[name="sylius_admin_product_variant"]')->form();
    }

    /** @param list<int> $modelIds */
    private function submitTicked(Crawler $crawler, array $modelIds): void
    {
        $form = $this->variantForm($crawler);
        $values = $form->getPhpValues();
        self::assertIsArray($values['sylius_admin_product_variant']);
        $values['sylius_admin_product_variant']['equipmentModels'] = array_map(strval(...), $modelIds);
        $this->client->request('POST', $form->getUri(), $values);
    }

    private function isTicked(Crawler $crawler, int $modelId): bool
    {
        return 1 === $crawler->filter(sprintf('input[type="checkbox"][value="%d"]:checked', $modelId))->count();
    }

    /** @return list<int> */
    private function modelIdsOf(int $variantId): array
    {
        return $this->fetchInts('SELECT equipment_model_id FROM equipment_model_variant WHERE variant_id = ? ORDER BY equipment_model_id', [$variantId]);
    }
}
