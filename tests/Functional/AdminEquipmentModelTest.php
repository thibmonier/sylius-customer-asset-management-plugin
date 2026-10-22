<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\EquipmentModelRepositoryInterface;

final class AdminEquipmentModelTest extends WebTestCase
{
    use EquipmentTestTrait;

    public function testAnAdministratorCreatesAModelNamedInTwoLanguages(): void
    {
        $this->loginAdmin();
        $code = 'TRUCK-' . strtoupper($this->catalog()->suffix);

        $crawler = $this->client->request('GET', '/admin/equipment-models/new');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="tmonier_equipment_equipment_model"]')->form();
        $values = ['tmonier_equipment_equipment_model[code]' => $code];
        $values['tmonier_equipment_equipment_model[translations][fr_FR][name]'] = 'Camion Modèle X';
        $values['tmonier_equipment_equipment_model[translations][en_US][name]'] = 'Truck model X';
        $this->client->submit($form, $values);

        self::assertResponseRedirects();
        $model = $this->models()->findOneByCode($code);
        self::assertInstanceOf(EquipmentModelInterface::class, $model);
        self::assertSame('Camion Modèle X', $model->getTranslation('fr_FR')->getName());
        self::assertSame('Truck model X', $model->getTranslation('en_US')->getName());
    }

    public function testTheListShowsTheNameInTheLanguageOfTheAdministrationAndTheCodeWithoutTranslation(): void
    {
        $named = $this->catalog()->model('NAMED', ['en_US' => 'Truck model X', 'fr_FR' => 'Camion Modèle X']);
        $unnamed = $this->catalog()->model('FRONLY', ['fr_FR' => 'Seulement français']);
        $this->loginAdmin();
        // Models are read back from the database, which assigns the locale of the administration.
        $this->entityManager()->clear();

        $crawler = $this->client->request('GET', '/admin/equipment-models/?criteria[search][value]=' . $this->catalog()->suffix);

        self::assertResponseIsSuccessful();
        self::assertSame('Truck model X', trim($crawler->filter(sprintf('[data-test-tmonier-equipment-model-name="%s"]', (string) $named->getCode()))->text()));
        self::assertSame((string) $unnamed->getCode(), trim($crawler->filter(sprintf('[data-test-tmonier-equipment-model-name="%s"]', (string) $unnamed->getCode()))->text()));
    }

    public function testTheListCountsTheCompatibleVariantsOfEachModel(): void
    {
        $model = $this->catalog()->model('COUNTED', ['en_US' => 'Counted']);
        $empty = $this->catalog()->model('EMPTY', ['en_US' => 'Empty']);
        $model->addCompatibleVariant($this->catalog()->product('Oil filter'));
        $model->addCompatibleVariant($this->catalog()->product('Air filter'));
        $this->entityManager()->flush();
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/?criteria[search][value]=' . $this->catalog()->suffix);

        self::assertSame('2', trim($crawler->filter(sprintf('[data-test-tmonier-equipment-model-variants="%s"]', (string) $model->getCode()))->text()));
        self::assertSame('0', trim($crawler->filter(sprintf('[data-test-tmonier-equipment-model-variants="%s"]', (string) $empty->getCode()))->text()));
    }

    public function testACodeAlreadyInUseIsRefused(): void
    {
        $existing = $this->catalog()->model('TAKEN', ['en_US' => 'Taken']);
        $before = $this->modelCount();
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/new');
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_equipment_model"]')->form(), [
            'tmonier_equipment_equipment_model[code]' => $existing->getCode(),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('This code is already in use.', (string) $this->client->getResponse()->getContent());
        self::assertSame($before, $this->modelCount());
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function invalidModels(): iterable
    {
        yield 'empty code' => ['', 'Name', 'Please enter the code of the equipment model.'];
        yield 'code of 65 characters' => [str_repeat('A', 65), 'Name', 'The code must not be longer than 64 characters.'];
        yield 'name of 256 characters' => ['VALID', str_repeat('n', 256), 'The name must not be longer than 255 characters.'];
    }

    /** @dataProvider invalidModels */
    public function testInvalidDataIsRefusedWithAMessagePerField(string $code, string $name, string $message): void
    {
        $before = $this->modelCount();
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/new');
        $this->client->submit($crawler->filter('form[name="tmonier_equipment_equipment_model"]')->form(), [
            'tmonier_equipment_equipment_model[code]' => $code,
            'tmonier_equipment_equipment_model[translations][en_US][name]' => $name,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString($message, (string) $this->client->getResponse()->getContent());
        self::assertSame($before, $this->modelCount());
    }

    public function testTheCodeCannotBeChangedOnceTheModelExists(): void
    {
        $model = $this->catalog()->model('FIXED', ['en_US' => 'Fixed']);
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/' . $model->getId() . '/edit');

        self::assertResponseIsSuccessful();
        self::assertNotNull($crawler->filter('input[name="tmonier_equipment_equipment_model[code]"]')->attr('disabled'));
    }

    public function testDeletingAModelRemovesItsLinksAndKeepsTheVariants(): void
    {
        $model = $this->catalog()->model('GONE', ['en_US' => 'Gone']);
        $first = $this->catalog()->product('Oil filter');
        $second = $this->catalog()->product('Air filter');
        $model->addCompatibleVariant($first);
        $model->addCompatibleVariant($second);
        $this->entityManager()->flush();
        $id = $this->idOf($model);
        $this->loginAdmin();

        $crawler = $this->client->request('GET', '/admin/equipment-models/?criteria[search][value]=' . $this->catalog()->suffix);
        $form = $crawler->filter(sprintf('form[action$="/admin/equipment-models/%d"]', $id))->form();
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager()->clear();
        self::assertNull($this->models()->find($id));
        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM equipment_model_variant WHERE equipment_model_id = ?', [$id]));
        self::assertSame(2, $this->fetchInt('SELECT COUNT(*) FROM sylius_product_variant WHERE id IN (?, ?)', [$this->idOf($first), $this->idOf($second)]));
    }

    public function testAnAdministratorWithoutTheRoleGets403AndNothingChanges(): void
    {
        $before = $this->modelCount();
        $this->loginAdmin(withModelsRole: false);

        $this->client->request('GET', '/admin/equipment-models/');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/admin/equipment-models/new', ['tmonier_equipment_equipment_model' => ['code' => 'SNEAKY']]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->modelCount());
    }

    public function testTheMenuOffersTheModelsToAdministratorsWithTheRoleOnly(): void
    {
        $this->loginAdmin();
        $crawler = $this->client->request('GET', '/admin/');
        self::assertCount(1, $crawler->filter('a[href="/admin/equipment-models/"]'));
    }

    private function modelCount(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM equipment_model');
    }

    private function models(): EquipmentModelRepositoryInterface
    {
        $repository = self::getContainer()->get('tmonier_equipment.repository.equipment_model');
        self::assertInstanceOf(EquipmentModelRepositoryInterface::class, $repository);

        return $repository;
    }
}
