# Rules Reference

## How Rules Work

A rule consists of:

1. **A tag** to apply when the rule matches
2. **One or more conditions** that define when it matches
3. **A match mode** - ALL conditions must match, or ANY one is enough

Rules are evaluated automatically when:
- An order is placed (on checkout completion)
- An order status changes (any transition)

You can also trigger rule evaluation manually using the **Re-run tagging rules** bulk action on the Orders screen.

---

## Conditions

### Order conditions

These evaluate the current order.

#### Order Total

Compares the order's total (after discounts, before tax where applicable).

| Operator | Example |
|----------|---------|
| Greater than | Order total > 500 |
| Less than | Order total < 50 |
| Equals | Order total = 100 |

#### Item Count

Number of line items in the order.

| Operator | Example |
|----------|---------|
| Greater than | Item count > 10 |
| Less than | Item count < 2 |
| Equals | Item count = 1 |

#### Shipping Method

Matches against the shipping method ID or label.

| Operator | Example |
|----------|---------|
| Contains | Shipping method contains "express" |
| Equals | Shipping method = "flat_rate:3" |

#### Payment Method

Matches against the payment method ID (e.g. `stripe`, `paypal`, `cod`).

| Operator | Example |
|----------|---------|
| Contains | Payment method contains "stripe" |
| Equals | Payment method = "cod" |

#### Order Status

Matches the current order status.

| Operator | Example |
|----------|---------|
| Equals | Order status = "completed" |
| Is not | Order status is not "refunded" |

#### Coupon Used

Whether any coupon code was applied to the order.

| Value |
|-------|
| Yes - coupon was used |
| No - no coupon |

#### Address Mismatch

Whether the billing and shipping addresses differ. Useful for flagging potential gifts or fraud indicators.

| Value |
|-------|
| Yes - billing differs from shipping |
| No - addresses match |

#### Shipping Country

The country from the shipping address (ISO 3166-1 alpha-2 code, e.g. `US`, `CA`, `GB`).

| Operator | Example |
|----------|---------|
| Is | Shipping country = "CA" |
| Is not | Shipping country is not "US" |

#### Billing Country

Same as Shipping Country but matched against the billing address.

---

### Customer conditions

These evaluate the customer associated with the order. Guest orders only trigger order-level tags; customer tags require a logged-in customer.

#### Customer Type

| Value |
|-------|
| Guest - no WordPress account |
| Registered - has a WordPress/WooCommerce account |

#### First Order

Whether this is the customer's first completed order.

| Value |
|-------|
| Yes - first order |
| No - returning customer |

#### Customer Lifetime Value

Total spend across all completed orders for this customer.

| Operator | Example |
|----------|---------|
| Greater than | Lifetime value > 1000 |
| Less than | Lifetime value < 100 |

#### Customer Order Count

Number of completed orders this customer has placed.

| Operator | Example |
|----------|---------|
| Greater than | Order count > 10 |
| Less than | Order count < 3 |
| Equals | Order count = 1 |

#### Days Since Last Order

Number of days since this customer's previous completed order.

| Operator | Example |
|----------|---------|
| Greater than | Days since last order > 180 |
| Less than | Days since last order < 30 |

#### Customer Account Age

Number of days since the customer's WordPress account was created.

| Operator | Example |
|----------|---------|
| Greater than | Account age > 365 |
| Less than | Account age < 7 |

---

## Match Modes

| Mode | Behaviour |
|------|-----------|
| **ALL** | Every condition in the rule must be true for the tag to apply |
| **ANY** | At least one condition must be true for the tag to apply |

---

## Rule Evaluation Order

Rules are evaluated in the order they appear in the admin. If multiple rules assign the same tag, the tag is applied only once - no duplicates.

---

## Removing Tags

Tags applied by a rule are not automatically removed if an order later fails the rule conditions (for example, if a refund reduces the order total below the threshold). Tags are sticky - once applied, they remain until manually removed via the inline editor.

If you need rules that reflect current state rather than history, use the bulk re-run action after updating the order.

---

## Starter Rules Reference

The 14 rules installed on activation:

| Rule | Tag | Condition |
|------|-----|-----------|
| High Value | High Value (order) | Order total > $500 |
| Large Order | Large Order (order) | Item count > 5 |
| First Order | First Order (order) | First order = yes |
| Guest Checkout | Guest Checkout (order) | Customer type = guest |
| Coupon Used | Coupon Used (order) | Coupon used = yes |
| International Shipping | International (order) | Shipping country is not [your base country] |
| Address Mismatch | Address Mismatch (order) | Address mismatch = yes |
| Card Payment | Card Payment (order) | Payment method contains "stripe" OR "square" |
| New Customer | New Customer (customer) | First order = yes, registered |
| Repeat Buyer | Repeat Buyer (customer) | Order count > 1 |
| Loyal Customer | Loyal Customer (customer) | Order count > 5 |
| High LTV | High LTV (customer) | Lifetime value > $1000 |
| Local Customer | Local Customer (customer) | Billing country = [your base country] |
| Long Inactive | Long Inactive (customer) | Days since last order > 180 |

All starter rules can be edited or deleted. They are created once on first activation and are not restored on plugin update.
