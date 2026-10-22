<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Filter;

use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Entity\CustomerAssetInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Fleet\FleetScopeInterface;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepository;
use Tmonier\SyliusCustomerAssetManagementPlugin\Repository\CustomerAssetRepositoryInterface;

/**
 * Decides, once per request, whether the filter is offered and what it selects. The state is the
 * `equipment` query parameter (`all`, the UUID of a machine, or `off`), kept in the session so that
 * it follows the customer from page to page; it is put back in the query so that the links of the
 * page (pagination, sorting) carry it. Nothing is read from the database for a visitor, and the
 * compatibility is queried only when the filter is selected.
 */
final class CompatibilityFilterResolver
{
    public const PARAMETER = 'equipment';

    public const OFF = 'off';

    private const SESSION_KEY = 'tmonier_equipment.compatibility_filter';

    /** @var \WeakMap<Request, array{0: CompatibilityFilter|null}> */
    private \WeakMap $resolved;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly FleetScopeInterface $fleetScope,
        private readonly CustomerAssetRepositoryInterface $assets,
    ) {
        $this->resolved = new \WeakMap();
    }

    public function resolve(Request $request): ?CompatibilityFilter
    {
        $this->resolved[$request] ??= [$this->compute($request)];

        return $this->resolved[$request][0];
    }

    private function compute(Request $request): ?CompatibilityFilter
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $customer = $user instanceof ShopUserInterface ? $user->getCustomer() : null;
        if (!$customer instanceof \Sylius\Component\Customer\Model\CustomerInterface) {
            return null;
        }

        $ownerIds = $this->fleetScope->ownerIds($customer);
        $fleet = $this->assets->findByOwners($ownerIds);
        if ([] === $fleet) {
            return null;
        }

        $selection = $this->selection($request, $fleet);
        if (null !== $selection && null === $this->requested($request)) {
            $request->query->set(self::PARAMETER, $selection);
        }

        return new CompatibilityFilter($fleet, $ownerIds, $selection);
    }

    /** @param list<CustomerAssetInterface> $fleet */
    private function selection(Request $request, array $fleet): ?string
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        $requested = $this->requested($request);
        if (null === $requested) {
            $stored = $session?->get(self::SESSION_KEY);
            $requested = \is_string($stored) && (CompatibilityFilter::ALL === $stored || CustomerAssetRepository::isUuid($stored)) ? $stored : null;
        }

        $selection = $this->normalise($requested, $fleet);
        if ($session instanceof \Symfony\Component\HttpFoundation\Session\SessionInterface && null !== $this->requested($request)) {
            null === $selection ? $session->remove(self::SESSION_KEY) : $session->set(self::SESSION_KEY, $selection);
        }

        return $selection;
    }

    /**
     * "all" for a machine that is not in the fleet: a machine of someone else must not be told
     * apart from a machine that does not exist, and nothing says that it exists.
     *
     * @param list<CustomerAssetInterface> $fleet
     */
    private function normalise(?string $requested, array $fleet): ?string
    {
        if (null === $requested || self::OFF === $requested) {
            return null;
        }
        if (CompatibilityFilter::ALL === $requested) {
            return CompatibilityFilter::ALL;
        }

        foreach ($fleet as $asset) {
            if ($asset->getId() === $requested) {
                return $requested;
            }
        }

        return CompatibilityFilter::ALL;
    }

    /** What the query asks for: "off", "all" or the UUID of a machine; anything else is ignored. */
    private function requested(Request $request): ?string
    {
        $value = $request->query->all()[self::PARAMETER] ?? null;
        if (!\is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return \in_array($value, [self::OFF, CompatibilityFilter::ALL], true) || CustomerAssetRepository::isUuid($value) ? $value : null;
    }
}
