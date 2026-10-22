<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;

/**
 * Denies (403) every administration route of the plugin to an administrator who does not hold the
 * configured role. Runs after the firewall: a visitor is redirected to the login by the firewall.
 */
final readonly class AdminAccessListener
{
    public const ROUTE_PREFIX = 'tmonier_equipment_admin_';

    public function __construct(private EquipmentAdminAccess $access)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!$event->isMainRequest() || !\is_string($route) || !str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        if (!$this->access->canManageModels()) {
            throw new AccessDeniedException('Managing the equipment models requires the configured role.');
        }
    }
}
