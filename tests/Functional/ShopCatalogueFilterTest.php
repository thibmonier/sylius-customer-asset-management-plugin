<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Functional;

use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Support\EquipmentTestTrait;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\EquipmentModelInterface;
use Webmozart\Assert\Assert;

final class ShopCatalogueFilterTest extends WebTestCase
{
    use EquipmentTestTrait {
        setUp as private setUpClientAndTransaction;
    }

    private TaxonInterface $taxon;

    private EquipmentModelInterface $lyonModel;

    private EquipmentModelInterface $forkliftModel;

    private CustomerAssetInterface $lyon;

    private CustomerAssetInterface $forklift;

    /** The catalogue of the scenario: 40 products, 7 fit the truck of Lyon, 6 the forklift (1 fits both: 12 in all). */
    protected function setUp(): void
    {
        $this->setUpClientAndTransaction();
        $catalog = $this->catalog();
        $this->taxon = $catalog->taxon('PARTS');
        $this->lyonModel = $catalog->model('TRUCK', ['en_US' => 'Truck model X']);
        $this->forkliftModel = $catalog->model('FORKLIFT', ['en_US' => 'Forklift']);
        for ($index = 1; $index <= 40; ++$index) {
            $variant = $catalog->product(sprintf('Part %02d%s', $index, $index <= 12 ? ' filter' : ''), $this->taxon);
            if ($index <= 7) {
                $this->lyonModel->addCompatibleVariant($variant);
            }
            if ($index >= 7 && $index <= 12) {
                $this->forkliftModel->addCompatibleVariant($variant);
            }
        }
        $this->entityManager()->flush();
        $this->claire = $catalog->customer('claire');
        $this->lyon = $catalog->asset($this->claire, $this->lyonModel, 'Lyon truck', 'AB-123-CD');
        $this->forklift = $catalog->asset($this->claire, $this->forkliftModel, 'Forklift', 'FK-001');
    }

    private \Sylius\Component\Core\Model\CustomerInterface $claire;

    public function testFilteringOnAllTheEquipmentKeepsTheProductsThatFitOneOfTheMachines(): void
    {
        $this->loginShopUser($this->claire);

        $crawler = $this->listing(['equipment' => 'all', 'limit' => 27]);

        self::assertResponseIsSuccessful();
        self::assertCount(12, $this->products($crawler));
        self::assertCount(1, $crawler->filter('[data-test-tmonier-equipment-filter]'));
    }

    public function testWithoutTheFilterTheWholeCategoryIsListed(): void
    {
        $this->loginShopUser($this->claire);

        $crawler = $this->listing(['limit' => 27]);

        self::assertCount(27, $this->products($crawler));
        self::assertCount(1, $crawler->filter('[data-test-tmonier-equipment-filter]'));
    }

    public function testFilteringOnOneMachine(): void
    {
        $this->loginShopUser($this->claire);

        $lyon = $this->listing(['equipment' => $this->lyon->getId(), 'limit' => 27]);
        self::assertCount(7, $this->products($lyon));

        $forklift = $this->listing(['equipment' => $this->forklift->getId(), 'limit' => 27]);
        self::assertCount(6, $this->products($forklift));
    }

    public function testTheFilterIsKeptFromOnePageAndOneCategoryToTheNext(): void
    {
        $other = $this->catalog()->taxon('OTHER');
        $variant = $this->catalog()->product('Other part', $other);
        $this->lyonModel->addCompatibleVariant($variant);
        $this->entityManager()->flush();
        $this->loginShopUser($this->claire);

        // 12 products, 9 per page: the links of the pagination carry the choice in the URL.
        $first = $this->listing(['equipment' => 'all']);
        self::assertCount(9, $this->products($first));
        self::assertGreaterThan(0, $first->filter('a[href*="page=2"][href*="equipment=all"]')->count());
        $second = $this->client->click($first->filter('a[href*="page=2"]')->link());
        self::assertCount(3, $this->products($second));
        self::assertSame('all', $second->filter('select[name="equipment"] option[selected]')->attr('value'));

        // Another category, no parameter in the link: the session still holds the choice.
        $third = $this->client->request('GET', $this->shopUrl('/taxons/' . $other->getSlug()));
        self::assertCount(1, $this->products($third));
        self::assertSame('all', $third->filter('select[name="equipment"] option[selected]')->attr('value'));
    }

    public function testTheFilterAppliesToTheSearchOfTheCategory(): void
    {
        $this->loginShopUser($this->claire);

        $crawler = $this->listing(['equipment' => 'all', 'criteria' => ['search' => ['value' => 'filter']], 'limit' => 27]);

        self::assertCount(12, $this->products($crawler));

        $none = $this->listing(['equipment' => 'all', 'criteria' => ['search' => ['value' => 'Part 3']], 'limit' => 27]);
        self::assertCount(0, $this->products($none));
    }

    public function testWhenNothingFitsTheCustomerIsToldAndCanRemoveTheFilter(): void
    {
        $catalog = $this->catalog();
        $customer = $catalog->customer('lonely');
        $unfit = $catalog->asset($customer, $catalog->model('ORPHAN', ['en_US' => 'Orphan model']), 'Orphan', 'OR-1');
        $this->loginShopUser($customer);

        $crawler = $this->listing(['equipment' => $unfit->getId()]);

        self::assertCount(0, $this->products($crawler));
        self::assertStringContainsString('No part compatible with your equipment', $crawler->filter('[data-test-tmonier-equipment-filter]')->text());
        $link = $crawler->selectLink('Remove the filter')->link();
        $crawler = $this->client->click($link);
        self::assertCount(9, $this->products($crawler));
    }

