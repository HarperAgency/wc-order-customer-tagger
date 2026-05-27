<?php
/**
 * Meta box: show applied tags on the WooCommerce order detail screen.
 * Supports both HPOS (woocommerce_page_wc-orders) and legacy (shop_order).
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

if (!defined('ABSPATH')) exit;

class OrderTagsMetaBox
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMetaBox(): void
    {
        $screens = ['shop_order', 'woocommerce_page_wc-orders'];

        foreach ($screens as $screen) {
            add_meta_box(
                'harper_tagger_order_tags',
                __('Order Tags', 'wc-order-customer-tagger'),
                [$this, 'renderMetaBox'],
                $screen,
                'side',
                'default'
            );
        }
    }

    public function enqueueAssets(string $hook): void
    {
        $orderScreens = ['post.php', 'post-new.php', 'woocommerce_page_wc-orders'];

        if (!in_array($hook, $orderScreens, true)) {
            // Also allow when the current screen post type is shop_order
            $screen = get_current_screen();
            if (!$screen || $screen->post_type !== 'shop_order') {
                return;
            }
        }

        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );
    }

    /**
     * Render callback — receives a WP_Post or WC_Order depending on HPOS mode.
     *
     * @param \WP_Post|\WC_Order $postOrOrder
     */
    public function renderMetaBox($postOrOrder): void
    {
        // Resolve the order ID from either a WP_Post or a WC_Order
        if ($postOrOrder instanceof \WC_Order) {
            $orderId = $postOrOrder->get_id();
        } elseif ($postOrOrder instanceof \WP_Post) {
            $orderId = $postOrOrder->ID;
        } else {
            $orderId = 0;
        }

        $tags = $orderId > 0 ? $this->getOrderTags($orderId) : [];

        require WC_TAGGER_DIR . 'admin/views/order-tags-metabox.php';
    }

    /**
     * Fetch applied tags for an order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getOrderTags(int $orderId): array
    {
        global $wpdb;
        $otTable   = $wpdb->prefix . 'harper_tagger_order_tags';
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name, t.color, t.image_url
                   FROM {$otTable} ot
                   JOIN {$tagsTable} t ON t.id = ot.tag_id
                  WHERE ot.order_id = %d
                  ORDER BY t.name ASC",
                $orderId
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }
}
