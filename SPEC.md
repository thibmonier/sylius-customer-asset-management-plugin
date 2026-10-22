# Specification — customer asset management plugin

Standalone plugin: a customer declares the equipment fleet they own, the catalogue can be filtered
to the spare parts compatible with it. Framed in US-054 (sprint 12), implemented in US-056 to
US-058 (sprint 21), see section 8. Checked against Sylius 2.2 and 2.3 (`sylius/sylius ^2.2.9 || ^2.3`).

## 1. Targets and need

Industry and building trades (B2B): a company buys spare parts and consumables for machines it
owns ("Truck model X, plate AB-123-CD"). Finding the right part means knowing which parts fit
which machine. Today the buyer filters by hand or phones the seller.

## 2. Vocabulary

| Term | Meaning |
|---|---|
| Equipment model | A kind of machine in the catalogue, managed by the merchant (e.g. "Truck model X"). Has a code and a name. |
| Compatibility | The statement "this product variant fits this equipment model". Many-to-many. |
| Customer asset | One machine owned by one customer: an equipment model, a free label, an identifier (plate or serial number). |
| Fleet | All the assets of one customer. If `tmonier/sylius-company-accounts-plugin` is installed, the fleet is shared with the members of the customer's company; the plugin works without it (optional dependency: `require-dev` and `suggest`). |
| Compatible parts filter | A catalogue listing restricted to variants compatible with at least one asset (or one chosen asset) of the signed-in customer. |

## 3. Administration (US-056)

- CRUD of equipment models (code, translated name).
- On a product variant: the list of compatible equipment models.
- No import in this slice (see section 6).

## 4. Customer area (US-057)

- A "My equipment" page in the account: list, add, edit, delete of the customer's assets
  (equipment model, label, plate or serial number).
- The owner of an asset is the customer who declared it. A customer sees and changes **only their own** fleet (plus, when the company accounts plugin is installed, the fleet of the members of their company); any other id answers 404, never 403 (no existence leak).
- Ids in URLs are UUIDs (not predictable); all input is validated on the server.

## 5. Catalogue filter (US-058)

- On taxon listings and search: a "Compatible with my equipment" control, offered only to signed-in customers with a non-empty fleet.
- Choice: all my equipment (default once enabled) or one asset. The state is kept in the session and in the URL query.
- Empty fleet: the filter is not offered and the listing is unchanged (fallback, never an empty page).
- Filter applied by extending the listing query (see ADR-0001), not by a search engine.

## 6. Out of scope

Maintenance or intervention management; bulk import of fleets; telematics; compatibility rules
by year or option (only model-to-variant); price or stock logic; a filter on the API.

## 7. Privacy and security

- A plate or serial number can identify a person (sole trader): personal data under GDPR. Deleted with the customer account; never logged; never in URLs.
- Authorisation on every asset route: the owner is the signed-in customer, checked server-side (voter).
- Rate limiting on asset creation is left to the application (documented in the README when implemented).

## 8. Slicing of the next stories

| Story | Content | Status |
|---|---|---|
| US-056 | Equipment models and variant compatibility (admin) | Done (0.1.0) |
| US-057 | Customer fleet CRUD (customer area) | Done (0.1.0) |
| US-058 | "Compatible parts" catalogue filter | Done (0.1.0) |
| US-059 | Publication (tag, Packagist, submitted by the maintainer) | Next |
| US-060 | Integration in the B2B demo (demo data, plugin list entry) | Next |

## 9. Notes on the implementation

- Administration: the screens of the equipment models need the role `admin_role` (default: any
  administrator); a model used by a machine cannot be deleted.
- The filter is the `equipment` query parameter (`all`, the UUID of a machine, or `off`) of the
  listing of a category and of its search, kept in the session. It is applied to the listing query of
  Sylius (grid `sylius_shop_product`); a listing that does not go through that grid needs its own hook.
- A customer is limited to `max_assets_per_customer` machines (default 50).
