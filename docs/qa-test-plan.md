# Harper Order & Customer Tagger — QA Test Plan v1.3

## Before You Start

**Plugin:** Harper Order & Customer Tagger v1.0.0  
**Platform:** WordPress + WooCommerce  
**Test environment:** `https://harperservice.tech/icon_test/wc`  
**Admin login:** `harperadmin` / `Harper@Test2026!`  
**WooCommerce menu path:** WooCommerce → Order & Cust. Tags

**Required browsers — run every section in all three:**

| Browser | Minimum version |
|---------|----------------|
| Google Chrome | 120+ |
| Mozilla Firefox | 121+ |
| Microsoft Edge | 120+ |

Mark each row **PASS**, **FAIL**, or **SKIP** (with reason).  
Log every FAIL with: browser, steps to reproduce, screenshot.  
Report failures to: dev@harper.agency

---

## How to Report a Failure

Include in your report:
1. Section and test number (e.g. §3 — 3.4)
2. Browser + version
3. Steps you took
4. What you expected
5. What actually happened
6. Screenshot or screen recording

---

## Section 1 — Installation & Activation

> Fresh install on a clean WordPress + WooCommerce site. If testing on the shared test env, skip 1.1–1.2 and start at 1.3.

| # | Test | Steps | Expected | Chrome | Firefox | Edge |
|---|------|-------|----------|--------|---------|------|
| 1.1 | Plugin installs without errors | Upload plugin zip via Plugins → Add New → Upload | No error messages. Plugin appears in list. | | | |
| 1.2 | Plugin activates cleanly | Click Activate | No PHP errors or white screen. WooCommerce sidebar gains "Order & Cust. Tags" menu item. | | | |
| 1.3 | Seed tags present on first load | WooCommerce → Order & Cust. Tags | Table shows exactly 14 tags. No error notice. | | | |
| 1.4 | Deactivate + reactivate | Deactivate then reactivate from Plugins list | Still 14 tags — no duplicates added. | | | |

---

## Section 2 — Tag List Page

Navigate to **WooCommerce → Order & Cust. Tags**.

| # | Test | Steps | Expected | Chrome | Firefox | Edge |
|---|------|-------|----------|--------|---------|------|
| 2.1 | Page loads without errors | Navigate to the tags page | Page loads. Heading visible. No "Fatal error" or "PHP Warning" on page. | | | |
| 2.2 | All 14 seed tags present | Scan the table | All 14 names visible: High Value, Unconfirmed Payment, Address Mismatch, Guest Checkout, First Order, Has Coupon, Bulk Order, Local Pickup, Free Shipping, International, VIP, Repeat Buyer, At Risk, New Customer | | | |
| 2.3 | Colour badges render | Look at the Badge column | Every row shows a coloured pill badge. No broken images, no empty cells. | | | |
| 2.4 | Badge colours are distinct | Visually scan badges | Badges use visibly different colours — not all the same colour. | | | |
| 2.5 | Type column correct | Check the Type column | 10 rows show "Order", 4 rows show "Customer". No blank type cells. | | | |
| 2.6 | Rules column shows counts | Check the Rules column | Each seed tag shows "1". No tag shows "0" (all have a seed rule). | | | |
| 2.7 | Edit link works | Click Edit on any tag | Navigates to edit form for that tag. URL contains `action=edit&id=`. | | | |
| 2.8 | Delete — cancel | Click Delete on any tag → click Cancel in browser dialog | Tag remains in list. No change. | | | |
| 2.9 | Delete — confirm | Click Delete on a non-seed tag (create one first if needed) → click OK | Tag removed from list. Green success notice shown. | | | |
| 2.10 | Add Tag button | Click "Add Tag" at top of page | Navigates to add form. URL contains `action=add`. | | | |

---

## Section 3 — Add Tag Form

Navigate to **WooCommerce → Order & Cust. Tags → Add Tag**.

| # | Test | Steps | Expected | Chrome | Firefox | Edge |
|---|------|-------|----------|--------|---------|------|
| 3.1 | All fields present | Load the add form | Visible: Name field, colour picker swatch button, Applies To dropdown, Custom Icon URL field, Preview badge, "Create Tag" submit button | | | |
| 3.2 | Name drives live preview | Type "My Test Tag" in Name field | Badge preview text updates to "My Test Tag" as you type — no page reload required | | | |
| 3.3 | Colour picker opens | Click the colour swatch button | Colour picker panel appears | | | |
| 3.4 | Colour change drives preview | Select a new colour in the picker | Badge preview background changes to match the selected colour | | | |
| 3.5 | Submit with empty name | Leave Name blank, click Create Tag | Stays on form. Error: "Tag name is required." Other field values preserved. | | | |
| 3.6 | Submit name > 100 characters | Paste 101-character string in Name, submit | Error: "Tag name must be 100 characters or fewer." | | | |
| 3.7 | Create valid order tag | Name = "QA Order Tag", type = Orders only, submit | Redirects to edit form. Success notice. | | | |
| 3.8 | New tag visible in list | Navigate to tag list | "QA Order Tag" appears with correct colour badge and type "Order" | | | |
| 3.9 | Create valid customer tag | Name = "QA Customer Tag", type = Customers only, submit | Tag created. List shows type "Customer". | | | |
| 3.10 | Custom icon URL — valid | Enter `https://harperservice.tech/icon_test/wc/wp-content/plugins/wc-order-customer-tagger/admin/img/` (or any valid image URL), submit | Badge column shows the image instead of colour pill | | | |
| 3.11 | Custom icon URL — blank | Leave Icon URL empty, submit | Badge column shows colour pill (no broken image) | | | |
| 3.12 | Duplicate name | Create a second tag with same name as an existing one | Tag created with a unique slug (e.g. `qa-order-tag-2`). No error. | | | |

