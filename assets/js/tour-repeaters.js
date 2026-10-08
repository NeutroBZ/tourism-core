/** Compact headers for long rich-text lists. Runs after the existing repeater modules. */
(function ($) {
	'use strict';
	$(function () {
		if (!$('body').hasClass('tourism-editor-active')) return;
		var sequence = 0;
		var groups = [
			{ container: '#tourism-core-booking-policy-container', item: '.tourism-core-repeater-item', header: '.tourism-core-repeater-item-header', body: '.tourism-core-repeater-item-body', title: 'input[name$="[title]"]', fallback: 'Política sin título' },
			{ container: '#tourism-core-faqs-container', item: '.tourism-core-faq-item', header: '.tourism-core-faq-header', body: '.tourism-core-faq-content', title: 'input[name$="[question]"]', fallback: 'Pregunta sin título' },
			{ container: '#tourism-core-highlights-container', item: '.tourism-core-repeater-item', header: '.tourism-core-repeater-item-header', body: '.tourism-core-repeater-item-body', title: 'input[name$="[title]"]', fallback: 'Destacado sin título' },
			{ container: '#tourism-core-what-to-expect-container', item: '.tourism-core-repeater-item', header: '.tourism-core-repeater-item-header', body: '.tourism-core-repeater-item-body', title: 'input[name$="[title]"]', fallback: 'Experiencia sin título' },
			{ container: '#tourism-core-pricing-info-container', item: '.tourism-core-pricing-info-item', header: '.tourism-core-label-row-header', body: '.tourism-core-label-row-body', title: 'input[name$="[title]"]', fallback: 'Detalle sin título' },
			{ container: '#tourism-core-what-to-bring-container', item: '.tourism-core-what-to-bring-item', header: '.postbox-header', body: '.inside', title: 'input[name$="[title]"]', fallback: 'Recomendación sin título' },
			{ container: '#tourism-includes-container', item: '.tourism-include-item', header: '.tourism-core-label-row-header', body: '.tourism-core-label-row-body', title: 'input[name$="[title]"]', fallback: 'Incluido sin título' },
			{ container: '#tourism-excludes-container', item: '.tourism-exclude-item', header: '.tourism-core-label-row-header', body: '.tourism-core-label-row-body', title: 'input[name$="[title]"]', fallback: 'Excluido sin título' }
		];
		groups.forEach(function (group) {
			var $container = $(group.container);
			if (!$container.length) return;
			function enhance(initial) {
				$container.children(group.item).each(function (index) {
					var $item = $(this);
					var $body = $item.children(group.body);
					var $header = $item.children(group.header);
					if (!$body.length || !$header.length || $header.find('.tourism-repeat-toggle').length) return;
					var id = 'tourism-repeat-body-' + (++sequence);
					$body.attr('id', id).prop('hidden', !!initial && index > 0);
					var $button = $('<button type="button" class="tourism-repeat-toggle"><span class="tourism-repeat-title"></span></button>')
						.attr({ 'aria-controls': id, 'aria-expanded': String(!$body.prop('hidden')) });
					var $label = $header.find('h4, .tourism-core-repeater-item-title, .tourism-core-label-item-title, .tourism-core-what-to-bring-item-title-text').first().css('flex', '0 0 auto');
					$button.insertAfter($label);
					$button.find('span').text($item.find(group.title).val() || group.fallback);
					$button.on('click', function () {
						$body.prop('hidden', !$body.prop('hidden'));
						$button.attr('aria-expanded', String(!$body.prop('hidden')));
					});
				});
			}
			var $actions = $('<div class="tourism-repeat-actions"></div>').insertBefore($container);
			['Desplegar todo', 'Contraer todo'].forEach(function (label, index) {
				$('<button type="button" class="button tourism-repeat-expand"></button>').text(label).appendTo($actions).on('click', function () {
					$container.find(group.body).prop('hidden', index === 1);
					$container.find('.tourism-repeat-toggle').attr('aria-expanded', String(index === 0));
				});
			});
			$container.on('input', group.title, function () {
				$(this).closest(group.item).find('.tourism-repeat-title').text(this.value || group.fallback);
			});
			enhance(true);
			// Observe only inserted/removed rows, not editor internals or header text updates.
			new MutationObserver(function () { enhance(false); }).observe($container[0], { childList: true });
		});
	});
})(jQuery);
