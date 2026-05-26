/* Harper Tagger — admin JS: colour picker + live badge preview */
jQuery(function ($) {
    // Activate WP colour picker on the color field
    $('.harper-color-picker').wpColorPicker({
        change: function (event, ui) {
            updatePreview(ui.color.toString());
        },
        clear: function () {
            updatePreview('#3788d8');
        }
    });

    // Live name → badge preview
    $('#harper-tag-name').on('input', function () {
        $('#harper-badge-preview').text($(this).val() || 'Preview');
    });

    function updatePreview(color) {
        $('#harper-badge-preview').css('background', color);
    }
});
