<?php
/**
 * User profile section: customer tags applied to this user.
 *
 * @var array<int, array<string, mixed>> $tags Applied tag rows (id, name, color, image_url)
 */
if (!defined('ABSPATH')) exit;
?>
<div class="harper-tagger-profile-section">
    <h2><?php esc_html_e('Customer Tags', 'wc-order-customer-tagger'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Applied Tags', 'wc-order-customer-tagger'); ?></th>
            <td>
                <?php if (empty($tags)): ?>
                    <p class="harper-tagger-no-tags"><?php esc_html_e('No customer tags applied.', 'wc-order-customer-tagger'); ?></p>
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
            </td>
        </tr>
    </table>
</div>
