<?php
/**
 * Admin view: add / edit tag form.
 *
 * @var array<string, mixed>|null $tag     Existing tag row, or null for new
 * @var array<string, string>     $errors  Validation errors
 * @var array<string, mixed>      $posted  Re-populated POST data after error
 */
if (!defined('ABSPATH')) exit;

$isNew    = ($tag === null);
$title    = $isNew ? __('Add Tag', 'wc-order-customer-tagger') : __('Edit Tag', 'wc-order-customer-tagger');
$listUrl  = add_query_arg(['page' => 'harper-tagger-tags'], admin_url('admin.php'));

// Merge saved values with any re-posted data (re-posted wins after validation fail)
$val = array_merge(
    ['name' => '', 'color' => '#3788d8', 'type' => 'order', 'image_url' => ''],
    $tag ?? [],
    $posted
);
$tagId = $isNew ? 0 : (int) $tag['id'];
?>
<div class="wrap harper-tagger">
    <h1>
        <?php echo esc_html($title); ?>
        <a href="<?php echo esc_url($listUrl); ?>" class="page-title-action">
            &larr; <?php esc_html_e('All Tags', 'wc-order-customer-tagger'); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php if ($errors): ?>
    <div class="notice notice-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'save'], admin_url('admin.php'))); ?>">
        <?php wp_nonce_field('harper_tagger_save_tag'); ?>
        <input type="hidden" name="tag_id" value="<?php echo esc_attr((string) $tagId); ?>">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="harper-tag-name"><?php esc_html_e('Name', 'wc-order-customer-tagger'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="harper-tag-name" name="name" class="regular-text"
                           value="<?php echo esc_attr($val['name']); ?>" maxlength="100" required>
                    <p class="description"><?php esc_html_e('Displayed as a badge on orders and customers.', 'wc-order-customer-tagger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="harper-tag-color"><?php esc_html_e('Badge Color', 'wc-order-customer-tagger'); ?></label>
                </th>
                <td>
                    <input type="text" id="harper-tag-color" name="color" class="harper-color-picker"
                           value="<?php echo esc_attr($val['color']); ?>">
                    <p class="description"><?php esc_html_e('Used when no custom image is set.', 'wc-order-customer-tagger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="harper-tag-type"><?php esc_html_e('Applies To', 'wc-order-customer-tagger'); ?></label>
                </th>
                <td>
                    <select id="harper-tag-type" name="type">
                        <option value="order"    <?php selected($val['type'], 'order'); ?>><?php esc_html_e('Orders only', 'wc-order-customer-tagger'); ?></option>
                        <option value="customer" <?php selected($val['type'], 'customer'); ?>><?php esc_html_e('Customers only', 'wc-order-customer-tagger'); ?></option>
                        <option value="both"     <?php selected($val['type'], 'both'); ?>><?php esc_html_e('Both orders &amp; customers', 'wc-order-customer-tagger'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="harper-tag-image"><?php esc_html_e('Custom Icon URL', 'wc-order-customer-tagger'); ?></label>
                </th>
                <td>
                    <input type="url" id="harper-tag-image" name="image_url" class="regular-text"
                           value="<?php echo esc_attr($val['image_url'] ?? ''); ?>"
                           placeholder="https://...">
                    <p class="description">
                        <?php esc_html_e('Optional PNG or SVG. Leave blank to use the color badge above.', 'wc-order-customer-tagger'); ?>
                    </p>
                    <?php if (!empty($val['image_url'])): ?>
                    <div class="harper-tagger-icon-preview-wrap">
                        <img src="<?php echo esc_url($val['image_url']); ?>" alt="" class="harper-tagger-icon-preview">
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php if (!$isNew): ?>
        <hr>
        <h3><?php esc_html_e('Preview', 'wc-order-customer-tagger'); ?></h3>
        <p>
            <?php if (!empty($val['image_url'])): ?>
                <img src="<?php echo esc_url($val['image_url']); ?>" alt="" class="harper-tagger-icon-preview">
            <?php else: ?>
                <span class="harper-tagger-badge" id="harper-badge-preview"
                      style="background:<?php echo esc_attr($val['color']); ?>">
                    <?php echo esc_html($val['name']); ?>
                </span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

        <?php submit_button($isNew ? __('Create Tag', 'wc-order-customer-tagger') : __('Update Tag', 'wc-order-customer-tagger')); ?>
    </form>
</div>
