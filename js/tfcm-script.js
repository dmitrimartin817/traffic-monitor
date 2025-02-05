jQuery(document).ready(function ($) {
	// Function to open the help panel and switch to the "Troubleshooting" tab
	function openTrafficMonitorHelpTab() {
		const helpButton = $('#contextual-help-link');

		// Open the Help panel if it's collapsed
		if (helpButton.length && !$('#contextual-help-wrap').is(':visible')) {
			helpButton.trigger('click');
		}

		// Wait for the panel to open, then switch to the Troubleshooting tab
		setTimeout(function () {
			const troubleshootingTab = $('#tab-link-traffic_monitor_troubleshooting');
			const troubleshootingPanel = $('#tab-panel-traffic_monitor_troubleshooting');

			if (troubleshootingTab.length && troubleshootingPanel.length) {
				// Click the tab to activate it
				troubleshootingTab.find('a').trigger('click');

				// Ensure only the Troubleshooting panel is visible
				$('.help-tab-content').hide();
				troubleshootingPanel.show();
			}
		}, 300);
	}

	/**
	 * Handle clicks on the "troublshooting" warning link.
	 */
	$(document).on('click', '#tfcm-open-troubleshooting', function (e) {
		e.preventDefault(); // Prevent default navigation behavior

		// If not already on the settings page, store intent and redirect
		if (window.location.href.indexOf('admin.php?page=traffic-monitor') === -1) {
			sessionStorage.setItem('tfcm_open_help_tab', 'true'); // Store intent
			window.location.href = tfcmAjax.admin_url + 'admin.php?page=traffic-monitor';
			return;
		}

		// If already on the page, just open the tab
		openTrafficMonitorHelpTab();
	});

	/**
	 * Automatically open the Help tab if redirected.
	 */
	if (sessionStorage.getItem('tfcm_open_help_tab') === 'true') {
		sessionStorage.removeItem('tfcm_open_help_tab'); // Clear intent after executing
		openTrafficMonitorHelpTab();
	}

	$(document).on('click', '#tfcm-delete-all', function (e) {
		e.preventDefault();
		if (confirm('Are you sure you want to delete ALL logs? This action cannot be undone.')) {
			handleGlobalAction('delete_all');
		}
	});

	$(document).on('click', '#tfcm-export-all', function (e) {
		e.preventDefault();
		handleGlobalAction('export_all');
	});

	function handleGlobalAction(action) {
		$.ajax({
			url: tfcmAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'tfcm_bulk_action',
				bulk_action: action,
				nonce: tfcmAjax.nonce
			},
			success: function (response) {
				if (response.success) {
					showNotice('success', response.data.message);
					if (action === 'delete_all') {
						location.reload();
					}
				} else {
					showNotice('error', response.data.message);
				}
			},
			error: function () {
				showNotice('error', 'An error occurred while processing your request.');
			}
		});
	}

	$('#doaction').on('click', function (e) {
		e.preventDefault();

		const action = $('#bulk-action-selector-top').val();
		const selectedIds = $('input[name="element[]"]:checked').map(function () {
			return $(this).val();
		}).get();

		const $button = $(this);
		$button.prop('disabled', true);

		if (!action || action === '-1') {
			showNotice('error', 'Please select a bulk action before clicking Apply.');
			$button.prop('disabled', false);
			return;
		}

		if ((action === 'export' || action === 'delete') && selectedIds.length === 0) {
			showNotice('error', 'Please select the records you want to ' + action + '.');
			$button.prop('disabled', false);
			return;
		}

		$.ajax({
			url: tfcmAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'tfcm_bulk_action',
				bulk_action: action,
				element: selectedIds,
				nonce: tfcmAjax.nonce,
			},
			success: function (response) {
				if (response.success) {
					if (action === 'export' || action === 'export_all') {
						showNotice('success', response.data.message);
					} else if (action === 'delete' || action === 'delete_all') {
						showNotice('success', response.data.message);

						selectedIds.forEach(function (id) {
							$(`input[name="element[]"][value="${id}"]`).closest('tr').remove();
						});

						$('input#cb-select-all-1, input#cb-select-all-2').prop('checked', false);
					}
				} else {
					showNotice('error', response.data.message);
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				showNotice('error', 'An error occurred while processing your request.');
			},
			complete: function () {
				$button.prop('disabled', false);
			}
		});
	});

	// Function to show notices dynamically
	function showNotice(type, message) {
		const noticeHtml = `
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>`;
		$('#tfcm-notices-container').append(noticeHtml);

		$('.notice.is-dismissible').on('click', '.notice-dismiss', function () {
			$(this).closest('.notice').fadeOut();
		});
	}
});