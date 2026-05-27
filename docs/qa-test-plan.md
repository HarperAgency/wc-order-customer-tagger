# Harper Order & Customer Tagger — QA Test Plan

## Overview

This document covers manual QA verification for the Harper Order & Customer Tagger plugin.  
Automated tests cover the rule engine and admin UI (see §7). This plan covers what automation
cannot — visual correctness, UX flows, and edge cases a real merchant would encounter.

**Current platform:** WooCommerce (v1.0.0)  
**Future platforms:** OpenMage, Shopify (not yet built — separate QA plans to follow)

**Test environment:** `https://harperservice.tech/icon_test/wc`  
**Admin login:** harperadmin / Harper@Test2026!  
**WooCommerce admin:** WooCommerce → Order & Cust. Tags

---

## 1. Installation & Activation

| # | Step | Expected |
|---|------|----------|
| 1.1 | Upload and activate plugin zip via WP → Plugins → Add New | No errors. Plugin appears in list as active. |
| 1.2 | Check WooCommerce sidebar | "Order & Cust. Tags" submenu appears under WooCommerce |
| 1.3 | Click "Order & Cust. Tags" | List page loads. 14 seed tags visible (10 order, 4 customer). |
| 1.4 | Check database | Tables `harper_tagger_tags`, `harper_tagger_rules`, `harper_tagger_order_tags`, `harper_tagger_customer_tags` exist |
| 1.5 | Deactivate and reactivate plugin | No duplicate seed tags. Seeder is guarded by `harper_tagger_seeded` option. |

---

## 2. Tag List Page

Navigate to **WooCommerce → Order & Cust. Tags**.

| # | Step | Expected |
|---|------|----------|
| 2.1 | Page loads | Table shows all 14 seed tags, no PHP errors or warnings |
| 2.2 | Badge column | Every tag shows a colour pill badge. Colour matches the tag's configured colour. |
| 2.3 | Type column | 10 rows show "Order", 4 rows show "Customer" |
| 2.4 | Rules column | Each seed tag shows "1". Tags with no rules show "0". |
| 2.5 | Edit link | Clicking Edit navigates to the edit form for that tag |
| 2.6 | Delete link | Browser confirmation dialog appears. On confirm, tag removed from list and success notice shown. On cancel, no change. |
| 2.7 | "Add Tag" button | Navigates to the add form |

**Seed tags to verify are present:**
High Value, Unconfirmed Payment, Address Mismatch, Guest Checkout, First Order, Has Coupon,
Bulk Order, Local Pickup, Free Shipping, International, VIP, Repeat Buyer, At Risk, New Customer

---

## 3. Add Tag Form

Navigate to **WooCommerce → Order & Cust. Tags → Add Tag**.

| # | Step | Expected |
|---|------|----------|
| 3.1 | Page loads | Form shows: Name, Badge Color (colour picker), Applies To (select), Custom Icon URL, Preview badge, Create Tag button |
| 3.2 | Type a name | Badge preview updates live as you type |
| 3.3 | Change colour via picker | Badge preview background updates to match chosen colour |
| 3.4 | Submit empty name | Validation error: "Tag name is required." Form repopulates with entered values. |
| 3.5 | Submit name > 100 chars | Validation error: "Tag name must be 100 characters or fewer." |
| 3.6 | Submit valid name "QA Test Order Tag", type=Order | Redirects to edit form for new tag. Success notice shown. |
| 3.7 | Navigate to list | New tag appears with correct badge colour, type "Order" |
| 3.8 | Submit duplicate name | No error — system generates a unique slug (e.g. `qa-test-order-tag-2`) |
| 3.9 | Enter a valid image URL | Badge column shows the image instead of colour pill |
| 3.10 | Enter an invalid URL | Saved as null (sanitized away). Badge falls back to colour pill. |

---

## 4. Edit Tag Form

Click **Edit** on any existing tag.

| # | Step | Expected |
|---|------|----------|
| 4.1 | Page loads | Form pre-filled with existing name, colour, type, image URL |
| 4.2 | Badge preview | Shows current name and colour |
| 4.3 | Change name, submit | Tag updated. List reflects new name. Slug unchanged (no slug re-generation on edit). |
| 4.4 | Change colour, submit | List badge reflects new colour |
| 4.5 | Change type Order → Customer | List type column updates |
| 4.6 | Clear name, submit | Validation error. Form repopulates with previously submitted values. |
| 4.7 | All Tags link | Navigates back to list without saving |

---

## 5. Rule Engine — Order Tagging

For each scenario below, create an order with the described properties and confirm the correct tag
appears. Orders can be created via **WooCommerce → Orders → Add Order** or by running the
automated seed script (see §7).

