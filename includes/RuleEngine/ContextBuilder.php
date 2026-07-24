<?php
/**
 * ContextBuilder — adapts WC_Order / WC_Customer into a plain context array
 * for the pure-PHP RuleEngine. The ONLY class allowed to call WC functions.
 *
 * @package ClarityMomentum\WCTagger\RuleEngine
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\RuleEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts WooCommerce objects into plain context arrays for the rule engine.
 */
class ContextBuilder {

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
	 *   address_mismatch  bool     true when billing address differs from shipping address
	 *   items             array    [{sku, categories[]}]
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return array<string, mixed>
	 */
	public function from_order( \WC_Order $order ): array {
		// ── Line items ────────────────────────────────────────────────────────
		$items      = array();
		$item_count = 0;

		foreach ( $order->get_items() as $item ) {
			if ( ! ( $item instanceof \WC_Order_Item_Product ) ) {
				continue;
			}
			$qty         = (int) $item->get_quantity();
			$item_count += $qty;

			$product    = $item->get_product();
			$sku        = $product ? (string) $product->get_sku() : '';
			$categories = array();

			if ( $product ) {
				$terms = get_the_terms( $product->get_id(), 'product_cat' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						$categories[] = $term->slug;
					}
				}
			}

			$items[] = array(
				'sku'        => $sku,
				'categories' => $categories,
			);
		}

		// ── Shipping method ───────────────────────────────────────────────────
		$shipping_method = '';
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			if ( ! ( $shipping_item instanceof \WC_Order_Item_Shipping ) ) {
				continue;
			}
			$shipping_method = $shipping_item->get_method_id();
			if ( $shipping_item->get_instance_id() ) {
				$shipping_method .= ':' . $shipping_item->get_instance_id();
			}
			break;
		}

		// ── Boolean signals ───────────────────────────────────────────────────
		$customer_id      = (int) $order->get_customer_id();
		$is_guest         = 0 === $customer_id;
		$is_first_order   = $this->is_first_order( $customer_id, (int) $order->get_id() );
		$has_coupon       = count( $order->get_coupon_codes() ) > 0;
		$address_mismatch = $this->addresses_differ( $order );

		return array(
			'order_total'      => (float) $order->get_total(),
			'item_count'       => $item_count,
			'shipping_method'  => $shipping_method,
			'payment_method'   => (string) $order->get_payment_method(),
			'payment_status'   => (string) $order->get_status(),
			'shipping_country' => (string) $order->get_shipping_country(),
			'billing_country'  => (string) $order->get_billing_country(),
			'is_guest'         => $is_guest,
			'is_first_order'   => $is_first_order,
			'has_coupon'       => $has_coupon,
			'address_mismatch' => $address_mismatch,
			'items'            => $items,
		);
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
	 * @param \WC_Customer $customer WooCommerce customer object.
	 * @return array<string, mixed>
	 */
	public function from_customer( \WC_Customer $customer ): array {
		$user_id   = (int) $customer->get_id();
		$tag_names = array();

		if ( $user_id > 0 ) {
			$terms = wp_get_object_terms( $user_id, 'customer_tag', array( 'fields' => 'names' ) );
			if ( is_array( $terms ) ) {
				$tag_names = $terms;
			}
		}

		$account_age_days      = $this->account_age_days( $customer );
		$days_since_last_order = $this->days_since_last_order( $user_id );

		return array(
			'customer_ltv'              => (float) $customer->get_total_spent(),
			'customer_order_count'      => (int) $customer->get_order_count(),
			'customer_account_age_days' => $account_age_days,
			'days_since_last_order'     => $days_since_last_order,
			'customer_tags'             => $tag_names,
		);
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * True when this is the customer's first completed/processing order.
	 * Guests always return true (no history to check).
	 *
	 * @param int $customer_id      WP user ID (0 for guests).
	 * @param int $current_order_id The current order to exclude from the count.
	 * @return bool
	 */
	private function is_first_order( int $customer_id, int $current_order_id ): bool {
		if ( 0 === $customer_id ) {
			return true;
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- exclude is necessary to check first order status
		$orders = wc_get_orders(
			array(
				'customer_id' => $customer_id,
				'status'      => array( 'completed', 'processing' ),
				'limit'       => 2,
				'exclude'     => array( $current_order_id ),
				'return'      => 'ids',
			)
		);

		return 0 === count( $orders );
	}

	/**
	 * True when billing address fields differ from shipping address fields.
	 * Compares country, state, postcode, and address_1.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return bool
	 */
	private function addresses_differ( \WC_Order $order ): bool {
		return $order->get_billing_country() !== $order->get_shipping_country() ||
			$order->get_billing_state() !== $order->get_shipping_state() ||
			$order->get_billing_postcode() !== $order->get_shipping_postcode() ||
			$order->get_billing_address_1() !== $order->get_shipping_address_1();
	}

	/**
	 * Days since the WP user account was registered. Returns 0 for guests.
	 *
	 * @param \WC_Customer $customer WooCommerce customer object.
	 * @return int
	 */
	private function account_age_days( \WC_Customer $customer ): int {
		$user_id = (int) $customer->get_id();
		if ( 0 === $user_id ) {
			return 0;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 0;
		}

		$registered = strtotime( $user->user_registered );
		return (int) floor( ( time() - $registered ) / 86400 );
	}

	/**
	 * Days since the customer's most recent order. Returns 0 if no orders.
	 *
	 * @param int $customer_id WP user ID.
	 * @return int
	 */
	private function days_since_last_order( int $customer_id ): int {
		if ( 0 === $customer_id ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $customer_id,
				'status'      => array( 'completed', 'processing' ),
				'limit'       => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'objects',
			)
		);

		if ( empty( $orders ) ) {
			return 0;
		}

		$last_date = $orders[0]->get_date_created();
		if ( ! $last_date ) {
			return 0;
		}

		return (int) floor( ( time() - $last_date->getTimestamp() ) / 86400 );
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return array<string, mixed>
	 * @deprecated Use from_order() instead.
	 */
	public function fromOrder( \WC_Order $order ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->from_order( $order );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param \WC_Customer $customer WooCommerce customer object.
	 * @return array<string, mixed>
	 * @deprecated Use from_customer() instead.
	 */
	public function fromCustomer( \WC_Customer $customer ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->from_customer( $customer );
	}
}
