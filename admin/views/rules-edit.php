<?php
/**
 * Admin view: add / edit rule form.
 *
 * @var array<string, mixed>             $tag    Parent tag row
 * @var array<string, mixed>|null        $rule   Existing rule row, or null for new
 * @var array<string, string>            $errors Validation errors
 * @var array<string, mixed>             $posted Re-populated POST data after error
 */
if (!defined('ABSPATH')) exit;

$tagId    = (int) $tag['id'];
$isNew    = ($rule === null);
$title    = $isNew ? __('Add Rule', 'wc-order-customer-tagger') : __('Edit Rule', 'wc-order-customer-tagger');
$backUrl  = add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rules', 'tag_id' => $tagId], admin_url('admin.php'));
$ruleId   = $isNew ? 0 : (int) $rule['id'];

// Merge existing data with any re-posted data (re-posted wins)
$defaultConditions = [];
$val = array_merge(
    ['label' => '', 'rule_trigger' => 'order_placed', 'operator' => 'AND', 'priority' => 10, 'is_active' => 1],
    $rule ?? [],
    $posted
);

// Conditions to pre-populate: from rule row (already decoded array) or posted JSON
if (!empty($posted['conditionsRaw'])) {
    $existingConditions = json_decode(stripslashes((string) $posted['conditionsRaw']), true) ?: [];
} elseif (!empty($rule['conditions']) && is_array($rule['conditions'])) {
    $existingConditions = $rule['conditions'];
} else {
    $existingConditions = $defaultConditions;
}

// Field definitions: [type, operators[]]
$fieldDefs = [
    'order_total'               => ['type' => 'numeric', 'label' => __('Order total',                'wc-order-customer-tagger')],
    'item_count'                => ['type' => 'numeric', 'label' => __('Item count',                 'wc-order-customer-tagger')],
    'customer_ltv'              => ['type' => 'numeric', 'label' => __('Customer LTV',               'wc-order-customer-tagger')],
    'customer_order_count'      => ['type' => 'numeric', 'label' => __('Customer order count',       'wc-order-customer-tagger')],
    'days_since_last_order'     => ['type' => 'numeric', 'label' => __('Days since last order',      'wc-order-customer-tagger')],
    'customer_account_age_days' => ['type' => 'numeric', 'label' => __('Customer account age (days)','wc-order-customer-tagger')],
    'payment_status'            => ['type' => 'string',  'label' => __('Payment status',             'wc-order-customer-tagger')],
    'payment_method'            => ['type' => 'string',  'label' => __('Payment method',             'wc-order-customer-tagger')],
    'shipping_method'           => ['type' => 'string',  'label' => __('Shipping method',            'wc-order-customer-tagger')],
    'shipping_country'          => ['type' => 'string',  'label' => __('Shipping country',           'wc-order-customer-tagger')],
    'billing_country'           => ['type' => 'string',  'label' => __('Billing country',            'wc-order-customer-tagger')],
    'is_guest'                  => ['type' => 'boolean', 'label' => __('Is guest',                   'wc-order-customer-tagger')],
    'is_first_order'            => ['type' => 'boolean', 'label' => __('Is first order',             'wc-order-customer-tagger')],
    'has_coupon'                => ['type' => 'boolean', 'label' => __('Has coupon',                 'wc-order-customer-tagger')],
    'address_mismatch'          => ['type' => 'boolean', 'label' => __('Address mismatch',           'wc-order-customer-tagger')],
];

