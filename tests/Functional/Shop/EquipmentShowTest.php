<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional\Shop;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

final class EquipmentShowTest extends WebTestCase
{
    use EquipmentTestTrait;

    public function testACustomerCanViewTheirEquipmentDetails(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $asset = $this->catalog()->asset($customer, $model, 'Lyon Truck', 'AB-123-CD');
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId()));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lyon Truck', $crawler->filter('[data-test-equipment-details]')->text());
        self::assertStringContainsString('Truck Model X', $crawler->filter('[data-test-equipment-details]')->text());
        self::assertStringContainsString('AB-123-CD', $crawler->filter('[data-test-equipment-details]')->text());
    }

    public function testTheEquipmentDetailsPageHasATwigHookForExtensions(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $asset = $this->catalog()->asset($customer, $model, 'Lyon Truck');
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId()));

        self::assertResponseIsSuccessful();
        // The extension hook is rendered: in the test environment, Twig hooks leave a debug comment with their name.
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('tmonier_equipment_shop.account.equipment_show.extensions', $html);
    }

    public function testTheEquipmentListHasALinkToTheDetailsPage(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $asset = $this->catalog()->asset($customer, $model, 'Lyon Truck');
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment'));

        self::assertResponseIsSuccessful();
        $link = $crawler->filter(sprintf('a[href="%s"]', $this->shopUrl('/account/equipment/' . $asset->getId())));
        self::assertCount(1, $link, 'The equipment list should have a link to the details page');
    }
}
