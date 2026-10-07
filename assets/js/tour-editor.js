/** Section navigation without replacing WordPress's form or stored tour metadata. */
(function ($) {
	'use strict';
	$(function () {
		var boot = window.TourismCoreEditorBoot;
		if (boot && boot.failed) return;
		var config = window.TourismCoreEditor;
		var $navigation = $('#tourism-tour-editor');
		var $normal = $('#normal-sortables');
		if (!config || !$navigation.length || !$normal.length || !$('#tourism-core-tour-details').length) {
			if (boot) boot.fallback();
			return;
		}
		var $form = $('#post');
		var $workspace = $('<div class="tourism-editor-workspace"></div>').prependTo($normal);
		var available = [];
		var active;
		var storageKey = 'tourism-editor-section-' + ($('#post_ID').val() || 'new');
		var relocated = [];
		try {
			Object.keys(config.sections).forEach(function (key) {
				var section = config.sections[key];
				var $panel = $('<section class="tourism-editor-panel" role="tabpanel" tabindex="0"></section>')
					.attr({ id: 'tourism-panel-' + key, 'aria-labelledby': 'tourism-tab-' + key, 'data-section': key });
				$('<h2 class="tourism-section-title"></h2>').text(section.label).appendTo($panel);
				$('<p class="tourism-section-help"></p>').text(section.help).appendTo($panel);
				section.boxes.forEach(function (id) {
					var box = document.getElementById(id);
					if (!box) return;
					relocated.push({ node: box, parent: box.parentNode, next: box.nextSibling });
					$(box).removeClass('closed').addClass('tourism-editor-box').appendTo($panel);
					$(box).find('.handlediv').attr('aria-expanded', 'true');
				});
				if (!$panel.find('.postbox').length) {
					$('#tourism-tab-' + key).prop('hidden', true);
					return;
				}
				$panel.appendTo($workspace);
				available.push(key);
			});
		} catch (error) {
			// Restore the original editor if section preparation fails partway through.
			relocated.reverse().forEach(function (entry) {
				entry.parent.insertBefore(entry.node, entry.next && entry.next.parentNode === entry.parent ? entry.next : null);
				$(entry.node).removeClass('tourism-editor-box');
			});
			$workspace.remove();
			if (boot) boot.fallback();
			return;
		}
		if (!available.length) return;

		// Move the original field nodes: names, values, nonce and save handlers stay intact.
		var $priceFields = $('<div class="tourism-editor-price-extra"></div>').appendTo('#tourism-core-tour-pricing .inside');
		['tourism_price_currency', 'tourism_offer_availability', 'tourism_price_valid_until'].forEach(function (key) {
			$('[data-schema-field="' + key + '"]').appendTo($priceFields);
		});
		$('[data-schema-field="tourism_trip_origin"]').appendTo('#tourism-core-tour-details .inside');
		$('<p class="description"></p>').text('La disponibilidad se declara manualmente; no sincroniza cupos ni confirma pagos.').appendTo($priceFields);
		$('#tourism-core-tour-schema .inside > p').first().text('El marcado se genera automáticamente con la información de las otras secciones. Aquí puedes ajustar el código comercial y su activación.');
		$('#tourism-core-tour-schema .description').text('Desactiva nuestro marcado solo si otra integración genera el mismo esquema para este tour.');

		// Selecting a value is the primary action; editing its option list is optional.
		$('.tourism-core-duration-builder, .tourism-core-difficulty-builder, .tourism-core-group-size-builder, .tourism-core-max-elevation-builder').each(function () {
			var $builder = $(this);
			var $details = $('<details class="tourism-editor-options"><summary>Personalizar opciones</summary></details>');
			$builder.before($details).appendTo($details);
		});
		var $destinations = $('input[name="tourism_destinations[]"]').first().closest('div');
		if ($destinations.length) {
			var $search = $('<input type="search" class="widefat tourism-destination-search" placeholder="Buscar un destino…" aria-label="Buscar destinos">');
			$search.insertBefore($destinations).on('input', function () {
				var needle = this.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
				$destinations.find('label').each(function () {
					this.hidden = $(this).text().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().indexOf(needle) < 0;
				});
			});
		}

		var $footer = $('<div class="tourism-editor-footer"><button type="button" class="button tourism-editor-prev">Anterior</button><span class="tourism-editor-position"></span><button type="button" class="button button-secondary tourism-editor-next">Siguiente</button></div>').appendTo($workspace);
		function select(key, focus) {
			if (available.indexOf(key) < 0) key = available[0];
			active = key;
			$workspace.children('.tourism-editor-panel').each(function () { this.hidden = $(this).attr('data-section') !== key; });
			$navigation.find('[role="tab"]').each(function () {
				var selected = $(this).attr('data-section') === key;
				$(this).attr({ 'aria-selected': String(selected), tabindex: selected ? '0' : '-1' });
			});
			var index = available.indexOf(key);
			$footer.find('.tourism-editor-prev').prop('disabled', index === 0);
			$footer.find('.tourism-editor-next').prop('disabled', index === available.length - 1);
			$footer.find('.tourism-editor-position').text('Sección ' + (index + 1) + ' de ' + available.length);
			try { window.sessionStorage.setItem(storageKey, key); } catch (error) { /* Storage is optional. */ }
			if (focus) document.getElementById('tourism-tab-' + key).focus();
			$(document).trigger('tourism:section-changed', [key]);
		}
		$navigation.on('click', '[role="tab"]', function () { select($(this).attr('data-section')); });
		$navigation.on('keydown', '[role="tab"]', function (event) {
			var index = available.indexOf(active);
			if (event.key === 'ArrowRight') index = (index + 1) % available.length;
			else if (event.key === 'ArrowLeft') index = (index + available.length - 1) % available.length;
			else if (event.key === 'Home') index = 0;
			else if (event.key === 'End') index = available.length - 1;
			else return;
			event.preventDefault();
			select(available[index], true);
		});
		$footer.on('click', '.tourism-editor-prev, .tourism-editor-next', function () {
			select(available[available.indexOf(active) + ($(this).hasClass('tourism-editor-next') ? 1 : -1)], true);
			$navigation[0].scrollIntoView({ block: 'start' });
		});
		function reveal(field) {
			var $field = $(field);
			var $panel = $field.closest('.tourism-editor-panel');
			if ($panel.length) select($panel.attr('data-section'));
			$field.parents('details').prop('open', true);
			$field.closest('.postbox').removeClass('closed').find('.handlediv').attr('aria-expanded', 'true');
			$field.closest('.tourism-core-itinerary-item').find('.tourism-core-itinerary-body').prop('hidden', false);
			$field.closest('.tourism-core-itinerary-item').find('.tourism-itinerary-toggle').attr('aria-expanded', 'true');
			$field.parents('[id^="tourism-repeat-body-"]').prop('hidden', false).each(function () {
				$('[aria-controls="' + this.id + '"]').attr('aria-expanded', 'true');
			});
			return $field;
		}
		// HTML validation must also work when the invalid input lives in another section.
		$form[0].addEventListener('invalid', function (event) {
			var field = event.target;
			if (!$(field).closest('.tourism-editor-panel').length) return;
			event.preventDefault();
			reveal(field);
			$(field).attr('aria-invalid', 'true');
			var errorId = 'tourism-error-' + field.id;
			var $error = $('#' + errorId);
			if (!$error.length) $error = $('<span class="tourism-field-error"></span>').attr('id', errorId).insertAfter(field);
			$error.text(field.validationMessage);
			$(field).attr('aria-describedby', errorId);
			field.focus();
		}, true);
		$form.on('input change', '[aria-invalid="true"]', function () {
			if (this.validity && this.validity.valid) {
				$('#tourism-error-' + this.id).remove();
				$(this).removeAttr('aria-invalid aria-describedby');
			}
		});

		var $summary = $('<div class="tourism-payment-summary" role="status" aria-live="polite"><h3>Opciones de reserva</h3><p class="tourism-payment-result"></p><ul></ul></div>').prependTo('#tourism-panel-pricing');
		if (config.paymentsUrl) $('<a>Configurar métodos generales</a>').attr('href', config.paymentsUrl).appendTo($summary);
		function paymentSummary() {
			var payments = config.payments;
			var uuid = String($('#tourism_wetravel_button_code').val() || '').trim();
			var price = Number($('#tourism_price_from').val());
			var currency = $('#tourism_price_currency').val() || payments.currency;
			var weTravel = payments.wetravel && /^[1-9][0-9]*$/.test(uuid);
			var paypal = payments.paypal && Number.isFinite(price) && price > 0 && ['USD', 'EUR'].indexOf(currency) >= 0;
			var methods = [];
			if (weTravel) methods.push('WeTravel');
			if (paypal) methods.push('PayPal');
			$summary.find('.tourism-payment-result').text(methods.length ? 'Al guardar, este tour ofrecerá: ' + methods.join(' y ') + '.' : payments.whatsapp ? 'Al guardar, este tour ofrecerá el botón de WhatsApp.' : 'No hay un método de reserva disponible. Configura un pago o el WhatsApp de la agencia.');
			var reasons = [];
			if (!weTravel) reasons.push(!payments.wetravel ? 'WeTravel: falta configurar la cuenta general.' : 'WeTravel: añade el UUID válido de este tour.');
			if (!paypal) reasons.push(!payments.paypal ? 'PayPal: falta configurar el correo general.' : ['USD', 'EUR'].indexOf(currency) < 0 ? 'PayPal: esta integración admite precios en USD o EUR.' : 'PayPal: añade un precio mayor que cero.');
			var $list = $summary.find('ul').empty();
			reasons.forEach(function (reason) { $('<li></li>').text(reason).appendTo($list); });
			$summary.toggleClass('tourism-payment-empty', !methods.length && !payments.whatsapp);
		}
		$form.on('input change', '#tourism_wetravel_button_code, #tourism_price_from, #tourism_price_currency', paymentSummary);
		paymentSummary();

		var $checklist = $('<div class="tourism-editor-checklist" aria-label="Datos básicos sugeridos"><span>Por completar:</span></div>').appendTo($navigation);
		var checks = [
			{ label: 'Nombre', section: 'general', target: '#title', complete: function () { return !!String($('#title').val() || '').trim(); } },
			{ label: 'Introducción', section: 'general', target: '#tourism_intro_description', complete: function () {
				var editor = window.tinymce && tinymce.get('tourism_intro_description');
				var text = editor ? editor.getContent({ format: 'text' }) : $('<div></div>').html($('#tourism_intro_description').val() || '').text();
				return !!text.trim();
			} },
			{ label: 'Imagen', section: 'photos', target: '#tourism-core-tour-hero', complete: function () {
				return Number($('#tour_hero_image').val()) > 0 || Number($('#_thumbnail_id').val()) > 0 || $('.tourism-core-gallery-field-id').toArray().some(function (field) { return Number(field.value) > 0; }) || $('.tourism-core-gallery-field-url').toArray().some(function (field) { return /^https?:\/\//.test(field.value); });
			} },
			{ label: 'Precio', section: 'pricing', target: '#tourism_price_from', complete: function () { var value = $('#tourism_price_from').val(); return value !== '' && Number.isFinite(Number(value)) && Number(value) >= 0; } }
		];
		function checklist() {
			$checklist.find('button, .tourism-checklist-done').remove();
			var missing = checks.filter(function (check) { return !check.complete(); });
			$checklist.find('> span').text(missing.length ? 'Por completar:' : 'Datos básicos completos');
			missing.forEach(function (check) {
				$('<button type="button"></button>').text(check.label).appendTo($checklist).on('click', function () {
					select(check.section);
					var $target = reveal($(check.target));
					$target[0].scrollIntoView({ block: 'center' });
					var editor = window.tinymce && tinymce.get($target.attr('id'));
					if (editor) editor.focus(); else $target.trigger('focus');
				});
			});
		}
		function dirty() { $('#tourism-editor-save-state').text('Cambios sin guardar').addClass('has-changes'); }
		$form.on('input change', 'input, textarea, select', function (event) { checklist(); if (event.originalEvent) dirty(); });
		$form.on('click', '.tourism-editor-panel button', function () {
			if (!$(this).is('.handlediv, .tourism-itinerary-toggle, .tourism-repeat-toggle, .tourism-repeat-expand')) dirty();
			window.setTimeout(checklist, 0);
		});
		if (window.tinymce) {
			tinymce.on('AddEditor', function (event) {
				event.editor.on('input change undo redo', function () { dirty(); checklist(); });
			});
		}
		$(document).on('tourism:content-changed', function () { dirty(); checklist(); });
		$form.on('submit', function () { if (window.tinymce) tinymce.triggerSave(); });
		// Media callbacks update hidden inputs without firing native change events.
		var mediaObserver = new MutationObserver(function () { checklist(); dirty(); });
		['tour-hero-preview', 'tourism-core-gallery-images-container', 'postimagediv'].forEach(function (id) {
			var node = document.getElementById(id);
			if (node) mediaObserver.observe(node, { childList: true, subtree: true });
		});
		$('body').addClass('tourism-editor-active');
		$navigation.prop('hidden', false);
		var remembered;
		try { remembered = window.sessionStorage.getItem(storageKey); } catch (error) { /* Storage is optional. */ }
		select(remembered);
		checklist();
		if (boot) boot.finish();
	});
})(jQuery);
