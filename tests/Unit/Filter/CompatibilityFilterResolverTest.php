<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Filter\CompatibilityFilter;
use Tmonier\SyliusCustomerAssetManagementPlugin\Filter\CompatibilityFilterResolver;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\CustomerFleetScope;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;

final class CompatibilityFilterResolverTest extends TestCase
{
    private const MINE = '11111111-1111-4111-8111-111111111111';

    private const SECOND = '22222222-2222-4222-8222-222222222222';

    private const FOREIGN = '99999999-9999-4999-8999-999999999999';

    public function testAVisitorIsNeverOfferedTheFilter(): void
    {
        $resolver = $this->resolver(signedIn: false, fleet: [self::MINE]);

        self::assertNull($resolver->resolve($this->request(['equipment' => 'all'])));
    }

    public function testACustomerWithoutMachineIsNeverOfferedTheFilter(): void
    {
        $resolver = $this->resolver(signedIn: true, fleet: []);

        self::assertNull($resolver->resolve($this->request(['equipment' => 'all'])));
    }

    public function testNothingIsAppliedUntilTheCustomerAsksForIt(): void
    {
        $filter = $this->resolver(signedIn: true, fleet: [self::MINE])->resolve($this->request([]));

        self::assertInstanceOf(CompatibilityFilter::class, $filter);
        self::assertFalse($filter->isActive());
        self::assertSame([self::MINE], $this->assetIds($filter));
    }

    public function testAllTheEquipmentIsTheDefaultOnceEnabled(): void
    {
        $filter = $this->resolver(signedIn: true, fleet: [self::MINE, self::SECOND])->resolve($this->request(['equipment' => 'all']));

        self::assertNotNull($filter);
        self::assertTrue($filter->isActive());
        self::assertNull($filter->assetId());
        self::assertSame('all', $filter->selection());
    }

    public function testOneMachineCanBeChosen(): void
    {
        $filter = $this->resolver(signedIn: true, fleet: [self::MINE, self::SECOND])->resolve($this->request(['equipment' => strtoupper(self::SECOND)]));

        self::assertNotNull($filter);
        self::assertSame(self::SECOND, $filter->assetId());
        self::assertSame(self::SECOND, $filter->selection());
    }

    public function testTheMachineOfAnotherCustomerIsIgnoredWithoutAnyHint(): void
    {
        $filter = $this->resolver(signedIn: true, fleet: [self::MINE])->resolve($this->request(['equipment' => self::FOREIGN]));

        self::assertNotNull($filter);
        self::assertTrue($filter->isActive());
        self::assertNull($filter->assetId());
        self::assertSame('all', $filter->selection());
    }

    public function testTheStateIsKeptInTheSessionAndPutBackInTheQuery(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $resolver = $this->resolver(signedIn: true, fleet: [self::MINE, self::SECOND]);
        $resolver->resolve($this->request(['equipment' => self::SECOND], $session));

        $later = $this->request([], $session);
        $filter = $resolver->resolve($later);

        self::assertNotNull($filter);
        self::assertSame(self::SECOND, $filter->assetId());
        // The links of the page (pagination, sorting) are built from the query: the URL shows the state.
        self::assertSame(self::SECOND, $later->query->get('equipment'));
    }

    public function testOffRemovesTheFilterAndForgetsIt(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $resolver = $this->resolver(signedIn: true, fleet: [self::MINE]);
        $resolver->resolve($this->request(['equipment' => 'all'], $session));

        $off = $resolver->resolve($this->request(['equipment' => 'off'], $session));
        $after = $resolver->resolve($this->request([], $session));

        self::assertNotNull($off);
        self::assertFalse($off->isActive());
        self::assertNotNull($after);
        self::assertFalse($after->isActive());
    }

    public function testGarbageInTheQueryIsIgnored(): void
    {
        foreach (['', 'nonsense', "1' OR '1'='1", '12'] as $value) {
            $filter = $this->resolver(signedIn: true, fleet: [self::MINE])->resolve($this->request(['equipment' => $value]));

            self::assertNotNull($filter);
            self::assertFalse($filter->isActive(), $value);
        }
        $filter = $this->resolver(signedIn: true, fleet: [self::MINE])->resolve($this->request(['equipment' => ['all']]));
        self::assertNotNull($filter);
        self::assertFalse($filter->isActive());
    }

    public function testTheFleetIsReadOncePerRequest(): void
    {
        $repository = $this->createMock(CustomerAssetRepositoryInterface::class);
        $repository->expects(self::once())->method('findByOwners')->willReturn([$this->asset(self::MINE)]);
        $resolver = new CompatibilityFilterResolver($this->tokenStorage(true), new CustomerFleetScope(), $repository);
        $request = $this->request(['equipment' => 'all']);

        $resolver->resolve($request);
        $resolver->resolve($request);
    }

    /** @return list<string> */
    private function assetIds(CompatibilityFilter $filter): array
    {
        return array_map(static fn (CustomerAssetInterface $asset): string => $asset->getId(), $filter->assets());
    }

    /** @param list<string> $fleet */
    private function resolver(bool $signedIn, array $fleet): CompatibilityFilterResolver
    {
        $repository = $this->createStub(CustomerAssetRepositoryInterface::class);
        $repository->method('findByOwners')->willReturn(array_map($this->asset(...), $fleet));

        return new CompatibilityFilterResolver($this->tokenStorage($signedIn), new CustomerFleetScope(), $repository);
    }

    private function asset(string $id): CustomerAsset
    {
        $asset = new CustomerAsset();
        (new \ReflectionProperty(CustomerAsset::class, 'id'))->setValue($asset, $id);

        return $asset;
    }

    private function tokenStorage(bool $signedIn): TokenStorage
    {
        $storage = new TokenStorage();
        if ($signedIn) {
            $customer = new Customer();
            (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, 5);
            $user = new ShopUser();
            $user->setCustomer($customer);
            $storage->setToken(new UsernamePasswordToken($user, 'shop', ['ROLE_USER']));
        }

        return $storage;
    }

    /** @param array<string, mixed> $query */
    private function request(array $query, ?Session $session = null): Request
    {
        $request = new Request($query);
        $request->setSession($session ?? new Session(new MockArraySessionStorage()));

        return $request;
    }
}
