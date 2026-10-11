# Installation

## Requirements

- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- PHP 8.1 or higher

---

## Option 1: Install from ZIP (recommended)

1. Download the latest release ZIP from the [Releases](https://github.com/claritymomentum/wc-order-customer-tagger/releases) page
2. Log in to your WordPress admin
3. Go to **Plugins → Add New**
4. Click **Upload Plugin**
5. Choose the downloaded ZIP file and click **Install Now**
6. Click **Activate Plugin**

On first activation, the plugin creates 14 starter tags and rules automatically. No configuration is required to start tagging orders.

---

## Option 2: Install from Source

Clone the repository and install dependencies:

```bash
git clone https://github.com/claritymomentum/wc-order-customer-tagger.git
cd wc-order-customer-tagger
composer install --no-dev
```

Upload the `wc-order-customer-tagger` folder to your site's `wp-content/plugins/` directory, then activate from **Plugins** in the WordPress admin.

---

## Verifying the Installation

After activation:

1. Go to **WooCommerce → Order & Customer Tagger**
2. You should see 14 pre-created tags listed (High Value, First Order, Repeat Buyer, etc.)
3. Click **Rules** to see the corresponding starter rules

Place a test order that meets one of the starter rule conditions (for example, an order totalling over $500) and check that the `high-value` tag appears on the order.

---

## Updating

### Via WordPress admin

When a new version is available, update via **Plugins → Installed Plugins** as you would any other plugin. All existing tags, rules, and applied tags are preserved across updates.

### From source

Pull the latest changes, run `composer install --no-dev`, and replace the plugin folder. No database migration is needed between minor versions.

---

## Uninstalling

Deactivating the plugin does not remove your data. To fully remove all tags, rules, and applied tag data, uninstall the plugin via **Plugins → Installed Plugins → Delete**. The uninstall routine drops the plugin's database tables and removes all related options.

> **Note:** Uninstalling is permanent. There is no export before delete. If you want to preserve your tag and rule configuration, export a database backup before uninstalling.
