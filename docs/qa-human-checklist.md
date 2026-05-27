# Harper Order & Customer Tagger — Human QA Checklist v1.3

**Test site:** https://harperservice.tech/icon_test/wc/wp-admin  
**Login:** `harperadmin` / `Harper@Test2026!`  
**Plugin version:** 1.3.0  
**Date tested:** _______________  
**Tester:** _______________

---

## Before You Start

- [ ] Log into the test site admin above
- [ ] Confirm plugin is active: **Plugins → Installed Plugins** → Harper Order & Customer Tagger = Active
- [ ] Confirm WooCommerce is active on the same screen

---

## 1 — Orders List: Tags Column

Go to: **WooCommerce → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 1.1 | A "Tags" column is visible in the orders grid | | | |
| 1.2 | The column appears between Status and other columns | | | |
| 1.3 | Orders with no tags show a dash (—), not blank or error | | | |
| 1.4 | Hovering a Tags cell highlights it (dashed outline, light background) | | | |
| 1.5 | Clicking a Tags cell opens the popover panel | | | |
| 1.6 | Popover shows the order number in the header | | | |
| 1.7 | Popover shows "Applied" section (dash if none) | | | |
| 1.8 | Popover shows "Add tag" section with all 14 seed tags as coloured badges | | | |
| 1.9 | Clicking a tag badge in "Add tag" applies it immediately | | | |
| 1.10 | Applied tag appears in the cell in the grid (no page reload) | | | |
| 1.11 | Applied tag moves from "Add tag" to "Applied" section in the popover | | | |
| 1.12 | Clicking × on an applied tag removes it immediately | | | |
| 1.13 | Removed tag disappears from the cell and moves back to "Add tag" | | | |
| 1.14 | Add multiple tags to one order — all show in the cell | | | |
| 1.15 | Clicking outside the popover closes it | | | |
| 1.16 | Pressing ESC closes the popover | | | |
| 1.17 | Clicking the same cell again (while open) closes the popover | | | |
| 1.18 | Opening popover on a different row closes the previous one | | | |

---

## 2 — Order Detail: Tags Meta Box

Go to: **WooCommerce → Orders → click any order**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 2.1 | "Order Tags" meta box is visible in the right sidebar | | | |
| 2.2 | Tags applied via the grid popover appear here immediately after page refresh | | | |
| 2.3 | Tags with colour show as coloured badge pills | | | |
| 2.4 | "No tags applied." shown when order has no tags | | | |

---

## 3 — Customers List: Tags Column

Go to: **WordPress Admin → Users**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 3.1 | A "Tags" column is visible in the users list | | | |
| 3.2 | Customers with no tags show a dash (—) | | | |
| 3.3 | Hovering a Tags cell highlights it | | | |
| 3.4 | Clicking a Tags cell opens the popover for that customer | | | |
| 3.5 | Popover header shows "Customer #[ID] — Tags" | | | |
| 3.6 | Click **VIP Tester** row — add the "VIP" tag via popover | | | |
| 3.7 | VIP badge appears in the cell immediately | | | |
| 3.8 | Remove the VIP tag — cell reverts to dash | | | |
| 3.9 | Add multiple tags to one customer — all show in cell | | | |

---

## 4 — Customer Profile: Tags Section

Go to: **WordPress Admin → Users → click VIP Tester**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 4.1 | "Customer Tags" section appears on the profile page | | | |
| 4.2 | Tags applied via the grid in §3 appear here after page refresh | | | |
| 4.3 | "No customer tags applied." shown when no tags | | | |

---

## 5 — Rule Engine: Auto-Tagging

Go to: **WooCommerce → Order & Cust. Tags → Rules**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 5.1 | Rules list page loads without error | | | |
| 5.2 | Click "Add Rule" — rule form loads | | | |
| 5.3 | Create a rule: Label = "High Value Orders", Tag = "High Value", Condition = Order Total > 100 | | | |
| 5.4 | Save rule — it appears in the rules list | | | |
| 5.5 | Go to **WooCommerce → Orders** and change any order status to trigger re-evaluation | | | |
| 5.6 | Orders over $100 now show the "High Value" tag in the grid | | | |

---

## 6 — Icon Tags (Image instead of colour badge)

Go to: **WooCommerce → Order & Cust. Tags → Tags → Add New Tag**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 6.1 | Create a tag with an image URL (e.g. any PNG from the internet) | | | |
| 6.2 | In the Orders grid popover "Add tag" section — icon tag shows as image, not badge | | | |
| 6.3 | After applying — icon image appears in the grid cell | | | |
| 6.4 | On the order detail meta box — icon image shows correctly | | | |
| 6.5 | Icon image is not oversized (max ~22×22px) | | | |

