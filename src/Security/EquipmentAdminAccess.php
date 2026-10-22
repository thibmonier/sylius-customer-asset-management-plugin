<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/** Who may manage the equipment models in the administration: holders of the configured role. */
final readonly class EquipmentAdminAccess
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage,
        private string $role,
    ) {
    }

    public function canManageModels(): bool
    {
        return $this->tokenStorage->getToken() instanceof \Symfony\Component\Security\Core\Authentication\Token\TokenInterface && $this->authorizationChecker->isGranted($this->role);
    }
}
