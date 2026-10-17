# Overlap with Sylius Plus

Checked on 2026-10-17 (task T-054-03). Question: does Sylius Plus already let a customer declare
the equipment they own and filter the catalogue to compatible parts (`SPEC.md`)?

## Verdict: not covered (with one uncertainty)

No public Sylius Plus source describes customer-declared equipment or fitment-based catalogue
filtering. The closest features are listed below; none matches.

| Plus feature | What it does (per the source) | Overlap |
|---|---|---|
| B2B Suite: Organization Management | One master account per company, shared billing data, order history, address books | None (organisations, not assets) |
| B2B Suite: Multiple Pricing Lists, Quick Shopping, Multiple Wishlists, Advanced Order Management | Per-organisation prices, bulk ordering by SKU, shared product lists, repeat orders | None |
| B2B Suite: Elasticsearch | Search over names, attributes, categories | Possible base to build a filter on, but no equipment data and no compatibility concept described |
| Product Configurator | Guided customisation of one product with options, live price, saved configuration | None (configures a product, does not match products to customer-owned machines) |
| Request for Quotation, Returns Management, Multi-source Inventory, Loyalty, Subscription, RBAC | Unrelated | None |

## Sources (all read 2026-10-17)

- https://sylius.com/plus/ : feature and edition list (Plus page, no date shown).
- https://plugins.sylius.com/blog/sylius-plus-module-overview-b2b-suite/ : "Sylius Plus module overview: B2B Suite", dated 5 March 2024.
- Web search "Sylius Plus features B2B customer assets equipment compatible products filter": no result mentioning equipment or fitment.

## Uncertainty

- The technical documentation of Sylius Plus (docs.sylius.com, Plus section) redirects to a sign-in
  and could not be read; only public marketing pages were checked. Release notes of Plus are not public either.
- "Advanced search feature" and "Product Configurator" are described only at marketing level.
- Therefore "not covered" means "not documented publicly", not "proven absent". Recheck with a Plus
  demo or the docs if the maintainer has access, before any promotion.

## Consequence for communication

Per the communication policy, a plugin that overlaps Sylius Plus is not promoted. With this verdict
there is no known overlap, so promotion remains the maintainer's decision after the recheck above.
Publication (public repository, Packagist) follows decision D1 of sprint 12 either way.
