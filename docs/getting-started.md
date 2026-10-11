# Getting Started

This guide walks you through the plugin from first activation to your first custom rule.

---

## Step 1: Check your starter tags

After activation, go to **WooCommerce → Order & Customer Tagger**.

You will see 14 tags created automatically:

| Tag | Type | Default colour |
|-----|------|---------------|
| High Value | Order | Green |
| First Order | Order | Blue |
| Repeat Buyer | Customer | Purple |
| Guest Checkout | Order | Grey |
| Large Order | Order | Orange |
| International | Order | Teal |
| Coupon Used | Order | Yellow |
| Card Payment | Order | Dark blue |
| Address Mismatch | Order | Red |
| Loyal Customer | Customer | Gold |
| New Customer | Customer | Light blue |
| Local Customer | Customer | Green |
| High LTV | Customer | Purple |
| Long Inactive | Customer | Red |

These come with corresponding starter rules already active. You can edit, delete, or add to them at any time.

---

## Step 2: Place a test order

Place a test order through your store that meets one of the starter conditions - for example, an order with a total above $500.

After checkout, go to **WooCommerce → Orders** and open the order. You should see the `High Value` tag badge on the order. If the customer was logged in, check their profile - customer rules run at the same time.

---

## Step 3: View tags in the order list

Go to **WooCommerce → Orders**. Each order row shows its applied tags as colour-coded badges in the Tags column.

Use the **Filter by tag** dropdown at the top of the list to show only orders with a specific tag.

---

## Step 4: Edit a tag inline

Click a tag badge in the order list (or the edit icon on the customer list) to open the inline tag editor. From here you can:

- Add any existing tag to the order or customer
- Remove a tag

Changes are saved immediately - no page reload required.

---

## Step 5: Create a custom rule

1. Go to **WooCommerce → Order & Customer Tagger → Rules**
2. Click **Add Rule**
3. Choose a tag to apply (or create a new tag first)
4. Add one or more conditions - for example:
   - **Order total** is greater than **200**
   - **Customer type** is **guest**
5. Choose whether all conditions must match (AND) or any one is enough (OR)
6. Save the rule

The rule runs automatically on every new order and every order status change from this point forward.

---

## Step 6: Tag existing orders retroactively

New rules only run on orders created or updated after the rule is saved. To apply rules to historical orders:

1. Go to **WooCommerce → Orders**
2. Select the orders you want to re-process (or select all)
3. Choose **Re-run tagging rules** from the Bulk Actions dropdown
4. Click **Apply**

An admin notice will confirm how many orders were processed.

---

## Tips

- **Order tags** track what happened in the transaction. **Customer tags** track who the buyer is. Use them for different purposes - order tags for fulfilment and operations, customer tags for segmentation and marketing.
- Colour-code tags consistently - for example, red for anything requiring attention, green for high-value or VIP.
- The bulk re-run is safe to run multiple times. Tags are not duplicated if a rule has already been applied.
