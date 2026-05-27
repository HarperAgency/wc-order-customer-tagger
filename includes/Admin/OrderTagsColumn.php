<?php
/**
 * Adds a "Tags" column to the WooCommerce orders list (both HPOS and legacy).
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

if (!defined('ABSPATH')) exit;

class OrderTagsColumn
{
    public function register(): void
    {
        // HPOS order list
        add_filter('woocommerce_shop_order_list_table_columns',       [$this, 'addColumn']);
        add_action('woocommerce_shop_order_list_table_custom_column', [$this, 'renderColumn'], 10, 2);

        // Legacy post-based order list
        add_filter('manage_edit-shop_order_columns',       [$this, 'addColumn']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderLegacyColumn'], 10, 2);

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addColumn(array $columns): array
    {
        // Insert Tags column after 'order_status'
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'order_status') {
                $new['harper_tags'] = __('Tags', 'wc-order-customer-tagger');
            }
        }
        // Fallback: wasn't inserted yet (column order differs)
        if (!isset($new['harper_tags'])) {
            $new['harper_tags'] = __('Tags', 'wc-order-customer-tagger');
        }
        return $new;
    }

    /**
     * HPOS column render — receives WC_Order directly.
     */
    public function renderColumn(string $column, \WC_Order $order): void
    {
        if ($column !== 'harper_tags') return;
        $this->outputTags($this->getOrderTags($order->get_id()), $order->get_id());
    }

    /**
     * Legacy column render — receives post ID.
     */
    public function renderLegacyColumn(string $column, int $postId): void
    {
        if ($column !== 'harper_tags') return;
        $this->outputTags($this->getOrderTags($postId), $postId);
    }

    public function enqueueAssets(string $hook): void
    {
        $screens = ['edit.php', 'woocommerce_page_wc-orders'];
        if (!in_array($hook, $screens, true)) return;

        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );
    }

    private function outputTags(array $tags, int $orderId): void
    {
        printf(
            '<div class="harper-tagger-cell" data-type="order" data-id="%d" title="%s">',
            $orderId,
            esc_attr__('Click to edit tags', 'wc-order-customer-tagger')
        );

        if (empty($tags)) {
            echo '<span class="harper-tagger-no-tags">—</span>';
        } else {
            echo '<div class="harper-tagger-column-tags">';
            foreach ($tags as $tag) {
                if (!empty($tag['image_url'])) {
                    printf(
                        '<img src="%s" alt="%s" title="%s" class="harper-tagger-icon-preview">',
                        esc_url($tag['image_url']),
                        esc_attr($tag['name']),
                        esc_attr($tag['name'])
                    );
                } else {
                    printf(
                        '<span class="harper-tagger-badge" style="background:%s" title="%s">%s</span>',
                        esc_attr($tag['color']),
                        esc_attr($tag['name']),
                        esc_html($tag['name'])
                    );
                }
            }
            echo '</div>';
        }

        echo '</div>';
    }

    private function getOrderTags(int $orderId): array
    {
        global $wpdb;
        $otTable   = $wpdb->prefix . 'harper_tagger_order_tags';
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name, t.color, t.image_url
                   FROM {$otTable} ot
                   JOIN {$tagsTable} t ON t.id = ot.tag_id
                  WHERE ot.order_id = %d
                  ORDER BY t.name ASC",
                $orderId
            ),
            ARRAY_A
        ) ?: [];
    }
}
