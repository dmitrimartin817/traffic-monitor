// tfcm-client-script.js
jQuery(document).ready(function ($) {
	console.log("tfcm-client-script.js loaded.");

	// Fetch client IP using ipify API.
	$.get("https://api.ipify.org?format=json", function (data) {
		const clientIp = data.ip;
		console.log("Client IP fetched:", clientIp);

		// Send AJAX request to WordPress.
		$.ajax({
			url: tfcmClientAjax.ajax_url,
			type: "POST",
			data: {
				action: "tfcm_log_ajax_request",
				nonce: tfcmClientAjax.nonce,
				ip_address: clientIp,
				request_url: window.location.href,
			},
			success: function (response) {
				console.log("Success:", response.data.message);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error("AJAX error:", jqXHR.responseText, "Status:", textStatus, "Error:", errorThrown);
			}
		});
	});
});