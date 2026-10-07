/**
 * Destination admin metabox behaviour (hero + highlights + quick edit order).
 */
(function ($) {
	'use strict';

	$(function () {
		var frame;

		$('.tourism-upload-destination-hero').on('click', function (e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Select Hero Image',
				button: { text: 'Use image' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#destination_hero_image').val(attachment.id);
				var url = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url)
					? attachment.sizes.medium.url
					: attachment.url;
				$('#destination-hero-preview').html('<img src="' + url + '" alt="" />');
			});

			frame.open();
		});

		$('.tourism-remove-destination-hero').on('click', function () {
			$('#destination_hero_image').val('');
			$('#destination-hero-preview').html('');
		});

		function renumberHighlights() {
			var $c = $('#tourism-core-destination-highlights-container');
			if (!$c.length) return;
			$c.children('.tourism-core-destination-highlight-item').each(function (i) {
				var $item = $(this);
				$item.attr('data-tourism-core-index', String(i));
				$item.find('.tourism-core-item-number').text(String(i + 1));
				$item.find('input[name^="tourism_destination_highlights["]').attr('name', 'tourism_destination_highlights[' + i + '][title]');
				$item.find('textarea[name^="tourism_destination_highlights["]').attr('name', 'tourism_destination_highlights[' + i + '][description]');
				$item.find('label[for^="tourism_core_destination_highlight_title_"]').attr('for', 'tourism_core_destination_highlight_title_' + i);
				$item.find('input[id^="tourism_core_destination_highlight_title_"]').attr('id', 'tourism_core_destination_highlight_title_' + i);
				$item.find('label[for^="tourism_core_destination_highlight_description_"]').attr('for', 'tourism_core_destination_highlight_description_' + i);
				$item.find('textarea[id^="tourism_core_destination_highlight_description_"]').attr('id', 'tourism_core_destination_highlight_description_' + i);
			});
		}

		var $highlightsContainer = $('#tourism-core-destination-highlights-container');
		if ($highlightsContainer.length) {
			$highlightsContainer.sortable({
				handle: '.tourism-core-handle',
				items: '> .tourism-core-destination-highlight-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					renumberHighlights();
				}
			});

			$(document).on('click', '#tourism-core-add-destination-highlight', function (e) {
				e.preventDefault();
				var tmpl = $('#tourism-core-tmpl-destination-highlight-item').html();
				if (!tmpl) return;
				var idx = $highlightsContainer.children('.tourism-core-destination-highlight-item').length;
				var html = tmpl
					.split('{{tourism_index}}').join(String(idx))
					.split('{{tourism_index_plus_one}}').join(String(idx + 1));
				$highlightsContainer.append(html);
				renumberHighlights();
			});

			$(document).on('click', '.tourism-core-delete-destination-highlight', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-destination-highlight-item').remove();
				renumberHighlights();
			});

			renumberHighlights();
		}

		if (typeof inlineEditPost !== 'undefined') {
			var originalEdit = inlineEditPost.edit;
			inlineEditPost.edit = function (id) {
				originalEdit.apply(this, arguments);
				var postId = 0;
				if (typeof id === 'object' && id !== null) {
					postId = parseInt(this.getId(id), 10);
				} else {
					postId = parseInt(id, 10);
				}
				if (!postId) return;

				var $row = $('#post-' + postId);
				var $editRow = $('#edit-' + postId);
				if (!$row.length || !$editRow.length) return;

				var current = $.trim($row.find('td.column-tourism_destination_order').text());
				$editRow.find('input[name="tourism_destination_order"]').val(current !== '' ? parseInt(current, 10) : 0);
			};
		}
	});
})(jQuery);
