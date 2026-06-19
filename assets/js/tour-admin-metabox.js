/**
 * Tour CPT metabox admin behaviour (Tourism Core).
 */
(function ($) {
	'use strict';

	var L10n =
		typeof TourismCoreTourAdmin !== 'undefined' && TourismCoreTourAdmin
			? TourismCoreTourAdmin
			: {};

	$(function () {
		var frame;

		$('.tourism-upload-hero').on('click', function (e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: L10n.heroMediaTitle || 'Select Hero Image',
				button: { text: L10n.heroMediaButton || 'Use image' },
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var mediumUrl =
					attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url
						? attachment.sizes.medium.url
						: attachment.url;
				$('#tour_hero_image').val(attachment.id);
				$('#tour-hero-preview').html(
					'<img src="' + mediumUrl + '" style="width:100%;" alt="" />'
				);
			});

			frame.open();
		});

		$('.tourism-remove-hero').on('click', function () {
			$('#tour_hero_image').val('');
			$('#tour-hero-preview').html('');
		});

		function tourismCoreSyncPricingBeforePrice() {
			var $priceNow = $('#tourism_price_from');
			var $discount = $('#tourism_price_discount_percent');
			var $priceBefore = $('#tourism_price_before');
			if (!$priceNow.length || !$discount.length || !$priceBefore.length) {
				return;
			}

			var now = parseFloat($priceNow.val());
			var discount = parseFloat($discount.val());

			if (isNaN(now) || isNaN(discount) || discount <= 0 || discount >= 100) {
				if (!isNaN(now) && (isNaN(discount) || discount <= 0)) {
					$priceBefore.val(now.toFixed(2));
				}
				return;
			}

			var before = now / (1 - discount / 100);
			if (isFinite(before)) {
				$priceBefore.val(before.toFixed(2));
			}
		}

		$(document).on('input change', '#tourism_price_from, #tourism_price_discount_percent', function () {
			tourismCoreSyncPricingBeforePrice();
		});

		$(document).on('click', '#tourism-core-calc-price-before', function (e) {
			e.preventDefault();
			tourismCoreSyncPricingBeforePrice();
		});

		function tourismCoreRefreshDurationSelect() {
			var $select = $('#tourism_duration');
			var $rows = $('#tourism-core-duration-options-list .tourism-core-duration-option-input');
			if (!$select.length) {
				return;
			}

			var currentValue = $select.val();
			var optionsHtml =
				'<option value="">' + (L10n.selectDuration || '') + '</option>';

			$rows.each(function () {
				var value = $.trim($(this).val());
				if (!value) {
					return;
				}
				var safeValue = $('<div>').text(value).html();
				var selected = value === currentValue ? ' selected' : '';
				optionsHtml += '<option value="' + safeValue + '"' + selected + '>' + safeValue + '</option>';
			});

			$select.html(optionsHtml);

			if (currentValue) {
				$select.val(currentValue);
			}
		}

		function tourismCoreRenumberDurationOptions() {
			$('#tourism-core-duration-options-list .tourism-core-duration-option-row').each(function (i) {
				$(this).find('.tourism-core-duration-option-input').attr('name', 'tourism_duration_options[' + i + ']');
			});
			tourismCoreRefreshDurationSelect();
		}

		function tourismCoreRefreshDifficultySelect() {
			var $select = $('#tourism_difficulty');
			var $rows = $('#tourism-core-difficulty-options-list .tourism-core-difficulty-option-input');
			if (!$select.length) {
				return;
			}

			var currentValue = $select.val();
			var optionsHtml =
				'<option value="">' + (L10n.selectDifficulty || '') + '</option>';

			$rows.each(function () {
				var value = $.trim($(this).val());
				if (!value) {
					return;
				}
				var safeValue = $('<div>').text(value).html();
				var selected = value === currentValue ? ' selected' : '';
				optionsHtml += '<option value="' + safeValue + '"' + selected + '>' + safeValue + '</option>';
			});

			$select.html(optionsHtml);

			if (currentValue) {
				$select.val(currentValue);
			}
		}

		function tourismCoreRenumberDifficultyOptions() {
			$('#tourism-core-difficulty-options-list .tourism-core-difficulty-option-row').each(function (i) {
				$(this).find('.tourism-core-difficulty-option-input').attr('name', 'tourism_difficulty_options[' + i + ']');
			});
			tourismCoreRefreshDifficultySelect();
		}

		$(document).on('click', '#tourism-core-add-duration-option', function (e) {
			e.preventDefault();
			$('#tourism-core-duration-options-list').append(
				'<div class="tourism-core-duration-option-row">' +
					'<input type="text" class="widefat tourism-core-duration-option-input" value="" placeholder="' +
					$('<div>').text(L10n.exampleFullDay || '').html() +
					'" />' +
					'<button type="button" class="button tourism-core-delete-duration-option">' +
					$('<div>').text(L10n.deleteLabel || '').html() +
					'</button>' +
					'</div>'
			);
			tourismCoreRenumberDurationOptions();
		});

		$(document).on('click', '.tourism-core-delete-duration-option', function (e) {
			e.preventDefault();
			$(this).closest('.tourism-core-duration-option-row').remove();
			tourismCoreRenumberDurationOptions();
		});

		$(document).on('input change', '.tourism-core-duration-option-input', function () {
			tourismCoreRefreshDurationSelect();
		});

		tourismCoreRenumberDurationOptions();

		$(document).on('click', '#tourism-core-add-difficulty-option', function (e) {
			e.preventDefault();
			$('#tourism-core-difficulty-options-list').append(
				'<div class="tourism-core-difficulty-option-row">' +
					'<input type="text" class="widefat tourism-core-difficulty-option-input" value="" placeholder="' +
					$('<div>').text(L10n.exampleModerate || '').html() +
					'" />' +
					'<button type="button" class="button tourism-core-delete-difficulty-option">' +
					$('<div>').text(L10n.deleteLabel || '').html() +
					'</button>' +
					'</div>'
			);
			tourismCoreRenumberDifficultyOptions();
		});

		$(document).on('click', '.tourism-core-delete-difficulty-option', function (e) {
			e.preventDefault();
			$(this).closest('.tourism-core-difficulty-option-row').remove();
			tourismCoreRenumberDifficultyOptions();
		});

		$(document).on('input change', '.tourism-core-difficulty-option-input', function () {
			tourismCoreRefreshDifficultySelect();
		});

		tourismCoreRenumberDifficultyOptions();

		function tourismCoreRenumberGallery() {
			var $g = $('#tourism-core-gallery-images-container');
			if (!$g.length) {
				return;
			}
			$g.children('.tourism-core-gallery-image-item').each(function (i) {
				var $item = $(this);
				$item.find('.tourism-core-gallery-field-id').attr('name', 'tourism_gallery_images[' + i + '][id]');
				$item.find('.tourism-core-gallery-field-url').attr('name', 'tourism_gallery_images[' + i + '][url]');
				$item.find('.tourism-core-image-details input[type="text"]').attr('name', 'tourism_gallery_images[' + i + '][title]');
				$item.find('.tourism-core-image-details textarea').attr('name', 'tourism_gallery_images[' + i + '][caption]');
			});
		}

		var $galleryContainer = $('#tourism-core-gallery-images-container');
		if ($galleryContainer.length) {
			$galleryContainer.sortable({
				items: '> .tourism-core-gallery-image-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					tourismCoreRenumberGallery();
				},
			});

			$(document).on('click', '#tourism-core-add-gallery-image', function (e) {
				e.preventDefault();
				var $tpl = $('#tourism-core-gallery-image-template').children('.tourism-core-gallery-image-item').first();
				if (!$tpl.length) {
					return;
				}
				var $clone = $tpl.clone(true, true);
				$galleryContainer.append($clone);
				tourismCoreRenumberGallery();
			});

			$(document).on('click', '.tourism-core-remove-gallery-image', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-gallery-image-item').remove();
				tourismCoreRenumberGallery();
			});

			$(document).on('click', '.tourism-core-replace-image', function (e) {
				e.preventDefault();
				var $item = $(this).closest('.tourism-core-gallery-image-item');
				var media = wp.media({
					title: L10n.galleryMediaTitle || 'Select image',
					button: { text: L10n.galleryMediaButton || 'Use image' },
					multiple: false,
				});
				media.on('select', function () {
					var att = media.state().get('selection').first().toJSON();
					var url =
						att.sizes && att.sizes.medium && att.sizes.medium.url ? att.sizes.medium.url : att.url;
					$item.find('.tourism-core-gallery-field-id').val(String(att.id));
					$item.find('.tourism-core-gallery-field-url').val(att.url);
					$item.find('.tourism-core-image-preview .tourism-core-no-image').remove();
					$item.find('.tourism-core-image-preview img').remove();
					$item.find('.tourism-core-image-preview').prepend('<img src="' + url + '" alt="" />');
				});
				media.open();
			});
		}

		function tourismCoreRenumberHighlights() {
			var $c = $('#tourism-core-highlights-container');
			if (!$c.length) {
				return;
			}
			$c.children('.tourism-core-highlight-item').each(function (i) {
				var $item = $(this);
				$item.attr('data-tourism-core-index', String(i));
				$item.find('.tourism-core-item-number').text(String(i + 1));
				$item.find('textarea[name^="tourism_highlights["]').attr('name', 'tourism_highlights[' + i + '][description]');
				$item.find('label[for^="tourism_core_highlight_description_"]').attr('for', 'tourism_core_highlight_description_' + i);
				$item.find('textarea[id^="tourism_core_highlight_description_"]').attr('id', 'tourism_core_highlight_description_' + i);
			});
		}

		function tourismCoreRenumberIncludes() {
			var $c = $('#tourism-core-includes-container');
			if (!$c.length) {
				return;
			}
			$c.children('.tourism-core-include-item').each(function (i) {
				var $item = $(this);
				$item.find('.tourism-core-label-item-num').text(String(i + 1));
				$item.find('.tourism-core-include-label-input').attr('name', 'tourism_includes_list[' + i + '][label]');
				$item.find('label[for^="tourism_include_label_"]').attr('for', 'tourism_include_label_' + i);
				$item.find('input.tourism-core-include-label-input').attr('id', 'tourism_include_label_' + i);
			});
		}

		function tourismCoreRenumberExcludes() {
			var $c = $('#tourism-core-excludes-container');
			if (!$c.length) {
				return;
			}
			$c.children('.tourism-core-exclude-item').each(function (i) {
				var $item = $(this);
				$item.find('.tourism-core-label-item-num').text(String(i + 1));
				$item.find('.tourism-core-exclude-label-input').attr('name', 'tourism_excludes_list[' + i + '][label]');
				$item.find('label[for^="tourism_exclude_label_"]').attr('for', 'tourism_exclude_label_' + i);
				$item.find('input.tourism-core-exclude-label-input').attr('id', 'tourism_exclude_label_' + i);
			});
		}

		var $incContainer = $('#tourism-core-includes-container');
		if ($incContainer.length) {
			$incContainer.sortable({
				handle: '.tourism-core-handle',
				items: '> .tourism-core-include-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					tourismCoreRenumberIncludes();
				},
			});
			$(document).on('click', '#tourism-core-add-include', function (e) {
				e.preventDefault();
				var tmpl = $('#tourism-core-tmpl-include-item').html();
				if (!tmpl) {
					return;
				}
				var idx = $incContainer.children('.tourism-core-include-item').length;
				var html = tmpl
					.split('{{tourism_index}}')
					.join(String(idx))
					.split('{{tourism_index_plus_one}}')
					.join(String(idx + 1));
				$incContainer.append(html);
				tourismCoreRenumberIncludes();
			});
			$(document).on('click', '.tourism-core-delete-include', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-include-item').remove();
				tourismCoreRenumberIncludes();
			});
		}

		var $excContainer = $('#tourism-core-excludes-container');
		if ($excContainer.length) {
			$excContainer.sortable({
				handle: '.tourism-core-handle',
				items: '> .tourism-core-exclude-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					tourismCoreRenumberExcludes();
				},
			});
			$(document).on('click', '#tourism-core-add-exclude', function (e) {
				e.preventDefault();
				var tmpl = $('#tourism-core-tmpl-exclude-item').html();
				if (!tmpl) {
					return;
				}
				var idx = $excContainer.children('.tourism-core-exclude-item').length;
				var html = tmpl
					.split('{{tourism_index}}')
					.join(String(idx))
					.split('{{tourism_index_plus_one}}')
					.join(String(idx + 1));
				$excContainer.append(html);
				tourismCoreRenumberExcludes();
			});
			$(document).on('click', '.tourism-core-delete-exclude', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-exclude-item').remove();
				tourismCoreRenumberExcludes();
			});
		}

		var $highlightsContainer = $('#tourism-core-highlights-container');
		if ($highlightsContainer.length) {
			$highlightsContainer.sortable({
				handle: '.tourism-core-handle',
				items: '> .tourism-core-highlight-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					tourismCoreRenumberHighlights();
				},
			});

			$(document).on('click', '#tourism-core-add-highlight', function (e) {
				e.preventDefault();
				var tmpl = $('#tourism-core-tmpl-highlight-item').html();
				if (!tmpl) {
					return;
				}
				var idx = $highlightsContainer.children('.tourism-core-highlight-item').length;
				var html = tmpl
					.split('{{tourism_index}}')
					.join(String(idx))
					.split('{{tourism_index_plus_one}}')
					.join(String(idx + 1));
				$highlightsContainer.append(html);
				tourismCoreRenumberHighlights();
			});

			$(document).on('click', '.tourism-core-delete-highlight', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-highlight-item').remove();
				tourismCoreRenumberHighlights();
			});

			tourismCoreRenumberHighlights();
		}

		function tourismCoreRenumberBring() {
			var $c = $('#tourism-core-bring-items-container');
			if (!$c.length) {
				return;
			}
			$c.children('.tourism-core-bring-item').each(function (i) {
				var $item = $(this);
				$item.find('.tourism-core-bring-field-id').attr('name', 'tourism_bring_items[' + i + '][id]');
				$item.find('.tourism-core-bring-field-url').attr('name', 'tourism_bring_items[' + i + '][url]');
				$item
					.find('.tourism-core-bring-field-title')
					.attr('name', 'tourism_bring_items[' + i + '][title]')
					.attr('id', 'tourism_bring_title_' + i);
				$item
					.find('.tourism-core-bring-field-description')
					.attr('name', 'tourism_bring_items[' + i + '][description]')
					.attr('id', 'tourism_bring_description_' + i);
				$item.find('label[for^="tourism_bring_title_"]').attr('for', 'tourism_bring_title_' + i);
				$item.find('label[for^="tourism_bring_description_"]').attr('for', 'tourism_bring_description_' + i);
			});
		}

		var $bringContainer = $('#tourism-core-bring-items-container');
		if ($bringContainer.length) {
			$bringContainer.sortable({
				items: '> .tourism-core-bring-item',
				cancel: 'input,textarea,button,select,option',
				update: function () {
					tourismCoreRenumberBring();
				},
			});

			$(document).on('click', '#tourism-core-add-bring-item', function (e) {
				e.preventDefault();
				var $tpl = $('#tourism-core-bring-item-template').children('.tourism-core-bring-item').first();
				if (!$tpl.length) {
					return;
				}
				var $clone = $tpl.clone(true, true);
				$bringContainer.append($clone);
				tourismCoreRenumberBring();
			});

			$(document).on('click', '.tourism-core-remove-bring-item', function (e) {
				e.preventDefault();
				$(this).closest('.tourism-core-bring-item').remove();
				tourismCoreRenumberBring();
			});

			$(document).on('click', '.tourism-core-replace-bring-image', function (e) {
				e.preventDefault();
				var $item = $(this).closest('.tourism-core-bring-item');
				var media = wp.media({
					title: L10n.bringMediaTitle || 'Select icon image',
					button: { text: L10n.bringMediaButton || 'Use image' },
					library: { type: 'image' },
					multiple: false,
				});
				media.on('select', function () {
					var att = media.state().get('selection').first().toJSON();
					var url =
						att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url
							? att.sizes.thumbnail.url
							: att.url;
					$item.find('.tourism-core-bring-field-id').val(String(att.id));
					$item.find('.tourism-core-bring-field-url').val(att.url);
					$item.find('.tourism-core-no-image').remove();
					$item.find('.tourism-core-bring-preview img').remove();
					$item.find('.tourism-core-bring-preview').prepend('<img src="' + url + '" alt="" />');
				});
				media.open();
			});
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
				if (!postId) {
					return;
				}

				var $row = $('#post-' + postId);
				var $editRow = $('#edit-' + postId);
				if (!$row.length || !$editRow.length) {
					return;
				}

				var current = $.trim($row.find('td.column-tourism_tour_order').text());
				$editRow.find('input[name="tourism_tour_order"]').val(current !== '' ? parseInt(current, 10) : 0);
			};
		}
	});
})(jQuery);
