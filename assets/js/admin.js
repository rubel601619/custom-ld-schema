/**
 * Custom LD Schema admin script.
 *
 * Adds a client-side "Validate JSON" helper to the LD Schema meta box.
 *
 * @package Custom_LD_Schema
 */
(function () {
	'use strict';

	function ldSchemaValidate() {
		var textarea = document.getElementById('ld-schema-json');
		var message = document.querySelector('.ld-schema-validation-message');

		if (!textarea || !message) {
			return;
		}

		var value = textarea.value.trim();

		if ('' === value) {
			message.textContent = '';
			message.className = 'ld-schema-validation-message';
			return;
		}

		var json = value
			.replace(/<script\b[^>]*>/gi, '')
			.replace(/<\/script\s*>/gi, '');

		try {
			JSON.parse(json);
			message.textContent = ldSchemaAdminLabels.valid;
			message.className = 'ld-schema-validation-message ld-schema-valid';
		} catch (error) {
			message.textContent = ldSchemaAdminLabels.invalid + ' ' + error.message;
			message.className = 'ld-schema-validation-message ld-schema-invalid';
		}
	}

	var button = document.querySelector('.ld-schema-validate');

	if (button) {
		button.addEventListener('click', ldSchemaValidate);
	}
})();
