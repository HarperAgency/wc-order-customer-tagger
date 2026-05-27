# WooCommerce Order & Customer Tagger

Automatically tag WooCommerce orders and customers based on configurable rule conditions — no code required.

![Version](https://img.shields.io/badge/version-1.2.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple)
![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a)
![License](https://img.shields.io/badge/license-MIT-green)

---

## What It Does

Define rules in the WordPress admin. When an order is placed or updated, the plugin evaluates those rules and applies tags to the order and/or customer automatically.

**Example rules:**
- Tag orders over $500 as `high-value`
- Tag customers from California as `west-coast`
- Tag orders containing a specific product category as `subscription`
- Tag first-time buyers as `new-customer`

Tags appear on the order detail screen and the customer profile, making it easy to filter, segment, and act on your data.

---

## Features

- Rule builder UI in WP Admin — no coding needed
- Supports order total, product, category, customer location, and order count conditions
- Tags applied automatically on order create and status change
- Tags visible on order and customer screens in WP Admin
- Powered by the [Harper Rule Engine](https://github.com/harper-agency/harper-rule-engine)

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.1+

---

## Installation

### From ZIP (recommended)

1. Download the latest release ZIP from the [Releases](../../releases) page
2. In WordPress admin: **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and activate

### From Source

```bash
git clone https://github.com/harper-agency/wc-order-customer-tagger.git
cd wc-order-customer-tagger
composer install --no-dev
```

Then upload the folder to `wp-content/plugins/` and activate in WordPress admin.

---

## Usage

1. Go to **WooCommerce → Order Tagger → Rules**
2. Click **Add Rule**
3. Set your conditions (order total, product, location, etc.)
4. Set the tag(s) to apply
5. Save — rules are evaluated on every new order and status change

---

## Screenshots

*Coming soon*

---

## Development

```bash
composer install
composer test
```

Tests require PHP 8.1+ and are run via PHPUnit 10. No WordPress or WooCommerce installation needed — the test suite uses stubs.

---

## Support & Custom Development

This plugin is free and open source. For custom rule conditions, integrations, or WooCommerce development:

**[Harper Agency](https://harperservice.tech)** — WooCommerce & eCommerce development

---

## License

MIT — see [LICENSE](LICENSE)
