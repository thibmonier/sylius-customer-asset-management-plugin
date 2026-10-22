<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Security;

use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\FleetScopeInterface;

/**
 * A machine belongs to the fleet of its owner, shared with their company when there is one.
 * Deny by default: a visitor, an administrator or another customer have no access.
 *
 * @extends Voter<string, CustomerAssetInterface>
 */
final class CustomerAssetVoter extends Voter
{
    public const VIEW = 'TMONIER_EQUIPMENT_ASSET_VIEW';

    public const EDIT = 'TMONIER_EQUIPMENT_ASSET_EDIT';

    public const DELETE = 'TMONIER_EQUIPMENT_ASSET_DELETE';

    public function __construct(private readonly FleetScopeInterface $fleetScope)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true) && $subject instanceof CustomerAssetInterface;
    }

    // The fourth parameter (a Vote since Symfony 7.3) keeps the signature valid on every supported Symfony.
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        $ownerId = $subject->getCustomer()?->getId();
        $customer = $user instanceof ShopUserInterface ? $user->getCustomer() : null;
        if (!$customer instanceof \Sylius\Component\Customer\Model\CustomerInterface || !\is_int($ownerId)) {
            return false;
        }

        return \in_array($ownerId, $this->fleetScope->ownerIds($customer), true);
    }
}
