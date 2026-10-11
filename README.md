# CM Order & Customer Tagger for WooCommerce

Automatically tag WooCommerce orders and customers using configurable rule conditions - no code required.

![Version](https://img.shields.io/badge/version-1.3.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple)
![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a)
![License](https://img.shields.io/badge/license-GPL--2.0-green)
![HPOS](https://img.shields.io/badge/HPOS-compatible-brightgreen)

**[Documentation](docs/)** · **[Changelog](CHANGELOG.md)** · **[claritymomentum.io](https://claritymomentum.io)**

---

## What It Does

Define rules in the WordPress admin. When an order is placed or updated, the plugin evaluates those rules and applies colour-coded tags to the order and/or customer automatically.

**Example rules:**
- Tag orders over $500 as `high-value`
- Tag first-time buyers as `new-customer`
- Tag orders paid by credit card as `card-payment`
- Tag customers from Canada as `ca-customer`
- Tag orders where billing and shipping addresses differ as `possible-gift`

Tags appear in your order list, on the order detail page, and on customer profiles - making it easy to filter, segment, and act on your data at a glance.

---

## Features

- **Rule-based automatic tagging** - set conditions once, tags apply to every matching order forever
- **14 starter tags and rules** installed on activation (High Value, First Order, Repeat Buyer, Guest Checkout, and more)
- **Colour-coded tags** with custom hex colours for instant visual scanning
- **Inline tag editor** - add or remove tags directly from the order or customer list without opening each record
- **Filter by tag** on the orders and customers list screens
- **Bulk re-run** - retroactively apply rules to existing orders in one action
- **HPOS compatible** - fully tested with WooCommerce High-Performance Order Storage
- **Separate order and customer tag types** - tag the transaction and the person independently

---

## Rule Conditions

Build rules using any combination of:

| Condition | Options |
|-----------|---------|
| Order total | greater than, less than, equals |
| Item count | greater than, less than, equals |
| Shipping method | contains, equals |
| Payment method | contains, equals |
| Order status | any WooCommerce status |
| Shipping country | is, is not |
| Billing country | is, is not |
| Customer type | guest, registered |
| First order | yes / no |
| Coupon used | yes / no |
| Address mismatch | billing differs from shipping |
| Customer lifetime value | greater than, less than |
| Customer order count | greater than, less than, equals |
| Days since last order | greater than, less than |
| Customer account age | greater than, less than (days) |

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.1+

---

## Installation

### From ZIP (recommended for most users)

1. Download the latest release ZIP from the [Releases](../../releases) page
2. In WordPress admin: **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and activate
4. Go to **WooCommerce → Order & Customer Tagger** to manage tags and rules

### From Source

```bash
git clone https://github.com/claritymomentum/wc-order-customer-tagger.git
cd wc-order-customer-tagger
composer install --no-dev
```

Upload the folder to `wp-content/plugins/` and activate in WordPress admin.

---

## Quick Start

See [docs/getting-started.md](docs/getting-started.md) for a full walkthrough.

1. After activation, 14 starter tags and rules are created automatically
2. Place a test order to see tags applied in real time
3. Go to **WooCommerce → Order & Customer Tagger** to edit or add rules
4. Use the tag filter dropdown on the Orders screen to segment your orders

---

## Screenshots

1. Order list with colour-coded tag badges
2. Inline tag editor popover
3. Tag management screen
4. Rule builder with conditions
5. Customer profile with applied tags

*(Screenshots coming soon)*

---

## Development

```bash
composer install
composer test        # PHPUnit 10 - no WP/WC install required
composer phpcs       # PHPCS code style check
composer phpstan     # Static analysis
```

Tests use PHP stubs only - no WordPress or WooCommerce installation needed.

---

## Support & Custom Development

This plugin is free and open source (GPL-2.0).

For custom rule conditions, WooCommerce integrations, or eCommerce development:

**[Clarity Momentum](https://claritymomentum.io)** - commerce integrations and automation for growing businesses.

---

## License

GPL-2.0-or-later - see [LICENSE](LICENSE)
