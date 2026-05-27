<?php
/**
 * GridFilters — tag filter dropdowns + retroactive rule evaluation bulk action.
 *
 * Wires up:
 *   - "Filter by tag" dropdown on the WC Orders list (HPOS + legacy)
 *   - "Filter by tag" dropdown on the WP Users list (WC customers)
 *   - "Re-run Tagger Rules" bulk action on the Orders list
 *   - Admin notice after bulk re-run showing order count processed
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

use HarperAgency\WCTagger\RuleEngine\Tagger;

if (!defined('ABSPATH')) exit;

class GridFilters
{
    private Tagger $tagger;

    public function __construct(Tagger $tagger)
    {
        $this->tagger = $tagger;
    }

    public function register(): void
    {
        // ── Orders: filter dropdown ───────────────────────────────────────────
        add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'renderOrderFilterHpos']);
        add_action('restrict_manage_posts', [$this, 'renderOrderFilterLegacy']);

        // ── Orders: apply filter to query ─────────────────────────────────────
        add_filter('woocommerce_orders_table_query_clauses', [$this, 'applyOrderTagFilterHpos'], 10, 3);
        add_filter('posts_join',  [$this, 'legacyOrdersJoin']);
        add_filter('posts_where', [$this, 'legacyOrdersWhere']);

        // ── Customers: filter dropdown ────────────────────────────────────────
        add_action('restrict_manage_users', [$this, 'renderCustomerFilter']);

        // ── Customers: apply filter ───────────────────────────────────────────
        add_filter('users_where', [$this, 'applyCustomerTagFilterWhere'], 10, 2);

        // ── Orders: bulk action registration ─────────────────────────────────
        add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'addOrderBulkActions']); // HPOS
        add_filter('bulk_actions-edit-shop_order',            [$this, 'addOrderBulkActions']); // legacy

        // ── Orders: bulk action handler ───────────────────────────────────────
        add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handleBulkRerun'], 10, 3);
        add_filter('handle_bulk_actions-edit-shop_order',            [$this, 'handleBulkRerun'], 10, 3);

        // ── Admin notice after bulk re-run ────────────────────────────────────
        add_action('admin_notices', [$this, 'showBulkRerunNotice']);
    }

    // ── Orders filter — HPOS ──────────────────────────────────────────────────

    public function renderOrderFilterHpos(): void
    {
        $this->renderTagSelect('harper_tag_id', 'order');
    }

    // ── Orders filter — legacy post-based ─────────────────────────────────────

    public function renderOrderFilterLegacy(): void
    {
        global $typenow;
        if ($typenow !== 'shop_order') {
            return;
        }
        $this->renderTagSelect('harper_tag_id', 'order');
    }

    // ── Orders query — HPOS ───────────────────────────────────────────────────

    /**
     * @param array<string,string> $clauses
     * @param mixed                $query
     * @param array<string,mixed>  $args
     * @return array<string,string>
     */
    public function applyOrderTagFilterHpos(array $clauses, $query, array $args): array
    {
        $tagId = (int) ($_GET['harper_tag_id'] ?? 0);
        if (!$tagId) {
            return $clauses;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'harper_tagger_order_tags';
        // wc_orders is the HPOS orders table; 'id' is its primary key
        $clauses['where'] .= $wpdb->prepare(
            " AND EXISTS (
                SELECT 1 FROM `{$table}` ht
                WHERE ht.order_id = {$wpdb->prefix}wc_orders.id
                  AND ht.tag_id  = %d
             )",
            $tagId
        );

        return $clauses;
    }

    // ── Orders query — legacy ─────────────────────────────────────────────────

    public function legacyOrdersJoin(string $join): string
    {
        global $pagenow, $typenow;
        if ($pagenow !== 'edit.php' || $typenow !== 'shop_order') {
            return $join;
        }
        $tagId = (int) ($_GET['harper_tag_id'] ?? 0);
        if (!$tagId) {
            return $join;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'harper_tagger_order_tags';
        // Safe: tagId already cast to int
        $join .= " LEFT JOIN `{$table}` ht_tag_filter"
               . " ON ht_tag_filter.order_id = {$wpdb->posts}.ID"
               . " AND ht_tag_filter.tag_id = {$tagId}";
        return $join;
    }

    public function legacyOrdersWhere(string $where): string
    {
        global $pagenow, $typenow;
        if ($pagenow !== 'edit.php' || $typenow !== 'shop_order') {
            return $where;
        }
        $tagId = (int) ($_GET['harper_tag_id'] ?? 0);
        if (!$tagId) {
            return $where;
        }

        $where .= ' AND ht_tag_filter.order_id IS NOT NULL';
        return $where;
    }

    // ── Customers filter ──────────────────────────────────────────────────────

    public function renderCustomerFilter(): void
    {
        // users.php is not restricted to a post type — render unconditionally.
        // Non-customer users simply won't appear in the tag table, so the filter
        // is harmless on a store with mixed user roles.
        $this->renderTagSelect('harper_customer_tag_id', 'customer');
    }

    /**
     * @param string        $where
     * @param \WP_User_Query $query
     */
    public function applyCustomerTagFilterWhere(string $where, \WP_User_Query $query): string
    {
        if (!is_admin()) {
            return $where;
        }
        $tagId = (int) ($_GET['harper_customer_tag_id'] ?? 0);
        if (!$tagId) {
            return $where;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'harper_tagger_customer_tags';
        $where .= $wpdb->prepare(
            " AND {$wpdb->users}.ID IN (
                SELECT customer_id FROM `{$table}` WHERE tag_id = %d
             )",
            $tagId
        );

        return $where;
    }

    // ── Bulk action: register ─────────────────────────────────────────────────

    /**
     * @param array<string,string> $actions
     * @return array<string,string>
     */
    public function addOrderBulkActions(array $actions): array
    {
        $actions['harper_rerun_rules'] = __('Re-run Tagger Rules', 'wc-order-customer-tagger');
        return $actions;
    }

    // ── Bulk action: handle ───────────────────────────────────────────────────

    /**
     * @param string   $redirectTo
     * @param string   $action
     * @param int[]    $orderIds
     * @return string
     */
    public function handleBulkRerun(string $redirectTo, string $action, array $orderIds): string
    {
        if ($action !== 'harper_rerun_rules') {
            return $redirectTo;
        }

        $count = 0;
        foreach ($orderIds as $orderId) {
            $order = wc_get_order((int) $orderId);
            if ($order instanceof \WC_Order) {
                $this->tagger->tagOrder($order);
                $count++;
            }
        }

        return add_query_arg('harper_rerun_count', $count, $redirectTo);
    }

    // ── Admin notice ──────────────────────────────────────────────────────────

    public function showBulkRerunNotice(): void
    {
        $count = (int) ($_GET['harper_rerun_count'] ?? 0);
        if ($count <= 0) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    _n(
                        'Tagger rules re-run on %d order.',
                        'Tagger rules re-run on %d orders.',
                        $count,
                        'wc-order-customer-tagger'
                    ),
                    $count
                )
            )
        );
    }

    // ── Shared helper ─────────────────────────────────────────────────────────

    /**
     * Render a <select> dropdown of tags filtered by type.
     * Preserves the current selection on page reload.
     */
    private function renderTagSelect(string $paramName, string $tagType): void
    {
        global $wpdb;
        $p    = $wpdb->prefix;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name FROM {$p}harper_tagger_tags
                  WHERE type = %s
                  ORDER BY name ASC",
                $tagType
            ),
            ARRAY_A
        );

        if (!$rows) {
            return;
        }

        $selected = (int) ($_GET[$paramName] ?? 0);
        echo '<select name="' . esc_attr($paramName) . '" id="' . esc_attr($paramName) . '" style="float:none;margin-left:6px;">';
        echo '<option value="">' . esc_html__('Filter by tag&hellip;', 'wc-order-customer-tagger') . '</option>';
        foreach ($rows as $row) {
            $id   = (int) $row['id'];
            $name = (string) $row['name'];
            printf(
                '<option value="%d"%s>%s</option>',
                $id,
                $id === $selected ? ' selected' : '',
                esc_html($name)
            );
        }
        echo '</select>';
    }
}
