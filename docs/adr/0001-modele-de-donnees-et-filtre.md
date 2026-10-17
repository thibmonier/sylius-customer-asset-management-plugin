# ADR-0001: Data model and catalogue filter

## Status

Proposed (2026-10-17), to be accepted at the review of slice 1.

## Context

The plugin must store which equipment models exist, which product variants fit them, and which
machines each customer owns, then restrict catalogue listings accordingly (see `SPEC.md`).
Constraints: Sylius 2.3 resources, no change to core tables beyond what a plugin can do, ids not
predictable, server-side validation, GDPR (a plate may be personal data).

## Decision

1. **Three tables owned by the plugin**: `equipment_model` (code, translations), the join table
   `equipment_model_variant` (many-to-many model to `sylius_product_variant`, owning side on the model),
   and `customer_asset` (UUID primary key, customer, equipment model, label, identifier).
   No column is added to core entities.
2. **Compatibility is per variant**, not per product: spare parts differ by variant (size, material).
3. **Ownership**: `customer_asset.customer_id` is the only owner; a voter authorises view, edit and delete.
   A foreign asset answers 404.
4. **Filter by extending the listing query**: a Doctrine query extension adds a join on the compatibility
   table (`EXISTS` sub-select on the customer's models) to the product listing repository call,
   enabled by a query parameter. No search engine dependency.
5. **Deletion**: assets are deleted with the customer (`ON DELETE CASCADE` at database level and a
   Doctrine cascade); identifiers are never logged.
6. **Migrations** shipped by the plugin, prepended to the Doctrine migrations configuration as the
   other plugins do.

## Alternatives considered

### Product attribute holding compatible models
- Pros: no table, editable with existing admin screens.
- Cons: no relation to variants, no customer-side join, filter by string match; rejected.

### Dedicated search engine (Elasticsearch or similar)
- Pros: fast facets.
- Cons: heavy dependency for a plugin; most shops have no engine; rejected (YAGNI). An adapter can be added later without changing the model.

### Compatibility per product
- Pros: simpler admin.
- Cons: wrong for parts whose variants fit different machines; rejected.

### Integer ids for customer assets
- Pros: familiar.
- Cons: predictable in URLs; rejected for UUID.

## Consequences

### Positive
- Plugin-owned schema, easy to uninstall.
- The filter works with the default Sylius listing and any listing that uses the same repository method.

### Negative
- A listing that bypasses the Sylius repository (custom controller, API collection) needs its own hook.
- Large compatibility tables need an index on `(equipment_model_id, variant_id)`; included in the migration.
- Doctrine query extension ties the filter to the ORM listing implementation of Sylius 2.3.
