/**
 * Tour itinerary metabox (sortable repeater + dynamic WYSIWYG descriptions).
 */
(function ($) {
	'use strict';

	var L10n =
		typeof TourismCoreTourItinerary !== 'undefined' && TourismCoreTourItinerary
			? TourismCoreTourItinerary
			: {};


	function tourismCoreItinerarySaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreItineraryInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if ($('#' + editorId).closest('.wp-editor-wrap').length || (typeof tinymce !== 'undefined' && tinymce.get(editorId))) {
			return;
		}

		wp.editor.initialize(editorId, window.TourismCoreRichEditorSettings());
	}

	function tourismCoreItineraryDestroyEditor(editorId) {
		if (!editorId) {
			return;
		}

		if (typeof wp !== 'undefined' && wp.editor && wp.editor.remove) {
			wp.editor.remove(editorId);
			return;
		}

		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get(editorId);
			if (editor) {
				editor.remove();
			}
		}

		var quicktagToolbar = document.getElementById('qt_' + editorId + '_toolbar');
		if (quicktagToolbar) {
			quicktagToolbar.remove();
		}
	}

	function tourismCoreItineraryDestroyAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreItineraryDestroyEditor($(this).attr('id'));
		});
	}

	function tourismCoreItineraryInitAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreItineraryInitEditor($(this).attr('id'));
		});
	}

	function tourismCoreRenumberItineraryFeatures($item, dayIndex) {
		$item.find('.tourism-core-itinerary-feature-row').each(function (featureIndex) {
			var $feature = $(this);

			$feature
				.find('.tourism-core-itinerary-feature-label')
				.attr('name', 'tourism_itinerary_items[' + dayIndex + '][features][' + featureIndex + '][label]')
				.attr('id', 'tourism_core_itinerary_feature_label_' + dayIndex + '_' + featureIndex);
			$feature
				.find('.tourism-core-itinerary-feature-value')
				.attr('name', 'tourism_itinerary_items[' + dayIndex + '][features][' + featureIndex + '][value]')
				.attr('id', 'tourism_core_itinerary_feature_value_' + dayIndex + '_' + featureIndex);
		});
	}

	function tourismCoreRenumberItinerary($container, reinitEditors) {
		if (!$container.length) {
			return;
		}

		tourismCoreItinerarySaveEditors();
		tourismCoreItineraryDestroyAllEditors($container);

		$container.children('.tourism-core-itinerary-item').each(function (dayIndex) {
			var $item = $(this);

			$item.attr('data-tourism-core-index', String(dayIndex));
			$item.find('.tourism-core-item-number').text(String(dayIndex + 1));
			$item
				.find('.tourism-core-itinerary-day')
				.attr('name', 'tourism_itinerary_items[' + dayIndex + '][day]')
				.attr('id', 'tourism_core_itinerary_day_' + dayIndex);
			$item
				.find('.tourism-core-itinerary-description')
				.attr('name', 'tourism_itinerary_items[' + dayIndex + '][description]')
				.attr('id', 'tourism_core_itinerary_description_' + dayIndex);
			$item.find('label[for^="tourism_core_itinerary_day_"]').attr('for', 'tourism_core_itinerary_day_' + dayIndex);
			$item.find('label[for^="tourism_core_itinerary_description_"]').attr('for', 'tourism_core_itinerary_description_' + dayIndex);
			tourismCoreItineraryControls($item, dayIndex, $container.children('.tourism-core-itinerary-item').length);

			tourismCoreRenumberItineraryFeatures($item, dayIndex);
		});

		if (reinitEditors !== false) {
			tourismCoreItineraryInitAllEditors($container);
		}
	}

	function tourismCoreItineraryControls($item, index, count) {
		var $header = $item.find('.tourism-core-itinerary-header');
		if (!$header.find('.tourism-itinerary-toggle').length) {
			$('<span class="tourism-itinerary-title"></span>').appendTo($header.find('h4'));
			$('<button type="button" class="tourism-itinerary-toggle"></button><button type="button" class="button tourism-itinerary-duplicate">Duplicar</button><button type="button" class="button tourism-itinerary-up" aria-label="Mover día hacia arriba">↑</button><button type="button" class="button tourism-itinerary-down" aria-label="Mover día hacia abajo">↓</button>').insertBefore($header.find('.tourism-core-delete-itinerary'));
		}
		var bodyId = 'tourism-itinerary-body-' + index;
		var collapsed = $item.find('.tourism-core-itinerary-body').prop('hidden');
		$item.find('.tourism-core-itinerary-body').attr('id', bodyId);
		$header.find('.tourism-itinerary-toggle').attr({ 'aria-controls': bodyId, 'aria-expanded': String(!collapsed) }).text(collapsed ? 'Desplegar' : 'Contraer');
		$header.find('.tourism-itinerary-title').text($item.find('.tourism-core-itinerary-day').val() || 'Sin título');
		$header.find('.tourism-itinerary-up').prop('disabled', index === 0);
		$header.find('.tourism-itinerary-down').prop('disabled', index === count - 1);
	}

	$(function () {
		var $itineraryContainer = $('#tourism-core-itinerary-container');
		if (!$itineraryContainer.length) {
			return;
		}

		tourismCoreItineraryInitAllEditors($itineraryContainer);
		$itineraryContainer.children('.tourism-core-itinerary-item').each(function (index) {
			$(this).find('.tourism-core-itinerary-body').prop('hidden', index > 0);
			tourismCoreItineraryControls($(this), index, $itineraryContainer.children().length);
		});

		$itineraryContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-itinerary-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			start: function () {
				tourismCoreItinerarySaveEditors();
				tourismCoreItineraryDestroyAllEditors($itineraryContainer);
			},
			stop: function () { tourismCoreRenumberItinerary($itineraryContainer); $(document).trigger('tourism:content-changed'); },
		});

		$(document).on('click', '#tourism-core-add-itinerary-item', function (e) {
			e.preventDefault();

			var tmpl = $('#tourism-core-tmpl-itinerary-item').html();
			if (!tmpl) {
				return;
			}

			var idx = $itineraryContainer.children('.tourism-core-itinerary-item').length;
			var html = tmpl
				.split('{{tourism_index}}')
				.join(String(idx))
				.split('{{tourism_index_plus_one}}')
				.join(String(idx + 1));

			$itineraryContainer.append(html);
			tourismCoreRenumberItinerary($itineraryContainer);
			$itineraryContainer.children().last().find('.tourism-core-itinerary-day').trigger('focus');
		});

		$itineraryContainer.on('input', '.tourism-core-itinerary-day', function () {
			$(this).closest('.tourism-core-itinerary-item').find('.tourism-itinerary-title').text(this.value || 'Sin título');
		});
		$itineraryContainer.on('click', '.tourism-itinerary-toggle', function () {
			var $item = $(this).closest('.tourism-core-itinerary-item');
			$item.find('.tourism-core-itinerary-body').prop('hidden', !$item.find('.tourism-core-itinerary-body').prop('hidden'));
			tourismCoreItineraryControls($item, $item.index(), $itineraryContainer.children().length);
		});
		$itineraryContainer.on('click', '.tourism-itinerary-up, .tourism-itinerary-down', function () {
			var $item = $(this).closest('.tourism-core-itinerary-item');
			var up = $(this).hasClass('tourism-itinerary-up');
			var $neighbor = up ? $item.prev() : $item.next();
			if (!$neighbor.length) return;
			tourismCoreItinerarySaveEditors();
			tourismCoreItineraryDestroyAllEditors($itineraryContainer);
			if (up) $item.insertBefore($neighbor); else $item.insertAfter($neighbor);
			tourismCoreRenumberItinerary($itineraryContainer);
			$item.find(up ? '.tourism-itinerary-up' : '.tourism-itinerary-down').trigger('focus');
			$(document).trigger('tourism:content-changed');
		});
		$itineraryContainer.on('click', '.tourism-itinerary-duplicate', function () {
			tourismCoreItinerarySaveEditors();
			var $source = $(this).closest('.tourism-core-itinerary-item');
			var index = $itineraryContainer.children().length;
			var html = $('#tourism-core-tmpl-itinerary-item').html().split('{{tourism_index}}').join(String(index)).split('{{tourism_index_plus_one}}').join(String(index + 1));
			var $copy = $(html);
			$copy.find('.tourism-core-itinerary-day').val($source.find('.tourism-core-itinerary-day').val());
			$copy.find('.tourism-core-itinerary-description').val($source.find('.tourism-core-itinerary-description').val());
			$source.find('.tourism-core-itinerary-feature-row').each(function () {
				var $feature = $($('#tourism-core-tmpl-itinerary-feature').html());
				$feature.find('.tourism-core-itinerary-feature-label').val($(this).find('.tourism-core-itinerary-feature-label').val());
				$feature.find('.tourism-core-itinerary-feature-value').val($(this).find('.tourism-core-itinerary-feature-value').val());
				$copy.find('.tourism-core-itinerary-feature-list').append($feature);
			});
			tourismCoreItineraryDestroyAllEditors($itineraryContainer);
			$copy.insertAfter($source);
			tourismCoreRenumberItinerary($itineraryContainer);
			$copy.find('.tourism-core-itinerary-day').trigger('focus');
			$(document).trigger('tourism:content-changed');
		});

		$(document).on('click', '.tourism-core-delete-itinerary', function (e) {
			e.preventDefault();

			var confirmMessage =
				L10n.deleteConfirm || 'Are you sure you want to delete this day?';
			if (!window.confirm(confirmMessage)) {
				return;
			}

			var $item = $(this).closest('.tourism-core-itinerary-item');
			var editorId = $item.find('.tourism-core-wysiwyg').attr('id');

			tourismCoreItineraryDestroyEditor(editorId);
			$item.remove();
			tourismCoreRenumberItinerary($itineraryContainer);
		});

		$(document).on('click', '.tourism-core-add-itinerary-feature', function (e) {
			e.preventDefault();

			var $item = $(this).closest('.tourism-core-itinerary-item');
			var tmpl = $('#tourism-core-tmpl-itinerary-feature').html();
			if (!tmpl) {
				return;
			}

			$item.find('.tourism-core-itinerary-feature-list').append(tmpl);

			var dayIndex = $itineraryContainer.children('.tourism-core-itinerary-item').index($item);
			tourismCoreRenumberItineraryFeatures($item, dayIndex);
		});

		$(document).on('click', '.tourism-core-delete-itinerary-feature', function (e) {
			e.preventDefault();

			var $item = $(this).closest('.tourism-core-itinerary-item');
			$(this).closest('.tourism-core-itinerary-feature-row').remove();

			var dayIndex = $itineraryContainer.children('.tourism-core-itinerary-item').index($item);
			tourismCoreRenumberItineraryFeatures($item, dayIndex);
		});

		$('#post').on('submit', function () {
			tourismCoreItinerarySaveEditors();
		});
	});
})(jQuery);
