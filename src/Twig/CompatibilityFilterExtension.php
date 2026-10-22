<?php

declare(strict_types=1);

namespace Tmonier\SyliusCustomerAssetManagementPlugin\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Tmonier\SyliusCustomerAssetManagementPlugin\Filter\CompatibilityFilter;
use Tmonier\SyliusCustomerAssetManagementPlugin\Filter\CompatibilityFilterResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CompatibilityFilterExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CompatibilityFilterResolver $resolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tmonier_equipment_compatibility', $this->filter(...)),
            new TwigFunction('tmonier_equipment_query_fields', $this->queryFields(...)),
            new TwigFunction('tmonier_equipment_listing_query', $this->listingQuery(...)),
        ];
    }

    /** The filter offered to the customer of the current request, null for a visitor or an empty fleet. */
    public function filter(): ?CompatibilityFilter
    {
        $request = $this->requestStack->getMainRequest();

        return $request instanceof \Symfony\Component\HttpFoundation\Request ? $this->resolver->resolve($request) : null;
    }

    /**
     * The query of the current listing as hidden fields (name, value), without the filter and the
     * page, so that the form of the control keeps the search, the sorting and the limit.
     *
     * @return list<array{name: string, value: string}>
     */
    public function queryFields(): array
    {
        $fields = [];
        $this->flatten($this->query([]), '', $fields);

        return $fields;
    }

    /**
     * The query of the current listing with the given parameters replaced, without the page.
     *
     * @param array<string, string|null> $overrides
     *
     * @return array<string, mixed>
     */
    public function listingQuery(array $overrides): array
    {
        return $this->query($overrides);
    }

    /**
     * @param array<string, string|null> $overrides
     *
     * @return array<string, mixed>
     */
    private function query(array $overrides): array
    {
        $query = $this->requestStack->getMainRequest()?->query->all() ?? [];
        unset($query['page'], $query[CompatibilityFilterResolver::PARAMETER]);

        return [...$query, ...array_filter($overrides, static fn (?string $value): bool => null !== $value)];
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<array{name: string, value: string}> $fields
     */
    private function flatten(array $node, string $prefix, array &$fields): void
    {
        foreach ($node as $key => $value) {
            $name = '' === $prefix ? (string) $key : sprintf('%s[%s]', $prefix, $key);
            if (\is_array($value)) {
                $this->flatten($value, $name, $fields);
            } elseif (\is_scalar($value)) {
                $fields[] = ['name' => $name, 'value' => (string) $value];
            }
        }
    }
}