| # | Scenario | Order properties | Expected tag(s) |
|---|----------|-----------------|-----------------|
| 5.1 | High Value | Total > $500 | high-value |
| 5.2 | Unconfirmed Payment | Status = Pending or On Hold | unconfirmed-payment |
| 5.3 | Address Mismatch | Billing address ≠ Shipping address (different postcode) | address-mismatch |
| 5.4 | Guest Checkout | No customer account (customer_id = 0) | guest-checkout |
| 5.5 | First Order | Logged-in customer with no prior completed orders | first-order |
| 5.6 | Has Coupon | Coupon code applied | has-coupon |
| 5.7 | Bulk Order | 10 or more items (qty) | bulk |
| 5.8 | Local Pickup | Shipping method = local_pickup | local-pickup |
| 5.9 | Free Shipping | Shipping method = free_shipping | free-shipping |
| 5.10 | International | Shipping country not US or CA | international |
| 5.11 | Multiple rules | Total $600, guest, first order | high-value + guest-checkout + first-order |
| 5.12 | No rule match | $50 order, US shipping, logged-in repeat customer | No order tags applied |

> **Note:** Tag application is triggered on order placement. The current build evaluates rules
> when the rule engine is called directly (via CI). The order hook integration (woocommerce_checkout_order_created etc.) is a pending build item — see §8.

---

## 6. Rule Engine — Customer Tagging

| # | Scenario | Customer properties | Expected tag(s) |
|---|----------|-------------------|-----------------|
| 6.1 | New Customer | 1 completed order | new-customer |
| 6.2 | Repeat Buyer | 3+ completed orders | repeat-buyer |
| 6.3 | VIP | LTV > $1000 AND 5+ completed orders | vip + repeat-buyer |
| 6.4 | At Risk | 2+ orders, last order > 90 days ago | at-risk |
| 6.5 | No match | 2 orders, last order 30 days ago, LTV $200 | No customer tags |

---

## 7. Automated Test Suite

Run from the server before releasing any build:

```bash
# Full suite: rule engine (14 checks) + Playwright UI (11 checks)
bash /home/www/available/harperservice.tech/icon_test/ci/check-wc-tagger.sh

# Rule engine only (no browser required)
SKIP_UI=1 bash /home/www/available/harperservice.tech/icon_test/ci/check-wc-tagger.sh

# Playwright UI only
cd /home/www/available/harperservice.tech/icon_test/ui-tests
npx playwright test
```

**What automation covers:**
- All 14 seed rules fire correctly against matching fixture orders/customers
- Tag list page loads, shows 14 tags, all have colour badges
- Both Order and Customer type rows present
- All 14 seed tag names present in the grid
- Edit and Delete links on every row
- Add Tag button navigates to form
- Form has all required fields (name, colour picker, type, image URL)
- Badge preview updates live when name is typed
- Create tag → appears in list
- Delete tag → removed from list

**What automation does NOT cover (manual QA required):**
- Visual badge colour accuracy
- Colour picker interaction (canvas UI, unavailable in headless)
- Image URL fallback rendering
- Order hook integration (tag applied at checkout — not yet wired)
- Front-end/customer-facing badge display
- WooCommerce order detail page badge display (pending)
- Multi-rule tags (AND/OR logic with multiple conditions)

---

## 8. Known Pending Items (Not Yet Built)

These are out of scope for v1.0.0 QA but should be covered when built:

| Item | Notes |
|------|-------|
| Order hook wiring | Connect rule engine to `woocommerce_checkout_order_created` / `woocommerce_order_status_changed` so tags apply automatically at checkout |
| Customer hook wiring | Re-evaluate customer tags after each order |
| Rule builder UI | Admin CRUD for `harper_tagger_rules` table (add/edit/delete rules with condition builder) |
| Tag display on order detail | Show applied tags on WC order edit screen |
| Tag display on customer profile | Show applied tags on WC customer edit screen |
| OpenMage port | Separate repo, separate QA plan |
| Shopify port | Separate repo, separate QA plan |

---

## 9. New Test Environment Setup Notes

When setting up a new test environment (new server, new subdirectory), follow these steps to
avoid issues encountered during initial setup:

1. **Database naming** — use `harper_tagger_{platform}` (e.g. `harper_tagger_wc`, `harper_tagger_mage2`) so DBs are identifiable by platform at a glance.

2. **nginx rate limiting** — WP admin page loads make 15–20 parallel subrequests (scripts, styles). Production rate limit zones (`req_general 15r/s burst=30`) will cause 429 errors during Playwright runs. **Do not apply rate limit zones to test environment location blocks.** See the comment block in `/etc/nginx/conf.d/harperservice.tech.conf` above the `icon_test` blocks for the pattern to follow.

3. **Outbound mail** — add `mu-plugins/disable-mail.php` to the test WP install to suppress sendmail/msmtp errors. The plugin uses `pre_wp_mail` (WP 5.7+) to short-circuit all outbound mail silently.

4. **Plugin symlink** — symlink the plugin repo into `wp-content/plugins/` rather than copying, so the live source is always what's tested: `ln -s /path/to/wc-order-customer-tagger wp-content/plugins/wc-order-customer-tagger`

5. **dbDelta gotchas** — do not use `CREATE TABLE IF NOT EXISTS` in `dbDelta()` SQL — it parses `IF` as the table name. Use `CREATE TABLE` only. Avoid MySQL reserved words as column names (`trigger` → `rule_trigger`).
