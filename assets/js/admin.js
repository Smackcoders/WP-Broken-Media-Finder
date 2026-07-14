/* jshint esversion: 6 */
/* global jQuery, wpbmf_admin, wp */
(function ($) {
    'use strict';

    // Pro tab click — show upgrade modal ONLY
    $(document).on('click', '.wpbmf-pro-tab, .wpbmf-pro-link', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        // Determine which feature was clicked
        var featureName = $(this).text().replace('PRO', '').trim();
        var msg = 'I would like to request an upgrade to Pro to use the ' + featureName + ' feature.';
        
        // Reset form and set message
        $('#wpbmf-pro-request-form')[0].reset();
        $('#wpbmf_pro_req_feature').val(featureName);
        $('#wpbmf_pro_req_msg').val(msg);
        
        // Show promo view, hide form view
        $('#wpbmf-pro-form-view').hide();
        $('#wpbmf-pro-promo-view').show();

        $('#wpbmf-pro-modal').addClass('is-visible');
    });

    $('#wpbmf-show-form-btn').on('click', function(e) {
        e.preventDefault();
        $('#wpbmf-pro-promo-view').hide();
        $('#wpbmf-pro-form-view').fadeIn(200);
    });

    $('.wslh-modal-close, #wpbmf-pro-modal-backdrop, #wpbmf-pro-modal-close-icon').on('click', function() {
        $('#wpbmf-pro-modal').removeClass('is-visible');
    });

    // Pro form submit via AJAX
    $(document).on('submit', '#wpbmf-pro-request-form', function(e) {
        e.preventDefault();
        
        var $btn = $('#wpbmf-pro-btn-submit');
        $btn.prop('disabled', true).text('Submitting...');
        
        var data = {
            action: 'wpbmf_submit_pro_request',
            nonce: wpbmf_admin.batch_nonce, // Re-using batch_nonce for simplicity or generic nonce if exists
            name: $('#wpbmf_pro_req_name').val(),
            email: $('#wpbmf_pro_req_email').val(),
            message: $('#wpbmf_pro_req_msg').val()
        };
        
        $.post(wpbmf_admin.ajax_url, data)
            .done(function(res) {
                // On success, hide modal and show success alert
                $('#wpbmf-pro-modal').removeClass('is-visible');
                alert('Request submitted successfully! We will get back to you soon.');
                $('#wpbmf-pro-request-form')[0].reset();
            })
            .always(function() {
                $btn.prop('disabled', false).text('Submit Request');
            });
    });

    // Tab navigation
    $(document).on('click', '.wpbmf-tab-link', function (e) {
        if ($(this).hasClass('wpbmf-pro-tab')) {
            return;
        }
        e.preventDefault();
        var target = $(this).attr('href');
        $('.wpbmf-tab-link').removeClass('active');
        $('.wpbmf-tab-panel').removeClass('active');
        $(this).addClass('active');
        $(target).addClass('active');
    });

    // Pro modal close
    $(document).on('click', '#wpbmf-pro-modal-backdrop, #wpbmf-pro-modal-close, #wpbmf-pro-modal-close-icon', function () {
        $('#wpbmf-pro-modal').removeClass('is-visible');
    });

    // Toggle clear panel
    $('#wpbmf-toggle-clear').on('click', function () {
        $('#wpbmf-clear-panel').toggle();
    });

    // Confirm destructive actions
    $(document).on('click', '.wpbmf-confirm-clear', function (e) {
        if (!confirm(wpbmf_admin.confirm_clear)) { e.preventDefault(); }
    });
    $(document).on('click', '.wpbmf-confirm-replace', function (e) {
        e.preventDefault();
        if (!confirm(wpbmf_admin.confirm_replace)) { return false; }
        
        var $btn = $(this);
        var $form = $btn.closest('form');
        var resultId = $form.find('input[name="result_id"]').val();
        var nonce = $form.find('input[name="_wpnonce"]').val();
        
        $btn.prop('disabled', true).text('Replacing...');
        
        $.ajax({
            url: wpbmf_admin.ajax_url,
            method: 'POST',
            data: {
                action: 'wpbmf_replace_placeholder',
                nonce: nonce,
                result_id: resultId
            }
        }).done(function(res) {
            if (res.success) {
                alert(res.data.message || 'Image replaced with placeholder.');
                window.location.reload();
            } else {
                alert(res.data.message || 'Error replacing placeholder.');
                $btn.prop('disabled', false).text('Use Placeholder');
            }
        }).fail(function() {
            alert('Network error. Please try again.');
            $btn.prop('disabled', false).text('Use Placeholder');
        });
    });

    // Media uploader for placeholder
    $('#wpbmf_upload_placeholder').on('click', function (e) {
        e.preventDefault();
        if (!wp || !wp.media) return;
        var frame = wp.media({ title: 'Select Placeholder Image', button: { text: 'Use this image' }, multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#wpbmf_custom_ph').val(attachment.url);
        });
        frame.open();
    });

    // Toggle Custom URL row visibility
    $('input[name="wpbmf_settings[placeholder_source]"]').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#wpbmf_custom_url_row').show();
            $('#wpbmf_custom_ph').val(''); // Clear old link so user has to select a new one
        } else {
            $('#wpbmf_custom_url_row').hide();
        }
    });

    // ── Batch AJAX scan ──────────────────────────────────────────────────────
    var $scanForm = $('form[data-wpbmf-scan]');
    var $scanBtn = $scanForm.find('button[name="wpbmf_run_scan"]');
    var $progressWrap = $('#wpbmf-scan-progress');
    var $progressBar = $('#wpbmf-scan-progress-bar');
    var $progressMsg = $('#wpbmf-scan-progress-msg');

    var steps = ['content_images', 'attachment_links', 'featured_images', 'unused_media'];
    var stepLabels = {
        content_images: 'Scanning content images...',
        attachment_links: 'Scanning attachment links...',
        featured_images: 'Scanning featured images...',
        unused_media: 'Scanning unused media...'
    };

    function setProgress(pct, msg) {
        $progressBar.css('width', pct + '%');
        $progressMsg.text(msg);
    }

    function runStep(scanId, stepIndex) {
        if (stepIndex >= steps.length) {
            setProgress(90, 'Finalising...');
            $.ajax({
                url: wpbmf_admin.ajax_url,
                method: 'POST',
                timeout: 30000,
                data: {
                    action: 'wpbmf_finish_scan',
                    nonce: wpbmf_admin.batch_nonce,
                    scan_id: scanId
                }
            }).done(function (res) {
                if (res.success) {
                    setProgress(100, 'Scan complete! ' + (res.data.total || 0) + ' issue(s) found.');
                    setTimeout(function () {
                        window.location.href = wpbmf_admin.dashboard_url + '&wpbmf_msg=scanned&total=' + (res.data.total || 0);
                    }, 1200);
                } else {
                    setProgress(100, 'Error finishing scan.');
                    $scanBtn.prop('disabled', false).text('Run New Scan');
                }
            }).fail(function () {
                setProgress(100, 'Network error finishing scan.');
                $scanBtn.prop('disabled', false).text('Run New Scan');
            });
            return;
        }

        var step = steps[stepIndex];
        var pct = Math.round(10 + (stepIndex / steps.length) * 80);
        setProgress(pct, stepLabels[step]);

        $.ajax({
            url: wpbmf_admin.ajax_url,
            method: 'POST',
            timeout: 300000,
            data: {
                action: 'wpbmf_scan_step',
                nonce: wpbmf_admin.batch_nonce,
                scan_id: scanId,
                step: step
            }
        }).done(function (res) {
            if (res.success) {
                runStep(scanId, stepIndex + 1);
            } else {
                setProgress(pct, 'Error on step: ' + step);
                $scanBtn.prop('disabled', false).text('Run New Scan');
            }
        }).fail(function () {
            setProgress(pct, 'Network error on step: ' + step);
            $scanBtn.prop('disabled', false).text('Run New Scan');
        });
    }

    $scanForm.on('submit', function (e) {
        e.preventDefault();

        $scanBtn.prop('disabled', true).text('Scanning...');
        $progressWrap.show();
        setProgress(5, 'Starting scan...');

        $.post(wpbmf_admin.ajax_url, {
            action: 'wpbmf_start_scan',
            nonce: wpbmf_admin.batch_nonce
        }).done(function (res) {
            if (res.success && res.data && res.data.scan_id) {
                runStep(res.data.scan_id, 0);
            } else {
                var errorMsg = (res.data && res.data.error) ? res.data.error : ((res.data && res.data.message) ? res.data.message : 'Failed to start scan.');
                setProgress(0, errorMsg);
                alert(errorMsg);
                $scanBtn.prop('disabled', false).text('Run New Scan');
            }
        }).fail(function () {
            setProgress(0, 'Network error. Please try again.');
            $scanBtn.prop('disabled', false).text('Run New Scan');
        });
    });

})(jQuery);