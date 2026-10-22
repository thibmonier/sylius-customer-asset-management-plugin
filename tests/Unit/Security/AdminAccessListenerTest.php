<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusCustomerAssetManagementPlugin\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tmonier\SyliusCustomerAssetManagementPlugin\EventListener\AdminAccessListener;
use Tmonier\SyliusCustomerAssetManagementPlugin\Security\EquipmentAdminAccess;

final class AdminAccessListenerTest extends TestCase
{
    public function testAnAdministratorWithoutTheRoleIsDeniedOnThePluginRoutes(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->listener(granted: false)($this->event('tmonier_equipment_admin_equipment_model_index'));
    }

    public function testAnAdministratorWithTheRoleIsLetThrough(): void
    {
        $this->listener(granted: true)($this->event('tmonier_equipment_admin_equipment_model_index'));

        $this->addToAssertionCount(1);
    }

    public function testOtherRoutesAreNotConcerned(): void
    {
        $this->listener(granted: false)($this->event('sylius_admin_product_index'));

        $this->addToAssertionCount(1);
    }

    public function testNoTokenMeansTheFirewallDecides(): void
    {
        $access = new EquipmentAdminAccess($this->checker(false), new TokenStorage(), 'ROLE_X');

        self::assertFalse($access->canManageModels());
    }

    private function listener(bool $granted): AdminAccessListener
    {
        $storage = new TokenStorage();
        $storage->setToken(new NullToken());

        return new AdminAccessListener(new EquipmentAdminAccess($this->checker($granted), $storage, 'ROLE_X'));
    }

    private function checker(bool $granted): AuthorizationCheckerInterface
    {
        return new class($granted) implements AuthorizationCheckerInterface {
            public function __construct(private readonly bool $granted)
            {
            }

            public function isGranted(mixed $attribute, mixed $subject = null): bool
            {
                return $this->granted;
            }
        };
    }

    private function event(string $route): RequestEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        return new RequestEvent($this->createStub(\Symfony\Component\HttpKernel\HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
