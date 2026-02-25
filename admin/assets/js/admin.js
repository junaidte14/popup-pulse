/**
 * Popup Pulse — Admin JavaScript
 * Handles: tabs, conditional fields, color pickers, image upload, AJAX actions, preview, templates
 */

/* global ppulseAdmin, wp */
(function ($) {
    'use strict';

    var PP = {

        // ── Init ────────────────────────────────────────────────────────────
        init: function () {
            PP.initTabs();
            PP.initConditionals();
            PP.initColorPickers();
            PP.initRangeInputs();
            PP.initImageUploader();
            PP.initDeviceCards();
            PP.initTemplateActions();
            PP.initPreviewButton();
            PP.initSaveAsTemplate();
            PP.initListTableActions();
            PP.initSettingsForm();
        },

        // ── Tabs ─────────────────────────────────────────────────────────────
        initTabs: function () {
            var $nav = $('.ppulse-tabs__nav');
            if (!$nav.length) return;

            var storedTab = sessionStorage.getItem('ppulse_active_tab_' + (ppulseAdmin.postId || '0'));

            $nav.find('.ppulse-tabs__link').on('click', function (e) {
                e.preventDefault();
                PP.activateTab($(this).data('tab'));
            });

            // Activate stored or first tab
            var firstTab = $nav.find('.ppulse-tabs__link').first().data('tab');
            PP.activateTab(storedTab || firstTab);
        },

        activateTab: function (tabId) {
            var $nav = $('.ppulse-tabs__nav');
            $nav.find('.ppulse-tabs__link').removeClass('is-active').attr('aria-selected', 'false');
            $('.ppulse-tabs__panel').removeClass('is-active');

            var $link  = $nav.find('[data-tab="' + tabId + '"]');
            var $panel = $('#ppulse-tab-' + tabId);

            if (!$link.length || !$panel.length) {
                // fallback to first
                $link  = $nav.find('.ppulse-tabs__link').first();
                $panel = $('.ppulse-tabs__panel').first();
            }

            $link.addClass('is-active').attr('aria-selected', 'true');
            $panel.addClass('is-active');
            sessionStorage.setItem('ppulse_active_tab_' + (ppulseAdmin.postId || '0'), tabId);
        },

        // ── Conditional fields ────────────────────────────────────────────────
        initConditionals: function () {
            var $body = $(document);

            function updateConditionals() {
                $('.ppulse-conditional').each(function () {
                    var rule = $(this).data('show-when'); // e.g. "trigger_type=delay,pageload"
                    if (!rule) return;

                    var parts    = rule.split('=');
                    var fieldKey = parts[0].trim();
                    var values   = (parts[1] || '').split(',').map(function (v) { return v.trim(); });

                    var $field = $('[name="ppulse_' + fieldKey + '"], #ppulse_' + fieldKey);
                    var currentVal;

                    if ($field.is(':checkbox')) {
                        currentVal = $field.is(':checked') ? '1' : '0';
                    } else {
                        currentVal = $field.val();
                    }

                    if (values.indexOf(currentVal) !== -1) {
                        $(this).addClass('is-visible').show();
                    } else {
                        $(this).removeClass('is-visible').hide();
                    }
                });
            }

            // Run on init
            updateConditionals();

            // Re-run on any input change inside the meta box
            $body.on('change input', '.ppulse-meta-wrap select, .ppulse-meta-wrap input', function () {
                updateConditionals();
            });

            // Also handle the display_pages radio + specific IDs field
            $body.on('change', '[name="ppulse_display_pages"]', function () {
                var val = $(this).val();
                var $ids = $('[name="ppulse_display_pages_ids"]').closest('.ppulse-conditional');
                if (val === 'specific') {
                    $ids.addClass('is-visible').show();
                } else {
                    $ids.removeClass('is-visible').hide();
                }
            });
            // Init display_pages conditionals
            var $checked = $('[name="ppulse_display_pages"]:checked');
            if ($checked.val() === 'specific') {
                $('[name="ppulse_display_pages_ids"]').closest('.ppulse-conditional').addClass('is-visible').show();
            }
        },

        // ── Color pickers ──────────────────────────────────────────────────────
        initColorPickers: function () {
            if (typeof $.fn.wpColorPicker === 'undefined') return;
            $('.ppulse-color-picker').wpColorPicker({
                change: function () {
                    setTimeout(function () { PP.updateInlinePreview(); }, 50);
                }
            });
        },

        // ── Range inputs ───────────────────────────────────────────────────────
        initRangeInputs: function () {
            $(document).on('input change', '.ppulse-range', function () {
                $(this).closest('.ppulse-range-wrap').find('.ppulse-range-val').text($(this).val());
            });
        },

        // ── Image uploader ─────────────────────────────────────────────────────
        initImageUploader: function () {
            var mediaUploader;

            $(document).on('click', '.ppulse-upload-image', function (e) {
                e.preventDefault();
                var $btn     = $(this);
                var target   = $btn.data('target');
                var idTarget = $btn.data('id-target');
                var preview  = $btn.data('preview');

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title:    ppulseAdmin.strings && ppulseAdmin.strings.chooseImage || 'Choose Image',
                    button:   { text: 'Use This Image' },
                    multiple: false,
                    library:  { type: 'image' }
                });

                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + target).val(attachment.url);
                    $('#' + idTarget).val(attachment.id);
                    var $preview = $(preview);
                    $preview.addClass('has-image').html('<img src="' + attachment.url + '" alt="" />');
                    // Show remove button
                    if (!$btn.siblings('.ppulse-remove-image').length) {
                        $btn.after('<button type="button" class="button ppulse-remove-image">Remove</button>');
                    }
                });

                mediaUploader.open();
            });

            $(document).on('click', '.ppulse-remove-image', function (e) {
                e.preventDefault();
                var $wrap    = $(this).closest('.ppulse-image-uploader');
                var $preview = $wrap.find('.ppulse-image-preview');
                $preview.removeClass('has-image').html('');
                $wrap.find('input[type="hidden"]').val('');
                $(this).remove();
            });
        },

        // ── Device cards ───────────────────────────────────────────────────────
        initDeviceCards: function () {
            $(document).on('change', '.ppulse-device-card input', function () {
                var $card = $(this).closest('.ppulse-device-card');
                if ($(this).is(':checked')) {
                    $card.addClass('is-checked');
                } else {
                    $card.removeClass('is-checked');
                }
            });
        },

        // ── Template actions ───────────────────────────────────────────────────
        initTemplateActions: function () {
            $(document).on('click', '.ppulse-apply-template', function (e) {
                e.preventDefault();
                var $btn    = $(this);
                var tplId   = $btn.data('tpl-id');
                var postId  = $btn.data('post-id') || ppulseAdmin.postId;

                if (!confirm(ppulseAdmin.strings.confirmApply)) return;

                $btn.prop('disabled', true).text(ppulseAdmin.strings.saving);

                $.post(ppulseAdmin.ajaxUrl, {
                    action:      'ppulse_apply_template',
                    nonce:       ppulseAdmin.nonce,
                    post_id:     postId,
                    template_id: tplId
                }, function (res) {
                    $btn.prop('disabled', false).text('Apply');
                    if (res.success) {
                        PP.showToast(res.data.message, 'success');
                        // Reload to reflect new meta values
                        setTimeout(function () { window.location.reload(); }, 800);
                    } else {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                    }
                }).fail(function () {
                    $btn.prop('disabled', false).text('Apply');
                    PP.showToast(ppulseAdmin.strings.error, 'error');
                });
            });
        },

        // ── Preview button ─────────────────────────────────────────────────────
        initPreviewButton: function () {
            $(document).on('click', '.ppulse-btn-preview', function (e) {
                e.preventDefault();
                var postId = $(this).data('post-id') || ppulseAdmin.postId;
                if (!postId) return;

                $.post(ppulseAdmin.ajaxUrl, {
                    action:  'ppulse_get_popup_data',
                    nonce:   ppulseAdmin.nonce,
                    post_id: postId
                }, function (res) {
                    if (!res.success) {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                        return;
                    }
                    PP.openAdminPreview(res.data);
                }).fail(function () {
                    PP.showToast(ppulseAdmin.strings.error, 'error');
                });
            });
        },

        openAdminPreview: function (data) {
            var $backdrop = $('<div class="ppulse-admin-preview-backdrop"></div>');
            var $dialog   = $(
                '<div class="ppulse-admin-preview-dialog" role="dialog" aria-modal="true">' +
                    '<div class="ppulse-admin-preview-dialog__bar">' +
                        '<span>Preview: ' + $('<span>').text(data.title).html() + '</span>' +
                        '<button class="ppulse-admin-preview-dialog__close" type="button" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<div class="ppulse-admin-preview-dialog__body">' +
                        data.content +
                    '</div>' +
                '</div>'
            );

            $backdrop.append($dialog).appendTo('body');

            // Apply background color from meta
            if (data.meta && data.meta.popup_bg_color) {
                $dialog.css('background-color', data.meta.popup_bg_color);
            }

            $backdrop.on('click', function (e) {
                if ($(e.target).is($backdrop) || $(e.target).is('.ppulse-admin-preview-dialog__close')) {
                    $backdrop.remove();
                }
            });

            $(document).on('keydown.ppulse-preview', function (e) {
                if (e.key === 'Escape') {
                    $backdrop.remove();
                    $(document).off('keydown.ppulse-preview');
                }
            });
        },

        // ── Save as template ───────────────────────────────────────────────────
        initSaveAsTemplate: function () {
            $(document).on('click', '.ppulse-btn-save-template', function (e) {
                e.preventDefault();
                var postId = $(this).data('post-id') || ppulseAdmin.postId;
                var name   = prompt(ppulseAdmin.strings.templateName);
                if (!name) return;

                $.post(ppulseAdmin.ajaxUrl, {
                    action:        'ppulse_save_as_template',
                    nonce:         ppulseAdmin.nonce,
                    post_id:       postId,
                    template_name: name
                }, function (res) {
                    if (res.success) {
                        PP.showToast(ppulseAdmin.strings.templateSaved, 'success');
                    } else {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                    }
                });
            });
        },

        // ── List table row actions ─────────────────────────────────────────────
        initListTableActions: function () {
            // Toggle status
            $(document).on('click', '.ppulse-row-toggle', function (e) {
                e.preventDefault();
                var $link  = $(this);
                var postId = $link.data('post-id');

                $.post(ppulseAdmin.ajaxUrl, {
                    action:  'ppulse_toggle_status',
                    nonce:   ppulseAdmin.nonce,
                    post_id: postId
                }, function (res) {
                    if (res.success) {
                        var $badge = $link.closest('tr').find('.ppulse-badge').first();
                        $badge.removeClass('ppulse-badge--on ppulse-badge--off');
                        if (res.data.enabled) {
                            $badge.addClass('ppulse-badge--on').text(res.data.label);
                        } else {
                            $badge.addClass('ppulse-badge--off').text(res.data.label);
                        }
                        PP.showToast(res.data.enabled ? ppulseAdmin.strings.active : ppulseAdmin.strings.disabled, 'success');
                    } else {
                        PP.showToast(ppulseAdmin.strings.error, 'error');
                    }
                });
            });

            // Duplicate
            $(document).on('click', '.ppulse-row-duplicate', function (e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                $.post(ppulseAdmin.ajaxUrl, {
                    action:  'ppulse_duplicate_popup',
                    nonce:   ppulseAdmin.nonce,
                    post_id: postId
                }, function (res) {
                    if (res.success) {
                        PP.showToast(res.data.message, 'success');
                        setTimeout(function () { window.location.reload(); }, 700);
                    } else {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                    }
                });
            });

            // Delete (inline)
            $(document).on('click', '.ppulse-row-delete', function (e) {
                e.preventDefault();
                if (!confirm(ppulseAdmin.strings.confirmDelete)) return;
                var $row   = $(this).closest('tr');
                var postId = $(this).data('post-id');
                $.post(ppulseAdmin.ajaxUrl, {
                    action:  'ppulse_delete_popup',
                    nonce:   ppulseAdmin.nonce,
                    post_id: postId
                }, function (res) {
                    if (res.success) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                        PP.showToast(res.data.message, 'success');
                    } else {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                    }
                });
            });

            // Add row actions via post_row_actions filter not available in JS,
            // so we insert inline if on list table
            PP.injectListTableActions();
        },

        injectListTableActions: function () {
            if (!$('body').hasClass('post-type-ppulse_popup') || !$('#the-list').length) return;
            if ($('#the-list').closest('.wp-list-table').length === 0) return;

            $('#the-list tr').each(function () {
                var $row   = $(this);
                var postId = $row.find('.check-column input').val();
                if (!postId) return;

                var $actions = $row.find('.row-actions');
                if (!$actions.length) return;

                // Remove existing WP trash link and inject ours
                var $existing = $actions.children('span');
                var html = '';
                html += '<span class="ppulse-toggle"><a href="#" class="ppulse-row-toggle" data-post-id="' + postId + '">Toggle</a> | </span>';
                html += '<span class="ppulse-duplicate"><a href="#" class="ppulse-row-duplicate" data-post-id="' + postId + '">Duplicate</a></span>';

                $actions.append(html);
            });
        },

        // ── Settings form ──────────────────────────────────────────────────────
        initSettingsForm: function () {
            $('#ppulse-settings-form').on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $btn  = $form.find('.ppulse-save-settings-btn');
                var $fb   = $form.find('.ppulse-save-feedback');

                $btn.prop('disabled', true).text(ppulseAdmin.strings.saving);

                var data = $form.serializeArray();
                data.push({ name: 'action', value: 'ppulse_save_settings' });
                data.push({ name: 'nonce',  value: ppulseAdmin.nonce });

                // Checkboxes need explicit 0 if unchecked
                ['disable_on_mobile','disable_on_tablet','respect_dnt','load_fontawesome'].forEach(function (key) {
                    if (!$form.find('[name="' + key + '"]').is(':checked')) {
                        data.push({ name: key, value: '0' });
                    }
                });

                $.post(ppulseAdmin.ajaxUrl, data, function (res) {
                    $btn.prop('disabled', false).text('Save Settings');
                    if (res.success) {
                        $fb.show().delay(2500).fadeOut();
                    } else {
                        PP.showToast(res.data.message || ppulseAdmin.strings.error, 'error');
                    }
                }).fail(function () {
                    $btn.prop('disabled', false).text('Save Settings');
                    PP.showToast(ppulseAdmin.strings.error, 'error');
                });
            });
        },

        // ── Toast notifications ────────────────────────────────────────────────
        showToast: function (message, type) {
            var $toast = $('<div class="ppulse-toast ppulse-toast--' + (type || 'success') + '">' + message + '</div>');
            $('body').append($toast);
            setTimeout(function () {
                $toast.addClass('is-hiding');
                setTimeout(function () { $toast.remove(); }, 250);
            }, 3000);
        },

        // ── Inline preview color update ────────────────────────────────────────
        updateInlinePreview: function () {
            // placeholder for future live CSS preview
        }
    };

    // ── DOM Ready ────────────────────────────────────────────────────────────
    $(function () {
        PP.init();
    });

}(jQuery));
