// tfcm-admin-script.js
jQuery(document).ready(function ($) {
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
			url: tfcmAdminAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'tfcm_handle_bulk_action',
				bulk_action: action,
				nonce: tfcmAdminAjax.nonce
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
			url: tfcmAdminAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'tfcm_handle_bulk_action',
				bulk_action: action,
				element: selectedIds,
				nonce: tfcmAdminAjax.nonce,
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