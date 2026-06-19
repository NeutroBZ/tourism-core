/**
 * Tour itinerary metabox (sortable repeater + dynamic WYSIWYG descriptions).
 */
(function ($) {
	'use strict';

	var L10n =
		typeof TourismCoreTourItinerary !== 'undefined' && TourismCoreTourItinerary
			? TourismCoreTourItinerary
			: {};

	var editorSettings = {
		tinymce: {
			wpautop: true,
			plugins: 'lists link paste wordpress',
			toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink',
			toolbar2: '',
			height: 100,
		},
		quicktags: true,
		mediaButtons: false,
	};

	function tourismCoreItinerarySaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreItineraryInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
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

			tourismCoreRenumberItineraryFeatures($item, dayIndex);
		});

		if (reinitEditors !== false) {
			tourismCoreItineraryInitAllEditors($container);
		}
	}

	$(function () {
		var $itineraryContainer = $('#tourism-core-itinerary-container');
		if (!$itineraryContainer.length) {
			return;
		}

		tourismCoreItineraryInitAllEditors($itineraryContainer);

		$itineraryContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-itinerary-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			update: function () {
				tourismCoreRenumberItinerary($itineraryContainer);
			},
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
			tourismCoreItineraryInitEditor('tourism_core_itinerary_description_' + idx);
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
