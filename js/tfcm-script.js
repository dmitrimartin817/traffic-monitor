jQuery(document).ready(function ($) {

	// Initiate Cache Check via Site Health
	$.ajax({
		url: tfcmAjax.ajax_url,
		type: 'POST',
		data: { action: 'tfcm_ajax_get_cache_status', nonce: tfcmAjax.nonce },
		success: function (response) {
			if (response.success && response.data.show_signup) {
				displayCacheNotice(response.data.message);
			}
		},
		error: function () {
			console.error('Failed to check caching status.');
		}
	});

	// Function to display cache notice with signup form
	function displayCacheNotice(message) {
		if ($('#tfcm-signup-notice').length) return;
		const noticeHtml = `
			<div id="tfcm-signup-notice" class="notice notice-warning is-dismissible">
				<p class="tfcm-form-message">${message}</p>
				<form id="tfcm-signup-form">
					<input id="tfcm-email" type="email" name="email" placeholder="Enter your email" required>
					<button type="submit">Sign Up</button>
				</form>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
			</div>`;

		$('#tfcm-notices-container').append(noticeHtml);

		$(document).on('click', '.notice-dismiss', function () {
			$(this).closest('.notice').fadeOut();

			$.post(tfcmAjax.ajax_url, {
				action: 'tfcm_dismiss_cache_notice',
				nonce: tfcmAjax.nonce
			}).done(function (response) {
				console.log("Cache notice dismissed:", response);
			}).fail(function () {
				console.error('Failed to record notice dismissal.');
			});
		});

		$(document).on('submit', '#tfcm-signup-form', function (e) {
			e.preventDefault();
			const email = $(this).find('input[name="email"]').val().trim();

			if (!email) {
				showNotice('error', 'Please enter a valid email.');
				return;
			}

			const $submitButton = $(this).find('button[type="submit"]');
			$submitButton.prop('disabled', true);

			$.post('https://hook.us1.make.com/tt7eyyb4s36qvggw8slw8sb6bh6utnl2', { email: email })
				.done(function () {
					showNotice('success', 'Thank you for signing up!');
					markUserAsSignedUp(email);
					$('#tfcm-signup-notice').fadeOut();
				})
				.fail(function () {
					showNotice('error', 'Signup failed. Please try again.');
					$submitButton.prop('disabled', false);
				});
		});

		// Marks the user as signed up via AJAX
		function markUserAsSignedUp(email) {
			// if (sessionStorage.getItem('tfcmSignedUp')) return;

			$.post(tfcmAjax.ajax_url, {
				action: 'tfcm_mark_user_signed_up',
				nonce: tfcmAjax.nonce,
				email: email
			}).done(function (response) {
				sessionStorage.setItem('tfcmSignedUp', 'true');
			}).fail(function () {
				console.error('Failed to mark user as signed up.');
			});
		}
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