<?php
/**
 * Adds a "Tags" column to the WP Users list (WooCommerce customers).
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

if (!defined('ABSPATH')) exit;

class CustomerTagsColumn
{
    public function register(): void
    {
        add_filter('manage_users_columns',       [$this, 'addColumn']);
        add_filter('manage_users_custom_column', [$this, 'renderColumn'], 10, 3);
        add_action('admin_enqueue_scripts',      [$this, 'enqueueAssets']);
    }

    public function addColumn(array $columns): array
    {
        $columns['harper_customer_tags'] = __('Tags', 'wc-order-customer-tagger');
        return $columns;
    }

    /**
     * @param string $output  Default column output (empty string for custom columns)
     * @param string $column  Column key
     * @param int    $userId  WP user ID
     */
    public function renderColumn(string $output, string $column, int $userId): string
    {
        if ($column !== 'harper_customer_tags') return $output;

        $tags = $this->getCustomerTags($userId);

        $html = sprintf(
            '<div class="harper-tagger-cell" data-type="customer" data-id="%d" title="%s">',
            $userId,
            esc_attr__('Click to edit tags', 'wc-order-customer-tagger')
        );

        if (empty($tags)) {
            $html .= '<span class="harper-tagger-no-tags">—</span>';
        } else {
            $html .= '<div class="harper-tagger-column-tags">';
            foreach ($tags as $tag) {
                if (!empty($tag['image_url'])) {
                    $html .= sprintf(
                        '<img src="%s" alt="%s" title="%s" class="harper-tagger-icon-preview">',
                        esc_url($tag['image_url']),
                        esc_attr($tag['name']),
                        esc_attr($tag['name'])
                    );
                } else {
                    $html .= sprintf(
                        '<span class="harper-tagger-badge" style="background:%s" title="%s">%s</span>',
                        esc_attr($tag['color']),
                        esc_attr($tag['name']),
                        esc_html($tag['name'])
                    );
                }
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'users.php') return;

        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );
    }

    private function getCustomerTags(int $customerId): array
    {
        global $wpdb;
        $ctTable   = $wpdb->prefix . 'harper_tagger_customer_tags';
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name, t.color, t.image_url
                   FROM {$ctTable} ct
                   JOIN {$tagsTable} t ON t.id = ct.tag_id
                  WHERE ct.customer_id = %d
                  ORDER BY t.name ASC",
                $customerId
            ),
            ARRAY_A
        ) ?: [];
    }
}
