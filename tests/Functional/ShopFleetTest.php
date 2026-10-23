<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;
use Webmozart\Assert\Assert;

final class ShopFleetTest extends WebTestCase
{
    use EquipmentTestTrait;

    private const PLATE = 'ZZ-987-YY';

    public function testACustomerDeclaresAMachineAndSeesItInTheFleet(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form([
            'tmonier_equipment_customer_asset[equipmentModel]' => (string) $this->idOf($model),
            'tmonier_equipment_customer_asset[label]' => 'Lyon truck',
            'tmonier_equipment_customer_asset[identifier]' => 'AB-123-CD',
        ]));

        self::assertResponseRedirects($this->shopUrl('/account/equipment'));
        $crawler = $this->client->followRedirect();
        self::assertStringContainsString('My equipment', $crawler->filter('[data-test-tmonier-equipment-title]')->text());
        $row = $crawler->filter('[data-test-tmonier-equipment-asset]');
        self::assertCount(1, $row);
        self::assertStringContainsString('Lyon truck', $row->text());
        self::assertStringContainsString('Truck model X', $row->text());
        self::assertStringContainsString('AB-123-CD', $row->text());
        $asset = $this->assets()->findByOwners([$this->idOf($customer)])[0];
        self::assertSame($asset->getId(), $row->attr('data-test-tmonier-equipment-asset'));
        self::assertMatchesRegularExpression('/\A[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/', $asset->getId());
        // No link of the page carries the plate.
        foreach ($crawler->filter('a, form')->each(static fn (Crawler $node): string => (string) ($node->attr('href') ?? $node->attr('action'))) as $url) {
            self::assertStringNotContainsString('AB-123-CD', $url);
        }
    }

    public function testACustomerEditsThenDeletesAMachine(): void
    {
        $customer = $this->catalog()->customer();
        $asset = $this->catalog()->asset($customer, $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']), 'Lyon truck');
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId() . '/edit'));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form([
            'tmonier_equipment_customer_asset[label]' => 'Grenoble truck',
        ]));
        self::assertResponseRedirects($this->shopUrl('/account/equipment'));
        $crawler = $this->client->followRedirect();
        self::assertStringContainsString('Grenoble truck', $crawler->filter('[data-test-tmonier-equipment-asset]')->text());

        $crawler = $this->client->click($crawler->filter('[data-test-tmonier-equipment-delete]')->link());
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('[data-test-tmonier-equipment-confirm-delete]')->form());
        self::assertResponseRedirects($this->shopUrl('/account/equipment'));
        $crawler = $this->client->followRedirect();
        self::assertCount(0, $crawler->filter('[data-test-tmonier-equipment-asset]'));
        self::assertSame([], $this->assets()->findByOwners([$this->idOf($customer)]));
    }

    public function testDeletingAMachineAsksForAConfirmationFirst(): void
    {
        $customer = $this->catalog()->customer();
        $asset = $this->catalog()->asset($customer, $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']), 'Lyon truck', self::PLATE);
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment'));
        self::assertCount(0, $crawler->filter('[data-test-tmonier-equipment-assets] form'), 'the list deletes nothing by itself');
        $crawler = $this->client->click($crawler->filter('[data-test-tmonier-equipment-delete]')->link());

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Remove this machine from your equipment?', $crawler->filter('[data-test-tmonier-equipment-delete-question]')->text());
        self::assertStringContainsString('Lyon truck', $crawler->filter('[data-test-tmonier-equipment-delete-question]')->text());
        self::assertStringNotContainsString(self::PLATE, $crawler->filter('[data-test-tmonier-equipment-delete-question]')->text());
        self::assertSame($this->shopUrl('/account/equipment'), $crawler->filter('[data-test-tmonier-equipment-cancel-delete]')->attr('href'));
        $this->entityManager()->clear();
        self::assertNotNull($this->assets()->findOneById($asset->getId()), 'showing the confirmation deletes nothing');
    }

    public function testTheFleetOfTheCompanyIsSharedBetweenItsMembers(): void
    {
        $company = $this->catalog()->company();
        $claire = $this->catalog()->customer('claire');
        $paul = $this->catalog()->customer('paul');
        $this->catalog()->member($company, $claire);
        $this->catalog()->member($company, $paul);
        $asset = $this->catalog()->asset($claire, $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']), 'Lyon truck');
        $this->loginShopUser($paul);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment'));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lyon truck', $crawler->filter('[data-test-tmonier-equipment-asset]')->text());

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId() . '/edit'));
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form(['tmonier_equipment_customer_asset[label]' => 'Edited by Paul']));
        self::assertResponseRedirects();
        $this->entityManager()->clear();
        self::assertSame('Edited by Paul', $this->assets()->findOneById($asset->getId())?->getLabel());
    }

    public function testWithoutACompanyACustomerSeesOnlyTheirOwnMachines(): void
    {
        $company = $this->catalog()->company();
        $claire = $this->catalog()->customer('claire');
        $paul = $this->catalog()->customer('paul');
        $this->catalog()->member($company, $claire);
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $this->catalog()->asset($claire, $model, 'Claire truck');
        $this->catalog()->asset($paul, $model, 'Paul truck');
        $this->loginShopUser($paul);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-test-tmonier-equipment-asset]'));
        self::assertStringContainsString('Paul truck', $crawler->filter('[data-test-tmonier-equipment-asset]')->text());
    }

    public function testAMachineOfAnotherCompanyAnswers404ForEveryAction(): void
    {
        $other = $this->catalog()->customer('other');
        $mine = $this->catalog()->customer('mine');
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $foreign = $this->catalog()->asset($other, $model, 'Foreign truck', self::PLATE);
        $this->loginShopUser($mine);
        $csrf = $this->csrfTokenOfTheForm();

        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $foreign->getId() . '/edit'));
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', $this->shopUrl('/account/equipment/' . $foreign->getId() . '/edit'), ['tmonier_equipment_customer_asset' => ['label' => 'Hijacked', 'identifier' => 'HACK', 'equipmentModel' => (string) $this->idOf($model), '_token' => $csrf]]);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $foreign->getId() . '/delete'));
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', $this->shopUrl('/account/equipment/' . $foreign->getId() . '/delete'), ['_csrf_token' => 'whatever']);
        self::assertResponseStatusCodeSame(404);

        $unknown = '3f2b8c1e-0000-4000-8000-000000000000';
        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $unknown . '/edit'));
        self::assertResponseStatusCodeSame(404);

        $this->entityManager()->clear();
        $reloaded = $this->assets()->findOneById($foreign->getId());
        self::assertSame('Foreign truck', $reloaded?->getLabel());
        self::assertSame(self::PLATE, $reloaded->getIdentifier());
    }

    public function testDeletingWithoutAValidCsrfTokenIsRefusedAndTheMachineStays(): void
    {
        $customer = $this->catalog()->customer();
        $asset = $this->catalog()->asset($customer, $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']), 'Lyon truck');
        $this->loginShopUser($customer);
        $url = $this->shopUrl('/account/equipment/' . $asset->getId() . '/delete');

        $this->client->request('POST', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['_csrf_token' => 'not-a-valid-token']);
        self::assertResponseStatusCodeSame(403);

        $this->entityManager()->clear();
        self::assertNotNull($this->assets()->findOneById($asset->getId()));
    }

    public function testAnIdThatIsNotAUuidAnswers404(): void
    {
        $customer = $this->catalog()->customer();
        $this->loginShopUser($customer);

        $this->client->request('GET', $this->shopUrl('/account/equipment/12/edit'));

        self::assertResponseStatusCodeSame(404);
    }

    /** @return iterable<string, array{string, string, string, string}> */
    public static function invalidMachines(): iterable
    {
        yield 'empty identifier' => ['Truck', '', 'model', 'Please enter the plate or the serial number.'];
        yield 'identifier of 41 characters' => ['Truck', str_repeat('A', 41), 'model', 'The plate or the serial number must not be longer than 40 characters.'];
        yield 'identifier with a control character' => ['Truck', "AB\x07CD", 'model', 'The plate or the serial number must not contain control characters.'];
        yield 'empty label' => ['', 'AB-123-CD', 'model', 'Please enter a label.'];
        yield 'unknown model' => ['Truck', 'AB-123-CD', '999999999', 'Please choose a valid equipment model.'];
    }

    /** @dataProvider invalidMachines */
    public function testAnInvalidMachineIsRefusedWithAMessagePerField(string $label, string $identifier, string $model, string $message): void
    {
        $customer = $this->catalog()->customer();
        $existing = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));
        $form = $crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form();
        $values = $form->getPhpValues();
        self::assertIsArray($values['tmonier_equipment_customer_asset']);
        $values['tmonier_equipment_customer_asset']['label'] = $label;
        $values['tmonier_equipment_customer_asset']['identifier'] = $identifier;
        $values['tmonier_equipment_customer_asset']['equipmentModel'] = 'model' === $model ? (string) $this->idOf($existing) : $model;
        $this->client->request('POST', $form->getUri(), $values);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString($message, html_entity_decode((string) $this->client->getResponse()->getContent()));
        self::assertSame([], $this->assets()->findByOwners([$this->idOf($customer)]));
    }

    public function testAVisitorIsRedirectedToTheLogin(): void
    {
        $this->client->request('GET', $this->shopUrl('/account/equipment'));

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testACustomerCannotDeclareMoreMachinesThanTheLimit(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        foreach (range(1, 3) as $index) {
            $this->catalog()->asset($customer, $model, 'Truck ' . $index, 'ID-' . $index);
        }
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form([
            'tmonier_equipment_customer_asset[equipmentModel]' => (string) $this->idOf($model),
            'tmonier_equipment_customer_asset[label]' => 'One too many',
            'tmonier_equipment_customer_asset[identifier]' => 'ID-4',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('You can declare at most 3 machines.', html_entity_decode((string) $this->client->getResponse()->getContent()));
        self::assertCount(3, $this->assets()->findByOwners([$this->idOf($customer)]));
    }

    public function testThePlateIsNeitherLoggedNorInTheRedirectOrTheUrls(): void
    {
        $customer = $this->catalog()->customer();
        $model = $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']);
        $logsDir = self::getContainer()->getParameter('kernel.logs_dir');
        Assert::string($logsDir);
        $logs = $logsDir . '/test.log';
        $before = is_file($logs) ? (int) filesize($logs) : 0;
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_customer_asset"]')->form([
            'tmonier_equipment_customer_asset[equipmentModel]' => (string) $this->idOf($model),
            'tmonier_equipment_customer_asset[label]' => 'Truck',
            'tmonier_equipment_customer_asset[identifier]' => self::PLATE,
        ]));
        self::assertStringNotContainsString(self::PLATE, (string) $this->client->getResponse()->headers->get('Location'));
        $asset = $this->assets()->findByOwners([$this->idOf($customer)])[0];
        $this->client->request('GET', $this->shopUrl('/account/equipment/' . $asset->getId() . '/edit'));
        $this->client->request('GET', $this->shopUrl('/account/equipment/00000000-0000-4000-8000-000000000000/edit'));

        $written = is_file($logs) ? (string) file_get_contents($logs, false, null, $before) : '';
        self::assertStringNotContainsString(self::PLATE, $written);
    }

    public function testEveryFieldOfTheFormHasALabelAndTheTableHasHeaders(): void
    {
        $customer = $this->catalog()->customer();
        $this->catalog()->asset($customer, $this->catalog()->model('TRUCK', ['en_US' => 'Truck model X']), 'Lyon truck');
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));
        $fields = $crawler->filter('form[name="tmonier_equipment_customer_asset"] input:not([type="hidden"]), form[name="tmonier_equipment_customer_asset"] select');
        self::assertGreaterThanOrEqual(3, $fields->count());
        foreach ($fields->each(static fn (Crawler $field): string => (string) $field->attr('id')) as $id) {
            self::assertCount(1, $crawler->filter(sprintf('label[for="%s"]', $id)), $id);
        }

        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment'));
        self::assertCount(1, $crawler->filter('table caption'));
        self::assertCount(4, $crawler->filter('thead th[scope="col"]'));
        self::assertCount(1, $crawler->filter('tbody th[scope="row"]'));
    }

    public function testThePageIsTranslatedInFrench(): void
    {
        $customer = $this->catalog()->customer();
        $this->catalog()->asset($customer, $this->catalog()->model('TRUCK', ['fr_FR' => 'Camion Modèle X']), 'Camion de Lyon');
        $french = $this->entityManager()->getRepository(LocaleInterface::class)->findOneBy(['code' => 'fr_FR']);
        self::assertInstanceOf(LocaleInterface::class, $french);
        $this->catalog()->channel()->addLocale($french);
        $this->entityManager()->flush();
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', '/fr_FR/account/equipment');

        self::assertResponseIsSuccessful();
        self::assertSame('Mon matériel', trim($crawler->filter('[data-test-tmonier-equipment-title]')->text()));
        self::assertSame('Plaque ou numéro de série', trim($crawler->filter('th')->eq(2)->text()));
    }

    public function testTheMenuOfTheAccountLinksToTheFleet(): void
    {
        $customer = $this->catalog()->customer();
        $this->loginShopUser($customer);

        $crawler = $this->client->request('GET', $this->shopUrl('/account/dashboard'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter(sprintf('a[href="%s"]', $this->shopUrl('/account/equipment'))));
    }

    private function csrfTokenOfTheForm(): string
    {
        $crawler = $this->client->request('GET', $this->shopUrl('/account/equipment/new'));

        return (string) $crawler->filter('input[name="tmonier_equipment_customer_asset[_token]"]')->attr('value');
    }

    private function assets(): CustomerAssetRepositoryInterface
    {
        $repository = self::getContainer()->get('tmonier_equipment.repository.customer_asset');
        self::assertInstanceOf(CustomerAssetRepositoryInterface::class, $repository);

        return $repository;
    }
}
