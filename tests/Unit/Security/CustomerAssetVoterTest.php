<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAsset;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\FleetScopeInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\CustomerAssetVoter;

final class CustomerAssetVoterTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function attributes(): iterable
    {
        yield 'view' => [CustomerAssetVoter::VIEW];
        yield 'edit' => [CustomerAssetVoter::EDIT];
        yield 'delete' => [CustomerAssetVoter::DELETE];
    }

    /** @dataProvider attributes */
    public function testTheOwnerIsGranted(string $attribute): void
    {
        [$token, $asset] = $this->scenario(ownerId: 1, assetOwnerId: 1);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter([1])->vote($token, $asset, [$attribute]));
    }

    /** @dataProvider attributes */
    public function testAMemberOfTheSameCompanyIsGranted(string $attribute): void
    {
        [$token, $asset] = $this->scenario(ownerId: 2, assetOwnerId: 1);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter([2, 1])->vote($token, $asset, [$attribute]));
    }

    /** @dataProvider attributes */
    public function testAnotherCustomerIsDenied(string $attribute): void
    {
        [$token, $asset] = $this->scenario(ownerId: 3, assetOwnerId: 1);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter([3])->vote($token, $asset, [$attribute]));
    }

    public function testAVisitorIsDenied(): void
    {
        $asset = $this->asset(1);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter([])->vote(new NullToken(), $asset, [CustomerAssetVoter::VIEW]));
    }

    public function testAnAdministratorIsNotACustomer(): void
    {
        $token = new UsernamePasswordToken(new AdminUser(), 'admin', ['ROLE_ADMINISTRATION_ACCESS']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter([1])->vote($token, $this->asset(1), [CustomerAssetVoter::VIEW]));
    }

    public function testOtherAttributesAndSubjectsAbstain(): void
    {
        [$token, $asset] = $this->scenario(ownerId: 1, assetOwnerId: 1);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter([1])->vote($token, $asset, ['SOMETHING_ELSE']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter([1])->vote($token, new \stdClass(), [CustomerAssetVoter::VIEW]));
    }

    /** @param list<int> $scope */
    private function voter(array $scope): CustomerAssetVoter
    {
        $fleetScope = new class($scope) implements FleetScopeInterface {
            /** @param list<int> $scope */
            public function __construct(private readonly array $scope)
            {
            }

            public function ownerIds(\Sylius\Component\Customer\Model\CustomerInterface $customer): array
            {
                return $this->scope;
            }
        };

        return new CustomerAssetVoter($fleetScope);
    }

    /** @return array{UsernamePasswordToken, CustomerAsset} */
    private function scenario(int $ownerId, int $assetOwnerId): array
    {
        $customer = $this->customer($ownerId);
        $user = new ShopUser();
        $user->setCustomer($customer);

        return [new UsernamePasswordToken($user, 'shop', ['ROLE_USER']), $this->asset($assetOwnerId)];
    }

    private function asset(int $ownerId): CustomerAsset
    {
        $asset = new CustomerAsset();
        $asset->setCustomer($this->customer($ownerId));

        return $asset;
    }

    private function customer(int $id): Customer
    {
        $customer = new Customer();
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, $id);

        return $customer;
    }
}
