/**
 * Harper Tagger — inline tag editing popover for Orders and Customers grids.
 *
 * Click any Tags cell → popover opens showing applied tags (with × remove)
 * and available tags to add. All changes are saved via AJAX immediately.
 */
(function ($) {
    'use strict';

    var cfg         = window.harperTaggerPopover || {};
    var ajaxUrl     = cfg.ajaxUrl  || '';
    var nonce       = cfg.nonce    || '';
    var i18n        = cfg.i18n    || {};

    var allTags     = null;   // Cached: [{id, name, color, image_url}, …]
    var $popover    = null;
    var ctx         = null;   // { type: 'order'|'customer', id: int, $cell: jQuery }

    // ── Boot ──────────────────────────────────────────────────────────────────

    $(function () {
        buildPopover();
        bindCellClicks();
        bindOutsideClick();
        bindEscKey();
    });

    // ── Build the popover DOM once ────────────────────────────────────────────

    function buildPopover() {
        $popover = $(
            '<div id="harper-tag-popover" role="dialog" aria-modal="true">' +
                '<div class="htp-header">' +
                    '<span class="htp-title"></span>' +
                    '<button class="htp-close" aria-label="Close">&times;</button>' +
                '</div>' +
                '<div class="htp-section">' +
                    '<div class="htp-section-label"></div>' +
                    '<div class="htp-applied"></div>' +
                '</div>' +
                '<div class="htp-section">' +
                    '<div class="htp-section-label"></div>' +
                    '<div class="htp-available"></div>' +
                '</div>' +
                '<div class="htp-status"></div>' +
            '</div>'
        );

        $('body').append($popover);

        $popover.on('click', '.htp-close', closePopover);

        // Remove tag
        $popover.on('click', '.htp-remove', function () {
            var tagId = parseInt($(this).data('tag-id'), 10);
            saveTag('remove', tagId);
        });

        // Add tag
        $popover.on('click', '.htp-add-badge', function () {
            var tagId = parseInt($(this).data('tag-id'), 10);
            saveTag('add', tagId);
        });
    }

    // ── Cell click → open popover ─────────────────────────────────────────────

    function bindCellClicks() {
        // Delegate from body — works for dynamically loaded rows (HPOS infinite scroll)
        $(document).on('click', '.harper-tagger-cell', function (e) {
            e.stopPropagation();

            var $cell = $(this);
            var type  = $cell.data('type');   // 'order' or 'customer'
            var id    = parseInt($cell.data('id'), 10);

            if (!type || !id) return;

            // Toggle: clicking the same cell again closes
            if ($popover.is(':visible') && ctx && ctx.$cell.is($cell)) {
                closePopover();
                return;
            }

            ctx = { type: type, id: id, $cell: $cell };

            if (allTags === null) {
                setStatus(i18n.saving || 'Loading…');
                showPopover($cell);
                $.post(ajaxUrl, { action: 'harper_tagger_get_all_tags', nonce: nonce })
                    .done(function (resp) {
                        if (resp.success) {
                            allTags = resp.data.tags;
                            renderPopover();
                        }
                    });
            } else {
                renderPopover();
                showPopover($cell);
            }
        });
    }

    // ── Render popover contents ───────────────────────────────────────────────

    function renderPopover() {
        if (!ctx) return;

        var appliedIds  = getAppliedIdsFromCell(ctx.$cell);
        var applied     = (allTags || []).filter(function (t) { return appliedIds.indexOf(parseInt(t.id, 10)) !== -1; });
        var available   = (allTags || []).filter(function (t) { return appliedIds.indexOf(parseInt(t.id, 10)) === -1; });
        var objectLabel = ctx.type === 'order' ? 'Order' : 'Customer';

        // Header
        $popover.find('.htp-title').text(objectLabel + ' #' + ctx.id + ' — Tags');

        // Applied section
        var $sections    = $popover.find('.htp-section');
        var $appliedSec  = $sections.eq(0);
        var $availSec    = $sections.eq(1);

        $appliedSec.find('.htp-section-label').text(i18n.applied || 'Applied');
        var $appliedWrap = $appliedSec.find('.htp-applied').empty();

        if (applied.length === 0) {
            $appliedWrap.append('<span class="htp-empty">—</span>');
        } else {
            applied.forEach(function (tag) {
                $appliedWrap.append(buildAppliedBadge(tag));
            });
        }

        // Available section
        $availSec.find('.htp-section-label').text(i18n.add || 'Add tag');
        var $availWrap = $availSec.find('.htp-available').empty();

        if ((allTags || []).length === 0) {
            $availWrap.append('<span class="htp-empty">' + escHtml(i18n.noTags || 'No tags defined yet.') + '</span>');
        } else if (available.length === 0) {
            $availWrap.append('<span class="htp-empty">' + escHtml(i18n.allApplied || 'All tags applied.') + '</span>');
        } else {
            available.forEach(function (tag) {
                $availWrap.append(buildAvailBadge(tag));
            });
        }

        clearStatus();
        showPopover(ctx.$cell);
    }

    // ── Badge builders ────────────────────────────────────────────────────────

    function buildAppliedBadge(tag) {
        var $wrap = $('<span class="htp-applied-tag">');

        if (tag.image_url) {
            $wrap.append(
                $('<img class="htp-icon">').attr('src', tag.image_url).attr('alt', tag.name).attr('title', tag.name)
            );
        } else {
            $wrap.append(
                $('<span class="harper-tagger-badge htp-badge-label">')
                    .css('background', tag.color)
                    .text(tag.name)
            );
        }

        $wrap.append(
            $('<button class="htp-remove" type="button">')
                .attr('title', 'Remove ' + tag.name)
                .attr('data-tag-id', tag.id)
                .html('&times;')
        );

        return $wrap;
    }

    function buildAvailBadge(tag) {
        if (tag.image_url) {
            return $('<img class="htp-icon htp-add-badge">')
                .attr('src', tag.image_url)
                .attr('alt', tag.name)
                .attr('title', 'Add: ' + tag.name)
                .attr('data-tag-id', tag.id)
                .css('cursor', 'pointer');
        }
        return $('<span class="harper-tagger-badge htp-add-badge">')
            .css('background', tag.color)
            .css('cursor', 'pointer')
            .attr('title', 'Add: ' + tag.name)
            .attr('data-tag-id', tag.id)
            .text(tag.name);
    }

    // ── AJAX: add / remove ────────────────────────────────────────────────────

    function saveTag(action, tagId) {
        if (!ctx) return;

        setStatus(i18n.saving || 'Saving…');

        $.post(ajaxUrl, {
            action:    'harper_tagger_' + action + '_tag',
            nonce:     nonce,
            type:      ctx.type,
            object_id: ctx.id,
            tag_id:    tagId,
        })
        .done(function (resp) {
            if (resp.success) {
                updateCell(resp.data.tags);
                renderPopover();
            } else {
                setStatus(i18n.error || 'Error saving tag. Please try again.', true);
            }
        })
        .fail(function () {
            setStatus(i18n.error || 'Error saving tag. Please try again.', true);
        });
    }

    // ── Update the cell HTML after a save ─────────────────────────────────────

    function updateCell(tags) {
        if (!ctx) return;

        var $cell = ctx.$cell;

        if (!tags || tags.length === 0) {
            $cell.html('<span class="harper-tagger-no-tags">—</span>');
            return;
        }

        var html = '<div class="harper-tagger-column-tags">';
        tags.forEach(function (tag) {
            if (tag.image_url) {
                html += '<img src="' + escAttr(tag.image_url) + '"'
                      + ' alt="' + escAttr(tag.name) + '"'
                      + ' title="' + escAttr(tag.name) + '"'
                      + ' class="harper-tagger-icon-preview">';
            } else {
                html += '<span class="harper-tagger-badge"'
                      + ' style="background:' + escAttr(tag.color) + '"'
                      + ' title="' + escAttr(tag.name) + '">'
                      + escHtml(tag.name)
                      + '</span>';
            }
        });
        html += '</div>';

        $cell.html(html);
    }

    // ── Get applied tag IDs from the current cell DOM ─────────────────────────

    function getAppliedIdsFromCell($cell) {
        // After a save, the cell holds badges/icons with title= matching tag names.
        // Simpler: compare against allTags by name match in the cell text/alt.
        // More robust: the AJAX response always gives us the fresh list — use
        // what's already rendered in the cell as a hint, but re-derive from DOM.
        var ids = [];
        if (!allTags) return ids;

        // Collect names from rendered badges and icon alts
        var names = [];
        $cell.find('.harper-tagger-badge').each(function () {
            names.push($(this).text().trim());
        });
        $cell.find('img.harper-tagger-icon-preview').each(function () {
            names.push($(this).attr('alt') || '');
        });

        allTags.forEach(function (t) {
            if (names.indexOf(t.name) !== -1) {
                ids.push(parseInt(t.id, 10));
            }
        });
        return ids;
    }

    // ── Positioning ───────────────────────────────────────────────────────────

    function showPopover($cell) {
        $popover.show();

        var cellRect  = $cell[0].getBoundingClientRect();
        var scrollTop = $(window).scrollTop();
        var scrollLeft= $(window).scrollLeft();

        var top  = cellRect.bottom + scrollTop + 6;
        var left = cellRect.left  + scrollLeft;

        // Flip left if popover would overflow viewport right edge
        var popWidth = $popover.outerWidth() || 300;
        if (left + popWidth > $(window).width() - 20) {
            left = Math.max(10, cellRect.right + scrollLeft - popWidth);
        }

        $popover.css({ top: top, left: left });
    }

    // ── Close ─────────────────────────────────────────────────────────────────

    function closePopover() {
        $popover.hide();
        ctx = null;
    }

    function bindOutsideClick() {
        $(document).on('click', function (e) {
            if (!$popover || !$popover.is(':visible')) return;
            if ($popover.is(e.target) || $popover.has(e.target).length) return;
            closePopover();
        });
    }

    function bindEscKey() {
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $popover && $popover.is(':visible')) {
                closePopover();
            }
        });
    }

    // ── Status bar ────────────────────────────────────────────────────────────

    function setStatus(msg, isError) {
        if (!$popover) return;
        $popover.find('.htp-status')
            .text(msg)
            .toggleClass('htp-status-error', !!isError)
            .show();
    }

    function clearStatus() {
        if (!$popover) return;
        $popover.find('.htp-status').hide().text('').removeClass('htp-status-error');
    }

    // ── Escape helpers ────────────────────────────────────────────────────────

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return escHtml(str);
    }

})(jQuery);
