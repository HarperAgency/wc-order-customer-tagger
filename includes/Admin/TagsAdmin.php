<?php
/**
 * Admin page: WooCommerce → Order & Customer Tags
 * Handles list, add, edit, and delete actions.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

use HarperAgency\WCTagger\Db\TagRepository;

if (!defined('ABSPATH')) exit;

class TagsAdmin
{
    private TagRepository $repo;

    public function __construct(TagRepository $repo)
    {
        $this->repo = $repo;
    }

    public function register(): void
    {
        add_action('admin_menu',   [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Order & Customer Tags', 'wc-order-customer-tagger'),
            __('Order & Cust. Tags', 'wc-order-customer-tagger'),
            'manage_woocommerce',
            'harper-tagger-tags',
            [$this, 'dispatch']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_harper-tagger-tags') {
            return;
        }
        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );
        // WP colour picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/js/tagger-admin.js',
            ['wp-color-picker'],
            WC_TAGGER_VERSION,
            true
        );
    }

    // ── Dispatcher ────────────────────────────────────────────────────────────

    public function dispatch(): void
    {
        $action = sanitize_key($_REQUEST['action'] ?? 'list');
        $id     = (int) ($_GET['id'] ?? 0);

        match ($action) {
            'add'    => $this->pageEdit(null),
            'edit'   => $this->pageEdit($id),
            'save'   => $this->handleSave(),
            'delete' => $this->handleDelete($id),
            default  => $this->pageList(),
        };
    }

    // ── List page ─────────────────────────────────────────────────────────────

    private function pageList(): void
    {
        $tags = $this->repo->findAll();

        // Append rule counts
        foreach ($tags as &$tag) {
            $tag['rule_count'] = $this->repo->countRulesForTag((int) $tag['id']);
        }
        unset($tag);

        $notice = $this->popNotice();
        require WC_TAGGER_DIR . 'admin/views/tags-list.php';
    }

    // ── Edit / Add page ───────────────────────────────────────────────────────

    private function pageEdit(?int $id): void
    {
        $tag = $id ? $this->repo->findById($id) : null;

        if ($id && !$tag) {
            wp_die(esc_html__('Tag not found.', 'wc-order-customer-tagger'));
        }

        $errors = $this->getErrors();
        $posted = $this->getPosted();           // repopulate form after validation failure
        require WC_TAGGER_DIR . 'admin/views/tags-edit.php';
    }

    // ── Save handler ──────────────────────────────────────────────────────────

    private function handleSave(): void
    {
        check_admin_referer('harper_tagger_save_tag');

        $id    = (int) ($_POST['tag_id'] ?? 0);
        $name  = trim(sanitize_text_field($_POST['name'] ?? ''));
        $color = sanitize_hex_color($_POST['color'] ?? '') ?: '#3788d8';
        $type  = sanitize_key($_POST['type'] ?? 'order');
        $image = esc_url_raw(trim($_POST['image_url'] ?? '')) ?: null;

        // ── Validate ──────────────────────────────────────────────────────────
        $errors = [];

        if ($name === '') {
            $errors[] = __('Tag name is required.', 'wc-order-customer-tagger');
        } elseif (strlen($name) > 100) {
            $errors[] = __('Tag name must be 100 characters or fewer.', 'wc-order-customer-tagger');
        }

        if (!in_array($type, TagRepository::VALID_TYPES, true)) {
            $errors[] = __('Invalid tag type.', 'wc-order-customer-tagger');
        }

        if ($errors) {
            $this->stashErrors($errors);
            $this->stashPosted(compact('name', 'color', 'type', 'image'));
            $redirect = $id
                ? add_query_arg(['action' => 'edit', 'id' => $id])
                : add_query_arg(['action' => 'add']);
            wp_safe_redirect($redirect);
            exit;
        }

        // ── Persist ───────────────────────────────────────────────────────────
        $slug = $this->repo->uniqueSlug($name, $id);
        $data = compact('name', 'slug', 'color', 'type') + ['image_url' => $image];

        if ($id > 0) {
            $this->repo->update($id, $data);
            $this->stashNotice(__('Tag updated.', 'wc-order-customer-tagger'), 'updated');
        } else {
            $id = $this->repo->insert($data);
            $this->stashNotice(__('Tag created.', 'wc-order-customer-tagger'), 'updated');
        }

        wp_safe_redirect(add_query_arg([
            'page'   => 'harper-tagger-tags',
            'action' => 'edit',
            'id'     => $id,
        ], admin_url('admin.php')));
        exit;
    }

    // ── Delete handler ────────────────────────────────────────────────────────

    private function handleDelete(int $id): void
    {
        check_admin_referer('harper_tagger_delete_tag_' . $id);

        if ($id > 0) {
            $this->repo->delete($id);
        }

        $this->stashNotice(__('Tag deleted.', 'wc-order-customer-tagger'), 'updated');
        wp_safe_redirect(add_query_arg(['page' => 'harper-tagger-tags'], admin_url('admin.php')));
        exit;
    }

    // ── Transient helpers ─────────────────────────────────────────────────────

    private function stashNotice(string $message, string $type): void
    {
        set_transient('harper_tagger_notice_' . get_current_user_id(), compact('message', 'type'), 60);
    }

    private function popNotice(): ?array
    {
        $key    = 'harper_tagger_notice_' . get_current_user_id();
        $notice = get_transient($key);
        delete_transient($key);
        return $notice ?: null;
    }

    private function stashErrors(array $errors): void
    {
        set_transient('harper_tagger_errors_' . get_current_user_id(), $errors, 60);
    }

    private function getErrors(): array
    {
        $key    = 'harper_tagger_errors_' . get_current_user_id();
        $errors = get_transient($key);
        delete_transient($key);
        return $errors ?: [];
    }

    private function stashPosted(array $data): void
    {
        set_transient('harper_tagger_posted_' . get_current_user_id(), $data, 60);
    }

    private function getPosted(): array
    {
        $key    = 'harper_tagger_posted_' . get_current_user_id();
        $posted = get_transient($key);
        delete_transient($key);
        return $posted ?: [];
    }
}
