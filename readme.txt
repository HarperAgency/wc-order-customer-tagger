=== CM Order & Customer Tagger ===
Contributors: claritymomentum
Tags: woocommerce, orders, customers, tags, automation
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically tag WooCommerce orders and customers using flexible, configurable rules.

== Description ==

CM Order & Customer Tagger lets you define rules that automatically apply colour-coded tags to orders and customers the moment they are created or updated - no manual work required.

**Order tags** appear in your order list, on the order detail page, and can be used to filter and bulk-process orders. **Customer tags** appear on customer profiles and the users list.

= Key features =

* Rule-based automatic tagging - set conditions once, tags apply forever
* 14 ready-to-use tags and rules installed on activation (High Value, First Order, Repeat Buyer, Guest Checkout, and more)
* Colour-coded tags with custom hex colours for instant visual scanning
* Inline tag editor - add or remove tags directly from the order or customer list without opening each record
* Filter orders and customers by tag
* Bulk re-run rules across selected orders
* Full WooCommerce HPOS (High-Performance Order Storage) compatibility
* Separate order and customer tag types

= Rule conditions =

Build rules using any combination of:

* Order total (greater than, less than, equals)
* Item count
* Shipping method
* Payment method
* Order status
* Shipping and billing country
* Guest vs. registered customer
* First order detection
* Coupon usage
* Billing/shipping address mismatch
* Customer lifetime value
* Customer order count
* Days since last order
* Customer account age

= Built by Clarity Momentum =

CM Order & Customer Tagger is built and maintained by [Clarity Momentum](https://claritymomentum.io), a commerce integrations and automation consultancy for growing businesses.

If you are working on WooCommerce store operations, you may also find these useful:

* **[SignalStream - Server-Side Tracking for WooCommerce](https://claritymomentum.io/signalstream/)** - reliable conversion tracking that works independently of ad blockers and browser privacy restrictions. Understand exactly which customer segments (including the ones you tag here) are converting from paid campaigns.
* **[Moneris Checkout for WooCommerce](https://claritymomentum.io/moneris/woocommerce/)** - a modern, PCI-compliant Moneris payment gateway with HPOS support and WooCommerce Subscriptions compatibility.
* **[Amazon Shipping for WooCommerce](https://claritymomentum.io/products/amazon-shipping/)** - connect your WooCommerce store to Amazon's carrier network directly.

For custom WooCommerce development, integrations, or enterprise automation - [contact the team at Clarity Momentum](https://claritymomentum.io/contact-us/).

= Feed notifications =

CM Order & Customer Tagger includes the Clarity Momentum module feed. When updates, new features, or important notices are available, a brief admin notice will appear - capped at two at a time and fully dismissible.

== Installation ==

1. Upload the `wc-order-customer-tagger` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **WooCommerce > Order & Customer Tagger** to manage tags and rules
4. Tags and starter rules are created automatically on first activation

== Frequently Asked Questions ==

= Does this work with WooCommerce HPOS? =

Yes. The plugin fully declares HPOS compatibility and works with both High-Performance Order Storage and the legacy post-based order system.

= When are rules evaluated? =

Rules run automatically when an order is placed and whenever an order status changes. You can also manually re-run rules on any order or use the bulk action on the orders list to re-process multiple orders at once.

= Can I create custom tags? =

Yes. Go to WooCommerce > Order & Customer Tagger and click Add New Tag. You can set a name, colour, and type (order or customer), then add rules to it.

= Will existing orders be tagged? =

Not automatically. Use the bulk action "Re-run tagging rules" on the Orders screen to retroactively tag existing orders.

= Does it tag customers automatically? =

Yes, for logged-in customers. When a logged-in customer places an order or their order status changes, customer rules are also evaluated. Guest orders are tagged at the order level only.

= Where can I get support? =

Open an issue on the [GitHub repository](https://github.com/claritymomentum/wc-order-customer-tagger) or [contact Clarity Momentum](https://claritymomentum.io/contact-us/) for priority support and custom development.

== Screenshots ==

1. Order list with colour-coded tag badges
2. Inline tag editor popover
3. Tag management screen
4. Rule builder with conditions
5. Customer profile with applied tags

== Changelog ==

= 1.3.0 =
* Added tag filter dropdowns to order and customer list screens
* Added bulk "Re-run tagging rules" action on orders list
* Fixed AJAX race condition in inline tag editor
* Security: hardened table name escaping in AJAX handlers
* Rebranded to Clarity Momentum

= 1.2.0 =
* Core feature release: tags, rules, inline editing, colour picker

= 1.1.0 =
* Initial release

== Upgrade Notice ==

= 1.3.0 =
Rebranded from Harper Order & Customer Tagger to CM Order & Customer Tagger. No data migration required - existing tags and rules are preserved.
