# Specification — customer asset management plugin

US-054 of the B2B demo (sprint 12), slice 1: framing only, no functional code. Standalone plugin:
a customer declares the equipment fleet they own, the catalogue can be filtered to the spare parts
compatible with it. Implementation slices: US-056 to US-060 (section 8).
Checked against Sylius 2.3 (`sylius/sylius ^2.3`).

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
| Fleet | All the assets of one customer. |
| Compatible parts filter | A catalogue listing restricted to variants compatible with at least one asset (or one chosen asset) of the signed-in customer. |

## 3. Administration (US-056)

- CRUD of equipment models (code, translated name).
- On a product variant: the list of compatible equipment models.
- No import in this slice (see section 6).

## 4. Customer area (US-057)

- A "My equipment" page in the account: list, add, edit, delete of the customer's assets
  (equipment model, label, plate or serial number).
- A customer sees and changes **only their own** fleet; any other id answers 404, never 403 (no existence leak).
- Ids in URLs are UUIDs (not predictable); all input is validated on the server.

## 5. Catalogue filter (US-058)

- On taxon listings and search: a "Compatible with my equipment" control, offered only to signed-in customers with a non-empty fleet.
- Choice: all my equipment (default once enabled) or one asset. The state is kept in the session and in the URL query.
- Empty fleet: the filter is not offered and the listing is unchanged (fallback, never an empty page).
- Filter applied by extending the listing query (see ADR-0001), not by a search engine.

## 6. Out of scope

Maintenance or intervention management; bulk import of fleets; telematics; compatibility rules
by year or option (only model-to-variant); price or stock logic; any promotion of the plugin
(depends on `docs/plus-overlap.md`).

## 7. Privacy and security

- A plate or serial number can identify a person (sole trader): personal data under GDPR. Deleted with the customer account; never logged; never in URLs.
- Authorisation on every asset route: the owner is the signed-in customer, checked server-side (voter).
- Rate limiting on asset creation is left to the application (documented in the README when implemented).

## 8. Slicing of the next stories

| Story | Content |
|---|---|
| US-056 | Equipment models and variant compatibility (admin) |
| US-057 | Customer fleet CRUD (customer area) |
| US-058 | "Compatible parts" catalogue filter |
| US-059 | Publication (tag, Packagist, submitted by the maintainer) |
| US-060 | Integration in the B2B demo (demo data, plugin list entry) |
