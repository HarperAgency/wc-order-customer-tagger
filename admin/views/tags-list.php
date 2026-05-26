<?php
/**
 * Admin view: tag list table.
 *
 * @var array<int, array<string, mixed>> $tags
 * @var array{message: string, type: string}|null $notice
 */
if (!defined('ABSPATH')) exit;

$addUrl = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'add'], admin_url('admin.php'));
?>
<div class="wrap harper-tagger">
    <h1 class="wp-heading-inline"><?php esc_html_e('Order &amp; Customer Tags', 'wc-order-customer-tagger'); ?></h1>
    <a href="<?php echo esc_url($addUrl); ?>" class="page-title-action">
        <?php esc_html_e('Add Tag', 'wc-order-customer-tagger'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ($notice): ?>
    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
        <p><?php echo esc_html($notice['message']); ?></p>
    </div>
    <?php endif; ?>

    <?php if (empty($tags)): ?>
    <p><?php esc_html_e('No tags yet. Create your first tag to get started.', 'wc-order-customer-tagger'); ?></p>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped harper-tagger-list">
        <thead>
            <tr>
                <th style="width:40px"><?php esc_html_e('ID', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Badge', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Name', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Slug', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Type', 'wc-order-customer-tagger'); ?></th>
                <th style="width:80px"><?php esc_html_e('Rules', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Created', 'wc-order-customer-tagger'); ?></th>
                <th><?php esc_html_e('Actions', 'wc-order-customer-tagger'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tags as $tag):
            $id      = (int) $tag['id'];
            $editUrl = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'edit', 'id' => $id], admin_url('admin.php'));
            $delUrl  = wp_nonce_url(
                add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'delete', 'id' => $id], admin_url('admin.php')),
                'harper_tagger_delete_tag_' . $id
            );
            $typeLabels = ['order' => __('Order', 'wc-order-customer-tagger'),
                           'customer' => __('Customer', 'wc-order-customer-tagger'),
                           'both' => __('Both', 'wc-order-customer-tagger')];
        ?>
        <tr>
            <td><?php echo esc_html((string) $id); ?></td>
            <td>
                <?php if (!empty($tag['image_url'])): ?>
                    <img src="<?php echo esc_url($tag['image_url']); ?>" alt="" class="harper-tagger-icon-preview">
                <?php else: ?>
                    <span class="harper-tagger-badge" style="background:<?php echo esc_attr($tag['color']); ?>">
                        <?php echo esc_html($tag['name']); ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><strong><?php echo esc_html($tag['name']); ?></strong></td>
            <td><code><?php echo esc_html($tag['slug']); ?></code></td>
            <td><?php echo esc_html($typeLabels[$tag['type']] ?? $tag['type']); ?></td>
            <td><?php echo esc_html((string) $tag['rule_count']); ?></td>
            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tag['created_at']))); ?></td>
            <td>
                <a href="<?php echo esc_url($editUrl); ?>"><?php esc_html_e('Edit', 'wc-order-customer-tagger'); ?></a>
                &nbsp;|&nbsp;
                <a href="<?php echo esc_url($delUrl); ?>"
                   onclick="return confirm('<?php esc_attr_e('Delete this tag? This cannot be undone.', 'wc-order-customer-tagger'); ?>')"
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