$operatorsByType = [
    'numeric' => [
        'gt'  => __('greater than (>)',          'wc-order-customer-tagger'),
        'gte' => __('greater than or equal (>=)', 'wc-order-customer-tagger'),
        'lt'  => __('less than (<)',              'wc-order-customer-tagger'),
        'lte' => __('less than or equal (<=)',    'wc-order-customer-tagger'),
        'eq'  => __('equals (=)',                 'wc-order-customer-tagger'),
    ],
    'string' => [
        'eq'       => __('equals',          'wc-order-customer-tagger'),
        'contains' => __('contains',        'wc-order-customer-tagger'),
        'in'       => __('in list',         'wc-order-customer-tagger'),
        'not_in'   => __('not in list',     'wc-order-customer-tagger'),
    ],
    'boolean' => [
        'is_true'  => __('is true',  'wc-order-customer-tagger'),
        'is_false' => __('is false', 'wc-order-customer-tagger'),
    ],
];
?>
<div class="wrap harper-tagger">
    <h1>
        <?php echo esc_html($title); ?>
        <span class="harper-tagger-subtitle">
            <?php
            /* translators: %s: tag name */
            printf(esc_html__('for tag: %s', 'wc-order-customer-tagger'), esc_html($tag['name']));
            ?>
        </span>
        <a href="<?php echo esc_url($backUrl); ?>" class="page-title-action">
            &larr; <?php esc_html_e('Back to Rules', 'wc-order-customer-tagger'); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php if ($errors): ?>
    <div class="notice notice-error">
        <ul><?php foreach ($errors as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" id="harper-rule-form"
          action="<?php echo esc_url(add_query_arg(['page' => 'harper-tagger-tags', 'action' => 'rule-save'], admin_url('admin.php'))); ?>">
        <?php wp_nonce_field('harper_tagger_save_rule'); ?>
        <input type="hidden" name="rule_id"  value="<?php echo esc_attr((string) $ruleId); ?>">
        <input type="hidden" name="tag_id"   value="<?php echo esc_attr((string) $tagId); ?>">
        <input type="hidden" name="conditions_json" id="harper-conditions-json" value="">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="harper-rule-label"><?php esc_html_e('Label', 'wc-order-customer-tagger'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="harper-rule-label" name="label" class="regular-text"
                           value="<?php echo esc_attr((string) $val['label']); ?>" maxlength="100" required>
                    <p class="description"><?php esc_html_e('Human-readable description, e.g. "Order total over $500".', 'wc-order-customer-tagger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logic', 'wc-order-customer-tagger'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="radio" name="operator" value="AND"
                                   <?php checked($val['operator'], 'AND'); ?>>
                            <?php esc_html_e('AND — all conditions must match', 'wc-order-customer-tagger'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="operator" value="OR"
                                   <?php checked($val['operator'], 'OR'); ?>>
                            <?php esc_html_e('OR — any condition must match', 'wc-order-customer-tagger'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="harper-rule-priority"><?php esc_html_e('Priority', 'wc-order-customer-tagger'); ?></label>
                </th>
                <td>
                    <input type="number" id="harper-rule-priority" name="priority" min="0" max="9999"
                           value="<?php echo esc_attr((string) (int) $val['priority']); ?>" class="small-text">
                    <p class="description"><?php esc_html_e('Lower numbers run first. Default: 10.', 'wc-order-customer-tagger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Active', 'wc-order-customer-tagger'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1"
                               <?php checked(!empty($val['is_active'])); ?>>
                        <?php esc_html_e('Enable this rule', 'wc-order-customer-tagger'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Conditions', 'wc-order-customer-tagger'); ?></h2>
        <p class="description">
            <?php esc_html_e('For "in list" / "not in list" operators, enter comma-separated values. Boolean operators need no value.', 'wc-order-customer-tagger'); ?>
        </p>

        <?php
        // Build JS field-type map for operator switching
        $fieldTypeMap = [];
        foreach ($fieldDefs as $key => $def) {
            $fieldTypeMap[$key] = $def['type'];
        }
        $fieldTypeMapJson = wp_json_encode($fieldTypeMap);
        $operatorsByTypeJson = wp_json_encode($operatorsByType);
        ?>

        <div id="harper-conditions-wrapper">
            <?php
            $conditionsToRender = !empty($existingConditions) ? $existingConditions : [['field' => '', 'operator' => '', 'value' => '']];
            foreach ($conditionsToRender as $idx => $cond):
                $condField = (string) ($cond['field']    ?? '');
                $condOp    = (string) ($cond['operator'] ?? '');
                // value may be array (from in/not_in) or string
                $condVal   = isset($cond['value']) && is_array($cond['value'])
                    ? implode(', ', $cond['value'])
                    : (string) ($cond['value'] ?? '');
                $condType  = isset($fieldDefs[$condField]) ? $fieldDefs[$condField]['type'] : 'numeric';
                $booleanOp = in_array($condOp, ['is_true', 'is_false'], true);
            ?>
            <div class="harper-condition-row" data-idx="<?php echo esc_attr((string) $idx); ?>">
                <select class="harper-cond-field" name="cond_field[]" aria-label="<?php esc_attr_e('Field', 'wc-order-customer-tagger'); ?>">
                    <option value=""><?php esc_html_e('— select field —', 'wc-order-customer-tagger'); ?></option>
                    <?php foreach ($fieldDefs as $key => $def): ?>
                    <option value="<?php echo esc_attr($key); ?>"
                            data-type="<?php echo esc_attr($def['type']); ?>"
                            <?php selected($condField, $key); ?>>
                        <?php echo esc_html($def['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select class="harper-cond-operator" name="cond_operator[]" aria-label="<?php esc_attr_e('Operator', 'wc-order-customer-tagger'); ?>">
                    <?php
                    // Output all operators grouped by current type so the selected one is visible
                    $allOpsForType = $operatorsByType[$condType] ?? $operatorsByType['numeric'];
                    foreach ($allOpsForType as $opKey => $opLabel): ?>
                    <option value="<?php echo esc_attr($opKey); ?>" <?php selected($condOp, $opKey); ?>>
                        <?php echo esc_html($opLabel); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="text"
                       class="harper-cond-value"
                       name="cond_value[]"
                       value="<?php echo esc_attr($condVal); ?>"
                       placeholder="<?php esc_attr_e('value', 'wc-order-customer-tagger'); ?>"
                       aria-label="<?php esc_attr_e('Value', 'wc-order-customer-tagger'); ?>"
                       <?php echo $booleanOp ? 'style="display:none"' : ''; ?>>

                <button type="button" class="button harper-remove-condition"
                        aria-label="<?php esc_attr_e('Remove condition', 'wc-order-customer-tagger'); ?>">
                    &times;
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <p>
            <button type="button" id="harper-add-condition" class="button button-secondary">
                <?php esc_html_e('+ Add Condition', 'wc-order-customer-tagger'); ?>
            </button>
        </p>

        <?php submit_button($isNew ? __('Create Rule', 'wc-order-customer-tagger') : __('Update Rule', 'wc-order-customer-tagger')); ?>
    </form>
</div>

<script type="application/json" id="harper-field-type-map"><?php echo $fieldTypeMapJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe ?></script>
<script type="application/json" id="harper-operators-by-type"><?php echo $operatorsByTypeJson; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe ?></script>