---

## 7 — Filter by Tag (NEW in v1.3)

### 7a — Orders grid filter

Go to: **WooCommerce → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 7a.1 | A "Filter by tag…" dropdown appears above the orders table | | | |
| 7a.2 | Dropdown lists all 14 seed tags | | | |
| 7a.3 | Selecting a tag and clicking Filter shows only orders tagged with it | | | |
| 7a.4 | The dropdown stays selected after the page reloads (selection preserved) | | | |
| 7a.5 | Selecting the blank "Filter by tag…" option and clicking Filter clears the filter | | | |
| 7a.6 | Filtered results page shows no PHP errors or warnings | | | |

### 7b — Customers / Users grid filter

Go to: **WordPress Admin → Users**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 7b.1 | A "Filter by tag…" dropdown appears above the users table | | | |
| 7b.2 | Dropdown lists customer tags only (not order-only tags) | | | |
| 7b.3 | Selecting a tag filters the users list to only tagged customers | | | |
| 7b.4 | Selection preserved after reload | | | |
| 7b.5 | Filtered results page shows no PHP errors or warnings | | | |

---

## 8 — Bulk Re-run Tagger Rules (NEW in v1.3)

Go to: **WooCommerce → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 8.1 | "Re-run Tagger Rules" option appears in the Bulk Actions dropdown | | | |
| 8.2 | Check one or more orders, choose "Re-run Tagger Rules", click Apply | | | |
| 8.3 | Page reloads and shows a green success notice: "Tagger rules re-run on N order(s)" | | | |
| 8.4 | Tags on the selected orders reflect the current rules after re-run | | | |
| 8.5 | Page shows no PHP errors or warnings | | | |

---

## 10 — Cross-Browser Spot Check

Repeat tests **1.5 → 1.13** in each browser:

| Browser | Popover opens | Add works | Remove works | Cell updates | Closes on outside click |
|---|---|---|---|---|---|
| Chrome 120+ | | | | | |
| Firefox 121+ | | | | | |
| Edge 120+ | | | | | |

---

## 11 — Edge Cases

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 8.1 | Apply all 14 tags to one order — "All tags applied." appears in "Add tag" section | | | |
| 8.2 | Remove all tags — "Applied" section shows dash (—) | | | |
| 8.3 | Popover does not break the table layout (no columns shift) | | | |
| 8.4 | Popover near the right edge of the screen positions correctly (flips left) | | | |
| 8.5 | No JS errors in browser console during normal operation | | | |
| 8.6 | No PHP errors/warnings in **WP Admin → Tools → Site Health → Info** | | | |

---

## 12 — Uninstall (Do Last — Destructive)

> Only run this if you want to verify clean removal. You will need to re-activate and re-seed after.

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 9.1 | Deactivate the plugin from Plugins screen | | | |
| 9.2 | Click "Delete" on the plugin | | | |
| 9.3 | Confirm all 4 harper_tagger_* tables are gone from the database | | | |
| 9.4 | No plugin menu items remain in WooCommerce menu | | | |

---

## Sign-Off

| Section | Tester | Date | Result | Notes |
|---|---|---|---|---|
| 1 — Orders grid column + popover | | | PASS / FAIL | |
| 2 — Order detail meta box | | | PASS / FAIL | |
| 3 — Customers grid column + popover | | | PASS / FAIL | |
| 4 — Customer profile section | | | PASS / FAIL | |
| 5 — Rule engine auto-tagging | | | PASS / FAIL | |
| 6 — Icon tags | | | PASS / FAIL | |
| 7 — Filter by tag (orders + customers) | | | PASS / FAIL | |
| 8 — Bulk re-run rules | | | PASS / FAIL | |
| 9 — Cross-browser | | | PASS / FAIL | |
| 10 — Edge cases | | | PASS / FAIL | |
| 11 — Uninstall | | | PASS / FAIL / SKIP | |

**Overall result:** PASS / FAIL  
**Sign-off:** _______________ Date: _______________

---

## Reporting a Failure

Include in your report:
1. Section and test number (e.g. §1 — 1.9)
2. Browser + version
3. Steps you took
4. What you expected vs what happened
5. Screenshot or screen recording
6. Any console errors (F12 → Console)

Send to: **dev@harper.agency**
