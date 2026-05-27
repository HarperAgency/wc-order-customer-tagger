<?php
/**
 * Admin page: WooCommerce → Order & Customer Tags
 * Handles list, add, edit, and delete actions for tags and rules.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

use HarperAgency\WCTagger\Db\TagRepository;
use HarperAgency\WCTagger\Db\RuleRepository;

if (!defined('ABSPATH')) exit;

class TagsAdmin
{
    private TagRepository  $repo;
    private RuleRepository $ruleRepo;

    public function __construct(TagRepository $repo, RuleRepository $ruleRepo)
    {
        $this->repo     = $repo;
        $this->ruleRepo = $ruleRepo;
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
        $tagId  = (int) ($_GET['tag_id'] ?? 0);

        match ($action) {
            'add'         => $this->pageEdit(null),
            'edit'        => $this->pageEdit($id),
            'save'        => $this->handleSave(),
            'delete'      => $this->handleDelete($id),
            'rules'       => $this->pageRulesList($tagId),
            'rule-add'    => $this->pageRuleEdit($tagId, null),
            'rule-edit'   => $this->pageRuleEdit($tagId, $id),
            'rule-save'   => $this->handleRuleSave(),
            'rule-delete' => $this->handleRuleDelete($id),
            default       => $this->pageList(),
        };
    }

    // ── Tag list page ─────────────────────────────────────────────────────────

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

    // ── Tag edit / add page ───────────────────────────────────────────────────

    private function pageEdit(?int $id): void
    {
        $tag = $id ? $this->repo->findById($id) : null;

        if ($id && !$tag) {
            wp_die(esc_html__('Tag not found.', 'wc-order-customer-tagger'));
        }

        $rules  = $id ? $this->ruleRepo->findByTagId($id) : [];
        $errors = $this->getErrors();
        $posted = $this->getPosted();           // repopulate form after validation failure
        require WC_TAGGER_DIR . 'admin/views/tags-edit.php';
    }

    // ── Tag save handler ──────────────────────────────────────────────────────

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

    // ── Tag delete handler ────────────────────────────────────────────────────

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

    // ── Rules list page ───────────────────────────────────────────────────────

    private function pageRulesList(int $tagId): void
    {
        $tag = $tagId ? $this->repo->findById($tagId) : null;

        if (!$tag) {
            wp_die(esc_html__('Tag not found.', 'wc-order-customer-tagger'));
        }

        $rules  = $this->ruleRepo->findByTagId($tagId);
        $notice = $this->popNotice();
        require WC_TAGGER_DIR . 'admin/views/rules-list.php';
    }

    // ── Rule edit / add page ──────────────────────────────────────────────────

    private function pageRuleEdit(int $tagId, ?int $ruleId): void
    {
        $tag = $tagId ? $this->repo->findById($tagId) : null;

        if (!$tag) {
            wp_die(esc_html__('Tag not found.', 'wc-order-customer-tagger'));
        }

        $rule   = $ruleId ? $this->ruleRepo->findById($ruleId) : null;
        $errors = $this->getErrors();
        $posted = $this->getPosted();
        require WC_TAGGER_DIR . 'admin/views/rules-edit.php';
    }

    // ── Rule save handler ─────────────────────────────────────────────────────

    private function handleRuleSave(): void
    {
        check_admin_referer('harper_tagger_save_rule');

        $ruleId  = (int) ($_POST['rule_id'] ?? 0);
        $tagId   = (int) ($_POST['tag_id'] ?? 0);
        $label   = trim(sanitize_text_field($_POST['label'] ?? ''));
        $trigger = sanitize_key($_POST['rule_trigger'] ?? 'order_placed');
        $op      = strtoupper(sanitize_key($_POST['operator'] ?? 'and'));
        $priority = (int) ($_POST['priority'] ?? 10);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Decode conditions from hidden JSON field
        $conditionsRaw = stripslashes(sanitize_textarea_field($_POST['conditions_json'] ?? '[]'));
        $conditions    = json_decode($conditionsRaw, true);
        if (!is_array($conditions)) {
            $conditions = [];
        }

        // ── Validate ──────────────────────────────────────────────────────────
        $errors = [];

        if (!$this->repo->findById($tagId)) {
            $errors[] = __('Invalid tag.', 'wc-order-customer-tagger');
        }

        if ($label === '') {
            $errors[] = __('Rule label is required.', 'wc-order-customer-tagger');
        } elseif (strlen($label) > 100) {
            $errors[] = __('Rule label must be 100 characters or fewer.', 'wc-order-customer-tagger');
        }

        if (!in_array($op, RuleRepository::VALID_OPERATORS, true)) {
            $errors[] = __('Invalid operator.', 'wc-order-customer-tagger');
        }

        if ($errors) {
            $this->stashErrors($errors);
            $this->stashPosted(compact('label', 'trigger', 'op', 'priority', 'isActive', 'conditionsRaw'));
            $redirect = $ruleId
                ? add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-edit', 'tag_id' => $tagId, 'id' => $ruleId], admin_url('admin.php'))
                : add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-add',  'tag_id' => $tagId], admin_url('admin.php'));
            wp_safe_redirect($redirect);
            exit;
        }

        // Sanitize each condition
        $conditions = $this->sanitizeConditions($conditions);

        $data = [
            'label'        => $label,
            'rule_trigger' => $trigger,
            'operator'     => $op,
            'conditions'   => $conditions,
            'priority'     => $priority,
            'is_active'    => $isActive,
        ];

        if ($ruleId > 0) {
            $this->ruleRepo->update($ruleId, $data);
            $this->stashNotice(__('Rule updated.', 'wc-order-customer-tagger'), 'updated');
        } else {
            $this->ruleRepo->insert($tagId, $data);
            $this->stashNotice(__('Rule created.', 'wc-order-customer-tagger'), 'updated');
        }

        wp_safe_redirect(add_query_arg([
            'page'   => 'harper-tagger-tags',
            'action' => 'rules',
            'tag_id' => $tagId,
        ], admin_url('admin.php')));
        exit;
    }

    // ── Rule delete handler ───────────────────────────────────────────────────

    private function handleRuleDelete(int $id): void
    {
        $tagId = (int) ($_GET['tag_id'] ?? 0);
        check_admin_referer('harper_tagger_delete_rule_' . $id);

        if ($id > 0) {
            $this->ruleRepo->delete($id);
        }

        $this->stashNotice(__('Rule deleted.', 'wc-order-customer-tagger'), 'updated');
        wp_safe_redirect(add_query_arg([
            'page'   => 'harper-tagger-tags',
            'action' => 'rules',
            'tag_id' => $tagId,
        ], admin_url('admin.php')));
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

    // ── Condition sanitiser ───────────────────────────────────────────────────

    /**
     * Sanitise raw decoded conditions array from JSON POST data.
     *
     * @param array<mixed> $conditions
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    private function sanitizeConditions(array $conditions): array
    {
        $valid = [];
        foreach ($conditions as $c) {
            if (!is_array($c)) {
                continue;
            }
            $field    = sanitize_key((string) ($c['field']    ?? ''));
            $operator = sanitize_key((string) ($c['operator'] ?? ''));
            $value    = $c['value'] ?? '';

            if ($field === '' || $operator === '') {
                continue;
            }

            // For in/not_in, value may be a comma-separated string → convert to array
            if (in_array($operator, ['in', 'not_in'], true) && is_string($value)) {
                $value = array_values(array_filter(array_map('trim', explode(',', $value))));
            }

            // For boolean operators, value is irrelevant — normalise to empty string
            if (in_array($operator, ['is_true', 'is_false'], true)) {
                $value = '';
            }

            $valid[] = [
                'field'    => $field,
                'operator' => $operator,
                'value'    => is_array($value)
                    ? array_map('sanitize_text_field', $value)
                    : sanitize_text_field((string) $value),
            ];
        }
        return $valid;
    }
}
