(function($) {
    'use strict';

    $(function() {
        const selectionType = $('input[name="altsync-selection-type"]');
        const imageSelection = $('.altsync-image-selection');
        const previewButton = $('#altsync-preview');
        const syncButton = $('#altsync-sync');
        const spinner = $('.altsync-buttons .spinner');
        const previewResults = $('#altsync-preview-results');
        const syncResults = $('#altsync-sync-results');
        const previewList = $('#altsync-preview-list');
        const syncList = $('#altsync-sync-list');
        const previewSummary = $('#altsync-preview-summary');
        const syncSummary = $('#altsync-sync-summary');
        const selectAllButton = $('#altsync-select-all');
        const selectNoneButton = $('#altsync-select-none');
        
        // Store preview data for sync operation
        let previewData = null;
        
        // Toggle image selection based on radio button choice
        selectionType.on('change', function() {
            if ($(this).val() === 'selected') {
                imageSelection.show();
            } else {
                imageSelection.hide();
            }
        });
        
        // Select/deselect all images
        selectAllButton.on('click', function() {
            $('.altsync-image-item.has-alt input[type="checkbox"]').prop('checked', true);
        });
        
        selectNoneButton.on('click', function() {
            $('.altsync-image-item input[type="checkbox"]').prop('checked', false);
        });
        
        // Preview changes (dry run)
        previewButton.on('click', function() {
            // Reset UI
            previewResults.hide();
            syncResults.hide();
            syncButton.prop('disabled', true);
            spinner.css('visibility', 'visible');
            
            // Get selected images or all images flag
            const isAll = $('input[name="altsync-selection-type"]:checked').val() === 'all';
            const syncMode = $('input[name="altsync-sync-mode"]:checked').val();
            let imageIds = [];
            
            if (!isAll) {
                // Get selected image IDs
                $('input[name="altsync-image[]"]:checked').each(function() {
                    imageIds.push($(this).val());
                });
                
                if (imageIds.length === 0) {
                    alert(altsync_bulk.no_images_selected);
                    spinner.css('visibility', 'hidden');
                    return;
                }
            }
            
            // AJAX request for preview
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'altsync_bulk_preview',
                    nonce: altsync_bulk.nonce,
                    all_images: isAll,
                    image_ids: imageIds,
                    sync_mode: syncMode
                },
                success: function(response) {
                    if (response.success) {
                        previewData = response.data;
                        displayPreviewResults(response.data);
                        syncButton.prop('disabled', false);
                    } else {
                        alert(response.data.message || altsync_bulk.error_text);
                    }
                },
                error: function() {
                    alert(altsync_bulk.error_text);
                },
                complete: function() {
                    spinner.css('visibility', 'hidden');
                }
            });
        });
        
        // Sync alt text
        syncButton.on('click', function() {
            if (!previewData || !previewData.preview || previewData.preview.length === 0) {
                return;
            }
            
            const syncMode = $('input[name="altsync-sync-mode"]:checked').val();
            let confirmMessage = altsync_bulk.confirm_sync;
            
            // Show a stronger warning for the "all" mode
            if (syncMode === 'all') {
                confirmMessage = altsync_bulk.confirm_sync_all;
            }
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Reset UI
            syncResults.hide();
            spinner.css('visibility', 'visible');
            syncButton.prop('disabled', true);
            
            // Extract image IDs from preview data
            const imageIds = previewData.preview.map(item => item.id);
            
            // AJAX request for sync
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'altsync_bulk_sync',
                    nonce: altsync_bulk.nonce,
                    image_ids: imageIds,
                    sync_mode: syncMode
                },
                success: function(response) {
                    if (response.success) {
                        displaySyncResults(response.data);
                    } else {
                        alert(response.data.message || altsync_bulk.error_text);
                    }
                },
                error: function() {
                    alert(altsync_bulk.error_text);
                },
                complete: function() {
                    spinner.css('visibility', 'hidden');
                    syncButton.prop('disabled', false);
                }
            });
        });
        
        // Display preview results
        function displayPreviewResults(data) {
            previewList.empty();
            
            if (!data.preview || data.preview.length === 0) {
                previewSummary.text(altsync_bulk.no_updates_needed);
                previewResults.show();
                return;
            }
            
            // Display appropriate summary based on sync mode
            if (data.sync_mode === 'all') {
                previewSummary.text(
                    altsync_bulk.preview_summary_all.replace('%d', data.total_posts)
                );
            } else {
                previewSummary.text(
                    altsync_bulk.preview_summary.replace('%d', data.total_posts)
                );
            }
            
            $.each(data.preview, function(index, item) {
                const itemHtml = $('<div class="altsync-item">' +
                    '<div class="altsync-item-image"><img src="' + item.thumbnail + '" alt=""></div>' +
                    '<div class="altsync-item-details">' +
                        '<div class="altsync-item-title">' + item.title + '</div>' +
                        '<div class="altsync-item-alt"><strong>' + altsync_bulk.alt_text + ':</strong> ' + item.alt_text + '</div>' +
                        '<div class="altsync-item-count"><strong>' + altsync_bulk.affected_posts + ':</strong> ' + item.affected_posts + '</div>' +
                    '</div>' +
                '</div>');
                
                previewList.append(itemHtml);
            });
            
            previewResults.show();
        }
        
        // Display sync results
        function displaySyncResults(data) {
            syncList.empty();
            
            if (!data.results || data.results.length === 0) {
                syncSummary.text(altsync_bulk.no_updates_performed);
                syncResults.show();
                return;
            }
            
            syncSummary.text(
                altsync_bulk.sync_summary.replace('%d', data.total_updated)
            );
            
            $.each(data.results, function(index, item) {
                const itemHtml = $('<div class="altsync-item">' +
                    '<div class="altsync-item-details">' +
                        '<div class="altsync-item-title">' + item.title + '</div>' +
                        '<div class="altsync-item-count"><strong>' + altsync_bulk.updated_posts + ':</strong> ' + item.updated_posts + '</div>' +
                    '</div>' +
                '</div>');
                
                syncList.append(itemHtml);
            });
            
            syncResults.show();
        }
    });
})(jQuery); 