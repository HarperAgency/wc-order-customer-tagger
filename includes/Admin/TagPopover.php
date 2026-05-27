<?php
/**
 * Inline tag editing popover for the Orders and Customers grid views.
 *
 * Registers AJAX endpoints and enqueues the popover JS/CSS on the relevant
 * admin screens. The JS handles all UI; PHP only persists the changes.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

if (!defined('ABSPATH')) exit;

class TagPopover
{
    private const NONCE_ACTION = 'harper_tagger_popover';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // AJAX — logged-in admin users only (no nopriv)
        add_action('wp_ajax_harper_tagger_get_all_tags',  [$this, 'ajaxGetAllTags']);
        add_action('wp_ajax_harper_tagger_add_tag',       [$this, 'ajaxAddTag']);
        add_action('wp_ajax_harper_tagger_remove_tag',    [$this, 'ajaxRemoveTag']);
    }

    public function enqueueAssets(string $hook): void
    {
        $screens = ['edit.php', 'woocommerce_page_wc-orders', 'users.php'];
        if (!in_array($hook, $screens, true)) return;

        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );

        wp_enqueue_script(
            'harper-tag-popover',
            WC_TAGGER_URL . 'admin/js/tag-popover.js',
            ['jquery'],
            WC_TAGGER_VERSION,
            true
        );

        wp_localize_script('harper-tag-popover', 'harperTaggerPopover', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(self::NONCE_ACTION),
            'i18n'    => [
                'applied'     => __('Applied', 'wc-order-customer-tagger'),
                'add'         => __('Add tag', 'wc-order-customer-tagger'),
                'noTags'      => __('No tags defined yet.', 'wc-order-customer-tagger'),
                'allApplied'  => __('All tags applied.', 'wc-order-customer-tagger'),
                'saving'      => __('Saving…', 'wc-order-customer-tagger'),
                'error'       => __('Error saving tag. Please try again.', 'wc-order-customer-tagger'),
            ],
        ]);
    }

    // ── AJAX: get all defined tags ─────────────────────────────────────────────

    public function ajaxGetAllTags(): void
    {
        $this->verifyRequest();

        global $wpdb;
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';
        $tags = $wpdb->get_results(
            $wpdb->prepare('SELECT id, name, color, image_url FROM `%i` ORDER BY name ASC', $tagsTable),
            ARRAY_A
        ) ?: [];

        wp_send_json_success(['tags' => $tags]);
    }

    // ── AJAX: add a tag to an order or customer ────────────────────────────────

    public function ajaxAddTag(): void
    {
        $this->verifyRequest();

        $type     = sanitize_key($_POST['type']     ?? '');
        $objectId = (int) ($_POST['object_id']      ?? 0);
        $tagId    = (int) ($_POST['tag_id']          ?? 0);

        if (!in_array($type, ['order', 'customer'], true) || !$objectId || !$tagId) {
            wp_send_json_error(['message' => 'Invalid parameters.']);
        }

        global $wpdb;
        $table = $type === 'order'
            ? $wpdb->prefix . 'harper_tagger_order_tags'
            : $wpdb->prefix . 'harper_tagger_customer_tags';

        $col = $type === 'order' ? 'order_id' : 'customer_id';

        // INSERT IGNORE prevents duplicate-key errors
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} ({$col}, tag_id) VALUES (%d, %d)",
            $objectId,
            $tagId
        ));

        wp_send_json_success([
            'tags' => $this->getAppliedTags($type, $objectId),
        ]);
    }

    // ── AJAX: remove a tag from an order or customer ───────────────────────────

    public function ajaxRemoveTag(): void
    {
        $this->verifyRequest();

        $type     = sanitize_key($_POST['type']    ?? '');
        $objectId = (int) ($_POST['object_id']     ?? 0);
        $tagId    = (int) ($_POST['tag_id']         ?? 0);

        if (!in_array($type, ['order', 'customer'], true) || !$objectId || !$tagId) {
            wp_send_json_error(['message' => 'Invalid parameters.']);
        }

        global $wpdb;
        $table = $type === 'order'
            ? $wpdb->prefix . 'harper_tagger_order_tags'
            : $wpdb->prefix . 'harper_tagger_customer_tags';

        $col = $type === 'order' ? 'order_id' : 'customer_id';

        $wpdb->delete($table, [$col => $objectId, 'tag_id' => $tagId]);

        wp_send_json_success([
            'tags' => $this->getAppliedTags($type, $objectId),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function verifyRequest(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
        }
    }

    private function getAppliedTags(string $type, int $objectId): array
    {
        global $wpdb;

        $joinTable = $type === 'order'
            ? $wpdb->prefix . 'harper_tagger_order_tags'
            : $wpdb->prefix . 'harper_tagger_customer_tags';

        $joinCol   = $type === 'order' ? 'order_id' : 'customer_id';
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name, t.color, t.image_url
                   FROM {$joinTable} jt
                   JOIN {$tagsTable} t ON t.id = jt.tag_id
                  WHERE jt.{$joinCol} = %d
                  ORDER BY t.name ASC",
                $objectId
            ),
            ARRAY_A
        ) ?: [];
    }
}
