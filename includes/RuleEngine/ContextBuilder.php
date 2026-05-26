<?php
/**
 * ContextBuilder — adapts WC_Order / WC_Customer into a plain context array
 * suitable for the pure-PHP RuleEngine.
 *
 * This is the ONLY class in the RuleEngine namespace that is allowed to call
 * WooCommerce functions or methods.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

class ContextBuilder
{
    /**
     * Build a context array from a WC_Order.
     *
     * Keys produced:
     *   order_total      float
     *   item_count       int   (sum of qty across all line items)
     *   shipping_method  string (first shipping method id, e.g. "flat_rate:1")
     *   payment_method   string
     *   shipping_country string
     *   billing_country  string
     *   items            array<int, array{sku: string, categories: string[]}>
     *
     * @return array<string, mixed>
     */
    public function fromOrder(\WC_Order $order): array
    {
        // Collect line items
        $items     = [];
        $itemCount = 0;

        /** @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $qty     = (int) $item->get_quantity();
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

            $items[] = [
                'sku'        => $sku,
                'categories' => $categories,
            ];
        }

        // First shipping method
        $shippingMethod = '';
        foreach ($order->get_items('shipping') as $shippingItem) {
            /** @var \WC_Order_Item_Shipping $shippingItem */
            $shippingMethod = $shippingItem->get_method_id();
            if ($shippingItem->get_instance_id()) {
                $shippingMethod .= ':' . $shippingItem->get_instance_id();
            }
            break; // only first
        }

        return [
            'order_total'      => (float) $order->get_total(),
            'item_count'       => $itemCount,
            'shipping_method'  => $shippingMethod,
            'payment_method'   => (string) $order->get_payment_method(),
            'shipping_country' => (string) $order->get_shipping_country(),
            'billing_country'  => (string) $order->get_billing_country(),
            'items'            => $items,
        ];
    }

    /**
     * Build a context array from a WC_Customer.
     *
     * Keys produced:
     *   customer_ltv          float  (lifetime total spent)
     *   customer_order_count  int
     *   customer_tags         string[] (tag names/slugs)
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

        return [
            'customer_ltv'         => (float) $customer->get_total_spent(),
            'customer_order_count' => (int) $customer->get_order_count(),
            'customer_tags'        => $tagNames,
        ];
    }
}
