/* CM Tagger — admin JS: colour picker + live badge preview + rule condition builder */
jQuery(function ($) {

    // ── Live name → badge preview ─────────────────────────────────────────────
    // Bound first so a color-picker init failure can't suppress it.
    $('#cm-tag-name').on('input', function () {
        $('#cm-badge-preview').text($(this).val() || 'Preview');
    });

    // ── WP colour picker ──────────────────────────────────────────────────────
    // Guard with try/catch: headless browsers may not support the canvas-based
    // picker; the name-preview listener must survive regardless.
    try {
        if ($.fn.wpColorPicker) {
            $('.cm-color-picker').wpColorPicker({
                change: function (event, ui) {
                    updatePreview(ui.color.toString());
                },
                clear: function () {
                    updatePreview('#3788d8');
                },
            });
        }
    } catch (e) {
        // Color picker unavailable (e.g. headless CI) — badge background stays as-is.
    }

    function updatePreview(color) {
        $('#cm-badge-preview').css('background', color);
    }

    // ── Rule condition builder ────────────────────────────────────────────────

    var $wrapper    = $('#cm-conditions-wrapper');
    var $form       = $('#cm-rule-form');
    var $jsonInput  = $('#cm-conditions-json');

    if (!$wrapper.length) {
        return; // Not on the rule edit page — stop here.
    }

    // Parse the field type map and operators injected by PHP
    var fieldTypeMap    = {};
    var operatorsByType = {};
    try {
        var ftEl = document.getElementById('cm-field-type-map');
        var obEl = document.getElementById('cm-operators-by-type');
        if (ftEl) fieldTypeMap    = JSON.parse(ftEl.textContent || ftEl.innerText || '{}');
        if (obEl) operatorsByType = JSON.parse(obEl.textContent || obEl.innerText || '{}');
    } catch (e) {
        // Fallback: use empty maps — UI degrades gracefully.
    }

    // ── Add condition row ─────────────────────────────────────────────────────

    $('#cm-add-condition').on('click', function () {
        addConditionRow();
    });

    function addConditionRow() {
        // Clone the first row as a template, then clear its values
        var $existing = $wrapper.find('.cm-condition-row').first();
        var $row;

        if ($existing.length) {
            $row = $existing.clone();
            $row.find('.cm-cond-field').val('');
            $row.find('.cm-cond-operator').empty();
            $row.find('.cm-cond-value').val('').show();
        } else {
            $row = buildBlankRow();
        }

        var newIdx = $wrapper.find('.cm-condition-row').length;
        $row.attr('data-idx', newIdx);
        $wrapper.append($row);
        wireRow($row);
        // Trigger a field change so operators populate correctly
        $row.find('.cm-cond-field').trigger('change');
    }

    // ── Remove condition row ──────────────────────────────────────────────────

    $wrapper.on('click', '.cm-remove-condition', function () {
        var $rows = $wrapper.find('.cm-condition-row');
        if ($rows.length <= 1) {
            return; // Keep at least one row
        }
        $(this).closest('.cm-condition-row').remove();
        renumberRows();
    });

    // ── Wire a row: field change → operator update ────────────────────────────

    function wireRow($row) {
        $row.find('.cm-cond-field').on('change', function () {
            var field    = $(this).val();
            var type     = fieldTypeMap[field] || 'numeric';
            var $opSel   = $row.find('.cm-cond-operator');
            var prevOp   = $opSel.val();
            var ops      = operatorsByType[type] || {};

            $opSel.empty();
            $.each(ops, function (key, label) {
                var $opt = $('<option>').val(key).text(label);
                if (key === prevOp) $opt.prop('selected', true);
                $opSel.append($opt);
            });

            updateValueVisibility($row);
        });

        $row.find('.cm-cond-operator').on('change', function () {
            updateValueVisibility($row);
        });
    }

    function updateValueVisibility($row) {
        var op        = $row.find('.cm-cond-operator').val();
        var $valInput = $row.find('.cm-cond-value');
        if (op === 'is_true' || op === 'is_false') {
            $valInput.val('').hide();
        } else {
            $valInput.show();
        }
    }

    // ── Wire existing rows on page load ──────────────────────────────────────

    $wrapper.find('.cm-condition-row').each(function () {
        wireRow($(this));
    });

    // ── Re-index row data attributes after removal ───────────────────────────

    function renumberRows() {
        $wrapper.find('.cm-condition-row').each(function (i) {
            $(this).attr('data-idx', i);
        });
    }

    // ── Build a blank row without DOM cloning ─────────────────────────────────

    function buildBlankRow() {
        var $row = $('<div class="cm-condition-row">');

        var $fieldSel = $('<select class="cm-cond-field" name="cond_field[]">')
            .append($('<option value="">').text('— select field —'));

        $.each(fieldTypeMap, function (key) {
            $fieldSel.append($('<option>').val(key).attr('data-type', fieldTypeMap[key]).text(key));
        });

        var $opSel  = $('<select class="cm-cond-operator" name="cond_operator[]">');
        var $valIn  = $('<input type="text" class="cm-cond-value" name="cond_value[]" placeholder="value">');
        var $removeBtn = $('<button type="button" class="button cm-remove-condition">').html('&times;');

        $row.append($fieldSel, $opSel, $valIn, $removeBtn);
        return $row;
    }

    // ── Serialize conditions into hidden JSON field on submit ─────────────────

    $form.on('submit', function () {
        var conditions = [];

        $wrapper.find('.cm-condition-row').each(function () {
            var field    = $(this).find('.cm-cond-field').val();
            var operator = $(this).find('.cm-cond-operator').val();
            var value    = $(this).find('.cm-cond-value').val();

            if (!field || !operator) {
                return; // skip incomplete rows
            }

            // For in/not_in, the value field is a comma-separated string.
            // We keep it as-is here; the PHP handler splits it.
            conditions.push({ field: field, operator: operator, value: value });
        });

        $jsonInput.val(JSON.stringify(conditions));
    });

});