    public function testAVisitorAndACustomerWithoutMachineSeeNoControlAndTheListingIsUnchanged(): void
    {
        $visitor = $this->listing(['equipment' => 'all', 'limit' => 27]);
        self::assertCount(0, $visitor->filter('[data-test-tmonier-equipment-filter]'));
        self::assertCount(27, $this->products($visitor));

        $this->loginShopUser($this->catalog()->customer('empty'));
        $empty = $this->listing(['equipment' => 'all', 'limit' => 27]);
        self::assertCount(0, $empty->filter('[data-test-tmonier-equipment-filter]'));
        self::assertCount(27, $this->products($empty));
    }

    public function testTheMachineOfAnotherCustomerInTheUrlIsIgnored(): void
    {
        $catalog = $this->catalog();
        $stranger = $catalog->customer('stranger');
        $foreign = $catalog->asset($stranger, $this->forkliftModel, 'Secret forklift', 'SECRET-1');
        $this->loginShopUser($this->claire);

        $crawler = $this->listing(['equipment' => $foreign->getId(), 'limit' => 27]);

        self::assertCount(12, $this->products($crawler));
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Secret forklift', $content);
        self::assertStringNotContainsString('SECRET-1', $content);
        self::assertSame('all', $crawler->filter('select[name="equipment"] option[selected]')->attr('value'));
    }

    public function testTheCompanySharesTheFilterOfItsMembers(): void
    {
        $catalog = $this->catalog();
        $company = $catalog->company();
        $paul = $catalog->customer('paul');
        $catalog->member($company, $this->claire);
        $catalog->member($company, $paul);
        $this->loginShopUser($paul);

        $crawler = $this->listing(['equipment' => 'all', 'limit' => 27]);

        self::assertCount(12, $this->products($crawler));
    }

    public function testNoCompatibilityQueryRunsUntilTheFilterIsEnabled(): void
    {
        $this->loginShopUser($this->claire);

        $this->client->enableProfiler();
        $this->listing(['limit' => 9]);
        $off = $this->selectQueries();
        $this->client->enableProfiler();
        $this->listing(['equipment' => 'all', 'limit' => 9]);
        $on = $this->selectQueries();

        // Filter off: the fleet of the control is the only query of the plugin, none about the compatibility.
        self::assertSame(['machines' => 1, 'compatibility' => 0], $off);
        // Filter on: still one fleet query, and the restriction only in the queries of the listing
        // (identifiers of the page, page, count), never one query per product.
        self::assertSame(1, $on['machines']);
        self::assertGreaterThanOrEqual(1, $on['compatibility']);
        self::assertLessThanOrEqual(3, $on['compatibility']);
    }

    public function testTheControlIsUsableWithTheKeyboard(): void
    {
        $this->loginShopUser($this->claire);

        $crawler = $this->listing([]);

        $select = $crawler->filter('[data-test-tmonier-equipment-filter] select[name="equipment"]');
        self::assertCount(1, $select);
        self::assertCount(1, $crawler->filter(sprintf('label[for="%s"]', $select->attr('id'))));
        self::assertCount(1, $crawler->filter('[data-test-tmonier-equipment-filter] button[type="submit"]'));
        self::assertCount(2 + 2, $select->filter('option'));
    }

    /** @param array<string, mixed> $query */
    private function listing(array $query): Crawler
    {
        return $this->client->request('GET', $this->shopUrl('/taxons/' . $this->taxon->getSlug()), $query);
    }

    /** @return list<string> */
    private function products(Crawler $crawler): array
    {
        return $crawler->filter('[data-test-products] .product-card, [data-test-products] [data-test-product-name]')->count() > 0
            ? $crawler->filter('[data-test-products] [data-test-product-name]')->each(static fn (Crawler $node): string => trim($node->text()))
            : $crawler->filter('[data-test-products] a[href*="/products/"]')->each(static fn (Crawler $node): string => (string) $node->attr('href'));
    }

    /**
     * The SELECT queries of the last request (profiler): those on the fleet and those that go
     * through the compatibility table.
     *
     * @return array{machines: int, compatibility: int}
     */
    private function selectQueries(): array
    {
        $profile = $this->client->getProfile();
        Assert::object($profile);
        $collector = $profile->getCollector('db');
        Assert::isInstanceOf($collector, DoctrineDataCollector::class);
        $machines = 0;
        $compatibility = 0;
        foreach ($collector->getQueries() as $queries) {
            Assert::isIterable($queries);
            foreach ($queries as $query) {
                Assert::isArray($query);
                $sql = $query['sql'] ?? '';
                Assert::string($sql);
                if (!str_starts_with($sql, 'SELECT')) {
                    continue;
                }
                if (str_contains($sql, 'equipment_model_variant')) {
                    ++$compatibility;
                } elseif (str_contains($sql, 'customer_asset')) {
                    ++$machines;
                }
            }
        }

        return ['machines' => $machines, 'compatibility' => $compatibility];
    }
}
