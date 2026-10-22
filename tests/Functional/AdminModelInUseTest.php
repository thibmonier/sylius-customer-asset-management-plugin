<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;

final class AdminModelInUseTest extends WebTestCase
{
    use EquipmentTestTrait;

    public function testAModelUsedByMachinesCannotBeDeleted(): void
    {
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $this->catalog()->asset($this->catalog()->customer(), $model, 'First', 'A-1');
        $this->catalog()->asset($this->catalog()->customer('second'), $model, 'Second', 'B-1');
        $id = $this->idOf($model);
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/?criteria[search][value]=' . $this->catalog()->suffix);
        $this->client->submit($crawler->filter(sprintf('form[action$="/admin/equipment-models/%d"]', $id))->form());
        $crawler = $this->client->followRedirect();

        self::assertStringContainsString('2 machines use this equipment model', $crawler->filter('[data-test-sylius-flash-message-type="error"]')->text());
        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM equipment_model WHERE id = ?', [$id]));
    }

    public function testAnUnusedModelIsStillDeleted(): void
    {
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $id = $this->idOf($model);
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/?criteria[search][value]=' . $this->catalog()->suffix);
        $this->client->submit($crawler->filter(sprintf('form[action$="/admin/equipment-models/%d"]', $id))->form());

        self::assertResponseRedirects();
        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM equipment_model WHERE id = ?', [$id]));
    }
}