---

## Section 4 — Edit Tag Form

Click **Edit** on any existing tag.

| # | Test | Steps | Expected | Chrome | Firefox | Edge |
|---|------|-------|----------|--------|---------|------|
| 4.1 | Form pre-filled | Open edit form | Name, colour, type, image URL fields show existing values | | | |
| 4.2 | Badge preview shows current state | Load edit form | Preview badge shows current name and colour | | | |
| 4.3 | Update name | Change name, click Update Tag | Returns to list. Updated name visible. | | | |
| 4.4 | Update colour | Change colour, click Update Tag | List badge reflects new colour | | | |
| 4.5 | Update type | Change type, click Update Tag | List type column reflects change | | | |
| 4.6 | Clear name, submit | Delete name, click Update Tag | Error: "Tag name is required." Form repopulates with the empty name (not the old value). | | | |
| 4.7 | All Tags back link | Click "← All Tags" | Returns to list without saving changes | | | |

---

## Section 5 — Rule Engine: Order Tags

> These tests verify the correct tags are applied to orders. The rule engine runs at order placement.
> Until the order hook is wired (§8 pending item), trigger manually via WP-CLI:
>
> ```bash
> wp eval-file /home/www/available/harperservice.tech/icon_test/scripts/verify-wc-fixtures.php \
>   --path=/home/www/available/harperservice.tech/icon_test/wc
> ```
>
> For UI verification, check **WooCommerce → Orders** and open each order — the applied tags should be visible (display on order screen is a §8 pending item; for now, verify via the CLI output or DB).

| # | Scenario | Order properties | Tags that MUST fire | Tags that must NOT fire | CLI result |
|---|----------|-----------------|-------------------|------------------------|------------|
| 5.1 | High Value | Total > $500, US shipping, logged-in | high-value | — | |
| 5.2 | Unconfirmed Payment | Status = pending | unconfirmed-payment | — | |
| 5.3 | Unconfirmed Payment — on-hold | Status = on-hold | unconfirmed-payment | — | |
| 5.4 | Address Mismatch | Billing postcode ≠ Shipping postcode | address-mismatch | — | |
| 5.5 | Guest Checkout | No WP account (customer_id = 0) | guest-checkout | — | |
| 5.6 | First Order | Logged-in customer, no prior completed orders | first-order | — | |
| 5.7 | NOT First Order | Logged-in customer with 1+ prior completed orders | — | first-order | |
| 5.8 | Has Coupon | Valid coupon applied | has-coupon | — | |
| 5.9 | Bulk Order | 10+ items (total quantity) | bulk | — | |
| 5.10 | Bulk Order boundary | 9 items | — | bulk | |
| 5.11 | Local Pickup | Shipping method = local_pickup | local-pickup | — | |
| 5.12 | Free Shipping | Shipping method = free_shipping | free-shipping | — | |
| 5.13 | International — DE | Shipping country = DE | international | — | |
| 5.14 | International — GB | Shipping country = GB | international | — | |
| 5.15 | NOT International — US | Shipping country = US | — | international | |
| 5.16 | NOT International — CA | Shipping country = CA | — | international | |
| 5.17 | Multi-rule | $600 order, guest, US, first purchase | high-value + guest-checkout + first-order | — | |
| 5.18 | No match | $50, US, repeat logged-in customer | — | all order tags | |

---

## Section 6 — Rule Engine: Customer Tags

| # | Scenario | Customer state | Tags that MUST fire | Tags that must NOT fire | CLI result |
|---|----------|---------------|-------------------|------------------------|------------|
| 6.1 | New Customer | Exactly 1 completed order | new-customer | repeat-buyer, vip, at-risk | |
| 6.2 | Repeat Buyer | 3 completed orders | repeat-buyer | new-customer, vip | |
| 6.3 | Repeat Buyer boundary | 2 completed orders | — | repeat-buyer | |
| 6.4 | VIP | LTV > $1000 AND 5+ completed orders | vip + repeat-buyer | new-customer | |
| 6.5 | VIP — order count short | LTV > $1000 but only 4 orders | — | vip | |
| 6.6 | VIP — LTV short | 5+ orders but LTV ≤ $1000 | — | vip | |
| 6.7 | At Risk | 2+ orders, last order > 90 days ago | at-risk | — | |
| 6.8 | NOT At Risk — recent | 2+ orders, last order 30 days ago | — | at-risk | |
| 6.9 | No match | 2 orders, recent, LTV $200 | — | all customer tags | |

