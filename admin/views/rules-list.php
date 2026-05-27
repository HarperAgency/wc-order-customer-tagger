<?php
/**
 * Admin view: rules list for a tag.
 *
 * @var array<string, mixed>                 $tag    Parent tag row
 * @var array<int, array<string, mixed>>     $rules  Rules for this tag
 * @var array{message: string, type: string}|null $notice
 */
if (!defined('ABSPATH')) exit;

$tagId      = (int) $tag['id'];
$tagEditUrl = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'edit', 'id' => $tagId], admin_url('admin.php'));
$addRuleUrl = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-add', 'tag_id' => $tagId], admin_url('admin.php'));

$operatorLabels = [
    'AND' => __('AND', 'wc-order-customer-tagger'),
    'OR'  => __('OR',  'wc-order-customer-tagger'),
];
?>
<div class="wrap harper-tagger">
    <h1 class="wp-heading-inline">
        <?php
        /* translators: %s: tag name */
        printf(esc_html__('Rules — %s', 'wc-order-customer-tagger'), esc_html($tag['name']));
        ?>
    </h1>
    <a href="<?php echo esc_url($addRuleUrl); ?>" class="page-title-action">
        <?php esc_html_e('Add Rule', 'wc-order-customer-tagger'); ?>
    </a>
    <a href="<?php echo esc_url($tagEditUrl); ?>" class="page-title-action">
        &larr; <?php esc_html_e('Back to Tag', 'wc-order-customer-tagger'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($notice): ?>
    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
        <p><?php echo esc_html($notice['message']); ?></p>
    </div>
    <?php endif; ?>

    <?php if (empty($rules)): ?>
    <p>
        <?php esc_html_e('No rules yet.', 'wc-order-customer-tagger'); ?>
        <a href="<?php echo esc_url($addRuleUrl); ?>"><?php esc_html_e('Add the first rule', 'wc-order-customer-tagger'); ?></a>.
    </p>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped harper-tagger-list">
        <thead>
            <tr>
                <th style="width:40px"><?php esc_html_e('ID', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Label', 'wc-order-customer-tagger'); ?></th>
                <th style="width:60px"><?php esc_html_e('Logic', 'wc-order-customer-tagger'); ?></th>
                <th style="width:70px"><?php esc_html_e('Priority', 'wc-order-customer-tagger'); ?></th>
                <th style="width:70px"><?php esc_html_e('Active', 'wc-order-customer-tagger'); ?></th>
                <th style="width:80px"><?php esc_html_e('Conditions', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Actions', 'wc-order-customer-tagger'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rules as $rule):
            $ruleId  = (int) $rule['id'];
            $editUrl = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-edit',   'tag_id' => $tagId, 'id' => $ruleId], admin_url('admin.php'));
            $delUrl  = wp_nonce_url(
                add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-delete', 'tag_id' => $tagId, 'id' => $ruleId], admin_url('admin.php')),
                'harper_tagger_delete_rule_' . $ruleId
            );
        ?>
        <tr>
            <td><?php echo esc_html((string) $ruleId); ?></td>
            <td><strong><?php echo esc_html($rule['label']); ?></strong></td>
            <td><?php echo esc_html($operatorLabels[$rule['operator']] ?? esc_html($rule['operator'])); ?></td>
            <td><?php echo esc_html((string) $rule['priority']); ?></td>
            <td>
                <?php if ($rule['is_active']): ?>
                    <span class="harper-tagger-badge" style="background:#00a32a"><?php esc_html_e('Yes', 'wc-order-customer-tagger'); ?></span>
                <?php else: ?>
                    <span class="harper-tagger-badge" style="background:#999"><?php esc_html_e('No', 'wc-order-customer-tagger'); ?></span>
                <?php endif; ?>
            </td>
            <td><?php echo esc_html((string) count((array) ($rule['conditions'] ?? []))); ?></td>
            <td>
                <a href="<?php echo esc_url($editUrl); ?>"><?php esc_html_e('Edit', 'wc-order-customer-tagger'); ?></a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url($delUrl); ?>"
                   onclick="return confirm('<?php esc_attr_e('Delete this rule? This cannot be undone.', 'wc-order-customer-tagger'); ?>')"
                   class="harper-tagger-delete-link">
                    <?php esc_html_e('Delete', 'wc-order-customer-tagger'); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
