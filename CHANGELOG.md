# Changelog — Harper Order & Customer Tagger (WooCommerce)

All notable changes to this project will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.3.0] — 2026-05-27

### Added
- Filter-by-tag dropdown on WooCommerce Orders grid (HPOS + legacy)
- Filter-by-tag dropdown on WordPress Users (Customers) grid
- "Re-run Tagger Rules" bulk action on Orders grid — retroactively evaluates rules on selected orders
- Admin notice after bulk re-run showing count of orders processed

### Fixed
- Popover AJAX race condition: popover was shown before the first `harper_tagger_get_all_tags`
  request returned, leaving title and tag sections empty on first open
- Duplicate `select#harper_customer_tag_id` rendered on Users grid (WP 5.9+ fires
  `restrict_manage_users` twice); now guarded with `$which !== 'top'` check

### Security
- Hardened `ajaxGetAllTags()`: table name now passed through `$wpdb->prepare()` with `%i`
  identifier placeholder instead of raw string interpolation

## [1.2.0] — 2026-05-26

### Added
- Orders grid: Tags column with inline click-to-edit popover (add/remove tags without page reload)
- Customers grid: Tags column with inline click-to-edit popover
- Rule engine wired to `woocommerce_checkout_order_created` and order status change hooks
- Order detail meta box showing applied tags
- Customer profile section showing applied tags
- Admin Rules CRUD with JS condition builder (15 order/customer context fields)

### Fixed
- `trigger` renamed to `rule_trigger` (MySQL reserved word)
- `CREATE TABLE IF NOT EXISTS` replaced with `CREATE TABLE` for dbDelta compatibility
- Missing context fields (e.g. `shipping_country` on customer context) now return `false`
  instead of empty string, preventing incorrect `not_in` matches

## [1.1.0] — 2026-05-20

### Added
- Admin Tags CRUD: list, add, edit, delete
- Colour badge picker (WP colour picker) with live preview
- Optional icon image URL per tag
- DB schema: 4 tables (tags, rules, order_tags, customer_tags)
- 14 seed tags and 14 seed rules on first activation
- Rule engine: Condition / Rule / Engine (pure PHP, zero WP dependencies)
- ContextBuilder: `WC_Order` + `WC_Customer` → plain context array
- Unit tests: 166 tests, 202 assertions

## [1.0.0] — 2026-05-15

### Added
- Initial release
