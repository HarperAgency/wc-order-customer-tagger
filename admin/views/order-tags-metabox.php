<?php
/**
 * Meta box view: tags applied to the current order.
 *
 * @var array<int, array<string, mixed>> $tags Applied tag rows (id, name, color, image_url)
 */
if (!defined('ABSPATH')) exit;
?>
<div class="harper-tagger-metabox">
    <?php if (empty($tags)): ?>
        <p class="harper-tagger-no-tags"><?php esc_html_e('No tags applied.', 'wc-order-customer-tagger'); ?></p>
    <?php else: ?>
        <div class="harper-tagger-metabox-tags">
            <?php foreach ($tags as $tag): ?>
                <?php if (!empty($tag['image_url'])): ?>
                    <img src="<?php echo esc_url($tag['image_url']); ?>"
                         alt="<?php echo esc_attr($tag['name']); ?>"
                         title="<?php echo esc_attr($tag['name']); ?>"
                         class="harper-tagger-icon-preview">
                <?php else: ?>
                    <span class="harper-tagger-badge"
                          style="background:<?php echo esc_attr($tag['color']); ?>"
                          title="<?php echo esc_attr($tag['name']); ?>">
                        <?php echo esc_html($tag['name']); ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
