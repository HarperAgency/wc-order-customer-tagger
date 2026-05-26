<?php
/**
 * ContextBuilder — adapts WC_Order / WC_Customer into a plain context array
 * for the pure-PHP RuleEngine. The ONLY class allowed to call WC functions.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

if (!defined('ABSPATH')) exit;

class ContextBuilder
{
    /**
     * Build a context array from a WC_Order.
     *
     * Keys produced:
     *   order_total       float
     *   item_count        int      sum of qty across all line items
     *   shipping_method   string   first shipping method id ("flat_rate:1")
     *   payment_method    string   e.g. "stripe", "cod", "cheque"
     *   payment_status    string   WC order status: pending, on-hold, processing, etc.
     *   shipping_country  string   2-letter ISO
     *   billing_country   string   2-letter ISO
     *   is_guest          bool     true when placed without a WP account
     *   is_first_order    bool     true when customer has no previous completed orders
     *   has_coupon        bool     true when one or more coupons are applied
     *   address_mismatch  bool     true when billing address ≠ shipping address
     *   items             array    [{sku, categories[]}]
     *
     * @return array<string, mixed>
     */
    public function fromOrder(\WC_Order $order): array
    {
        // ── Line items ────────────────────────────────────────────────────────
        $items     = [];
        $itemCount = 0;

        foreach ($order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $qty        = (int) $item->get_quantity();
            $itemCount += $qty;

            $product    = $item->get_product();
            $sku        = $product ? (string) $product->get_sku() : '';
            $categories = [];

            if ($product) {
                $terms = get_the_terms($product->get_id(), 'product_cat');
                if (is_array($terms)) {
                    foreach ($terms as $term) {
                        $categories[] = $term->slug;
                    }
                }
            }

            $items[] = ['sku' => $sku, 'categories' => $categories];
        }

        // ── Shipping method ───────────────────────────────────────────────────
        $shippingMethod = '';
        foreach ($order->get_items('shipping') as $shippingItem) {
            /** @var \WC_Order_Item_Shipping $shippingItem */
            $shippingMethod = $shippingItem->get_method_id();
            if ($shippingItem->get_instance_id()) {
                $shippingMethod .= ':' . $shippingItem->get_instance_id();
            }
            break;
        }

        // ── Boolean signals ───────────────────────────────────────────────────
        $customerId     = (int) $order->get_customer_id();
        $isGuest        = $customerId === 0;
        $isFirstOrder   = $this->isFirstOrder($customerId, (int) $order->get_id());
        $hasCoupon      = count($order->get_coupon_codes()) > 0;
        $addressMismatch = $this->addressesDiffer($order);

        return [
            'order_total'      => (float) $order->get_total(),
            'item_count'       => $itemCount,
            'shipping_method'  => $shippingMethod,
            'payment_method'   => (string) $order->get_payment_method(),
            'payment_status'   => (string) $order->get_status(),
            'shipping_country' => (string) $order->get_shipping_country(),
            'billing_country'  => (string) $order->get_billing_country(),
            'is_guest'         => $isGuest,
            'is_first_order'   => $isFirstOrder,
            'has_coupon'       => $hasCoupon,
            'address_mismatch' => $addressMismatch,
            'items'            => $items,
        ];
    }

    /**
     * Build a context array from a WC_Customer.
     *
     * Keys produced:
     *   customer_ltv               float
     *   customer_order_count       int
     *   customer_account_age_days  int   days since account registered
     *   days_since_last_order      int   days since most recent order (0 if none)
     *   customer_tags              string[]
     *
     * @return array<string, mixed>
     */
    public function fromCustomer(\WC_Customer $customer): array
    {
        $userId   = (int) $customer->get_id();
        $tagNames = [];

        if ($userId > 0) {
            $terms = wp_get_object_terms($userId, 'customer_tag', ['fields' => 'names']);
            if (is_array($terms)) {
                $tagNames = $terms;
            }
        }

        $accountAgeDays   = $this->accountAgeDays($customer);
        $daysSinceLastOrder = $this->daysSinceLastOrder($userId);

        return [
            'customer_ltv'              => (float) $customer->get_total_spent(),
            'customer_order_count'      => (int)   $customer->get_order_count(),
            'customer_account_age_days' => $accountAgeDays,
            'days_since_last_order'     => $daysSinceLastOrder,
            'customer_tags'             => $tagNames,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * True when this is the customer's first completed/processing order.
     * Guests always return true (no history to check).
     */
    private function isFirstOrder(int $customerId, int $currentOrderId): bool
    {
        if ($customerId === 0) {
            return true;
        }

        $orders = wc_get_orders([
            'customer_id' => $customerId,
            'status'      => ['completed', 'processing'],
            'limit'       => 2,
            'exclude'     => [$currentOrderId],
            'return'      => 'ids',
        ]);

        return count($orders) === 0;
    }

    /**
     * True when billing address fields differ from shipping address fields.
     * Compares country, state, postcode, and address_1.
     */
    private function addressesDiffer(\WC_Order $order): bool
    {
        return
            $order->get_billing_country()   !== $order->get_shipping_country()   ||
            $order->get_billing_state()     !== $order->get_shipping_state()     ||
            $order->get_billing_postcode()  !== $order->get_shipping_postcode()  ||
            $order->get_billing_address_1() !== $order->get_shipping_address_1();
    }

    /** Days since the WP user account was registered. Returns 0 for guests. */
    private function accountAgeDays(\WC_Customer $customer): int
    {
        $userId = (int) $customer->get_id();
        if ($userId === 0) {
            return 0;
        }

        $user = get_userdata($userId);
        if (!$user) {
            return 0;
        }

        $registered = strtotime($user->user_registered);
        return (int) floor((time() - $registered) / 86400);
    }

    /** Days since the customer's most recent order. Returns 0 if no orders. */
    private function daysSinceLastOrder(int $customerId): int
    {
        if ($customerId === 0) {
            return 0;
        }

        $orders = wc_get_orders([
            'customer_id' => $customerId,
            'status'      => ['completed', 'processing'],
            'limit'       => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'return'      => 'objects',
        ]);

        if (empty($orders)) {
            return 0;
        }

        $lastDate = $orders[0]->get_date_created();
        if (!$lastDate) {
            return 0;
        }

        return (int) floor((time() - $lastDate->getTimestamp()) / 86400);
    }
}