---

## Section 7 — Orders List Grid (Tags Column)

> WooCommerce → Orders. Verify the Tags column appears and displays correctly.

| # | Test | Chrome | Firefox | Edge |
|---|------|--------|---------|------|
| 7.1 | Tags column appears in the orders list between Status and other columns | | | |
| 7.2 | Order with one tag: badge pill shows correct tag name and colour | | | |
| 7.3 | Order with multiple tags: all badges visible, no overflow or truncation | | | |
| 7.4 | Order with an icon tag: image displays at correct size (24×24), not oversized | | | |
| 7.5 | Order with no tags: shows a dash (—), not blank or error | | | |
| 7.6 | Column is visible in both HPOS mode and legacy post-based orders | | | |
| 7.7 | Column width does not cause other columns to overflow the table | | | |

---

## Section 8 — Customers List Grid (Tags Column)

> WordPress Admin → Users. Verify the Tags column appears and displays correctly.

| # | Test | Chrome | Firefox | Edge |
|---|------|--------|---------|------|
| 8.1 | Tags column appears in the users/customers list | | | |
| 8.2 | Customer with one tag: badge pill shows correct tag name and colour | | | |
| 8.3 | Customer with multiple tags: all badges visible | | | |
| 8.4 | Customer with an icon tag: image displays correctly | | | |
| 8.5 | Customer with no tags: shows a dash (—) | | | |
| 8.6 | Tags are correct after a new order fires and tags the customer | | | |

---

## Section 9 — Order Detail Screen (Tags Meta Box)

> WooCommerce → Orders → click any order. Verify the Tags meta box on the right sidebar.

| # | Test | Chrome | Firefox | Edge |
|---|------|--------|---------|------|
| 9.1 | "Order Tags" meta box appears in the right sidebar | | | |
| 9.2 | Badge pills show correct name and colour for each applied tag | | | |
| 9.3 | Icon tags display the image, not a badge pill | | | |
| 9.4 | "No tags applied." shown when order has no tags | | | |

---

## Section 10 — Customer Profile Screen (Tags Section)

> WordPress Admin → Users → click any customer. Verify the Tags section.

| # | Test | Chrome | Firefox | Edge |
|---|------|--------|---------|------|
| 10.1 | "Customer Tags" section appears on the user profile page | | | |
| 10.2 | Badge pills show correct name and colour | | | |
| 10.3 | Icon tags display the image | | | |
| 10.4 | "No customer tags applied." shown when customer has no tags | | | |

---

## Section 11 — Cross-Browser Visual Checks

Run these with a real logged-in browser session, not CLI.

| # | Test | Chrome | Firefox | Edge |
|---|------|--------|---------|------|
| 7.1 | Badge pills render with correct background colour (not transparent, not black) | | | |
| 7.2 | Badge text is readable (white or dark text, sufficient contrast) | | | |
| 7.3 | Colour picker opens and closes correctly | | | |
| 7.4 | Colour picker swatch reflects selected colour after close | | | |
| 7.5 | Live badge preview updates while typing (no lag > 1 second) | | | |
| 7.6 | Table layout is not broken (columns aligned, no overflow) | | | |
| 7.7 | Admin notices (success/error) appear and are dismissible | | | |
| 7.8 | "Delete this tag?" confirmation dialog appears before delete | | | |

---

## Section 12 — Automated Test Suite (Run Once, Any Browser)

These are already automated. QA should verify the suite passes clean on the test environment — this confirms no regressions were introduced since the last dev commit.

```bash
bash /home/www/available/harperservice.tech/icon_test/ci/check-wc-tagger.sh
```

Expected output (last two lines):
```
 Rule-engine: PASS
 UI tests:    PASS
 RESULT: PASS
```

| # | Check | Result |
|---|-------|--------|
| 12.1 | CI script exits 0 | |
| 12.2 | All 234 unit tests pass, 0 failed | |
| 12.3 | Playwright UI: 11 passed, 0 failed | |

---

## Section 13 — Out of Scope / Future

| Item | Status |
|------|--------|
| OpenMage (Magento 1) port | Separate repo |
| Shopify port | Hosted app, not self-install |

---

## Sign-Off

| Section | Tester | Date | Chrome | Firefox | Edge | Notes |
|---------|--------|------|--------|---------|------|-------|
| 1 — Installation | | | | | | |
| 2 — Tag list | | | | | | |
| 3 — Add tag | | | | | | |
| 4 — Edit tag | | | | | | |
| 5 — Order rules | | | | | | |
| 6 — Customer rules | | | | | | |
| 7 — Orders list column | | | | | | |
| 8 — Customers list column | | | | | | |
| 9 — Order detail meta box | | | | | | |
| 10 — Customer profile section | | | | | | |
| 11 — Visual / cross-browser | | | | | | |
| 12 — Automated suite | | | N/A | N/A | N/A | |
