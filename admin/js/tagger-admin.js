/* Harper Tagger — admin JS: colour picker + live badge preview */
jQuery(function ($) {

    // ── Live name → badge preview ─────────────────────────────────────────────
    // Bound first so a color-picker init failure can't suppress it.
    $('#harper-tag-name').on('input', function () {
        $('#harper-badge-preview').text($(this).val() || 'Preview');
    });

    // ── WP colour picker ──────────────────────────────────────────────────────
    // Guard with try/catch: headless browsers may not support the canvas-based
    // picker; the name-preview listener must survive regardless.
    try {
        if ($.fn.wpColorPicker) {
            $('.harper-color-picker').wpColorPicker({
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
        $('#harper-badge-preview').css('background', color);
    }
});
