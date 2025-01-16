jQuery(document).ready(function ($) {
	$('#doaction').on('click', function (e) {
		e.preventDefault();

		// Get selected action and elements
		const action = $('#bulk-action-selector-top').val();
		const selectedIds = $('input[name="element[]"]:checked').map(function () {
			return $(this).val();
		}).get();

		// Disable button to prevent duplicate clicks
		const $button = $(this);
		$button.prop('disabled', true);

		if (!action || action === '-1') {
			showNotice('error', 'Please select a bulk action before clicking Apply.');
			$button.prop('disabled', false);
			return;
		}

		if ((action === 'export' || action === 'delete') && selectedIds.length === 0) {
			showNotice('error', 'Please select the records you want to ' + action + '.');
			$button.prop('disabled', false); // Re-enable button
			return;
		}

		// Send AJAX request
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

						// Remove deleted rows from screen without reload
						selectedIds.forEach(function (id) {
							$(`input[name="element[]"][value="${id}"]`).closest('tr').remove();
						});

						// Uncheck the "select all" checkboxes if they are checked
						$('input#cb-select-all-1, input#cb-select-all-2').prop('checked', false);
					}
				} else {
					showNotice('error', response.data.message);
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error('AJAX Error:', textStatus, errorThrown, jqXHR);
				showNotice('error', 'An error occurred while processing your request.');
			},
			complete: function () {
				// Re-enable button
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
		$('#tfcm-notices-container').html(noticeHtml);

		// Make dismissible
		$('.notice.is-dismissible').on('click', '.notice-dismiss', function () {
			$(this).closest('.notice').fadeOut();
		});
	}
});