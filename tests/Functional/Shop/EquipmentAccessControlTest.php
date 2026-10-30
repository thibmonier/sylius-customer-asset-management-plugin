<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional\Shop;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

final class EquipmentAccessControlTest extends WebTestCase
{
    use EquipmentTestTrait;

    public function testAnEquipmentOfAnotherCompanyReturns404(): void
    {
        $otherCustomer = $this->catalog()->customer('other');
        $myCustomer = $this->catalog()->customer('mine');
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $foreignAsset = $this->catalog()->asset($otherCustomer, $model, 'Foreign Truck', 'ZZ-999-YY');
        $this->loginShopUser($myCustomer);

        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $foreignAsset->getId()));

        self::assertResponseStatusCodeSame(404);
        // Verify no data from the other company's equipment is shown
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Foreign Truck', $content);
        self::assertStringNotContainsString('ZZ-999-YY', $content);
    }

    public function testAVisitorNotLoggedInIsRedirectedToLogin(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $asset = $this->catalog()->asset($customer, $model, 'Lyon Truck');

        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId()));

        self::assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/login', $location);
    }

    public function testANonExistentEquipmentIdReturns404(): void
    {
        $customer = $this->catalog()->customer();
        $this->loginShopUser($customer);

        $unknownId = '00000000-0000-4000-8000-000000000000';
        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $unknownId));

        self::assertResponseStatusCodeSame(404);
    }

    public function testFleetMembersOfTheSameCompanyCanViewEachOthersEquipment(): void
    {
        $company = $this->catalog()->company();
        $claire = $this->catalog()->customer('claire');
        $paul = $this->catalog()->customer('paul');
        $this->catalog()->member($company, $claire);
        $this->catalog()->member($company, $paul);
        $model = $this->catalog()->model('TRUCK_X', ['en_US' => 'Truck Model X']);
        $claireAsset = $this->catalog()->asset($claire, $model, 'Claire Truck');
        $this->loginShopUser($paul);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/' . $claireAsset->getId()));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Claire Truck', $crawler->filter('[data-test-equipment-details]')->text());
    }
}
