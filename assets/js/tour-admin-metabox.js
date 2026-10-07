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

		// ════════════════════════════════════════════
		// HERO IMAGE
		// ════════════════════════════════════════════
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

		// ════════════════════════════════════════════
		// PRICING SYNC
		// ════════════════════════════════════════════
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

		// ════════════════════════════════════════════
		// DURATION BUILDER
		// ════════════════════════════════════════════
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

		// ════════════════════════════════════════════
		// DIFFICULTY BUILDER
		// ════════════════════════════════════════════
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

		// ════════════════════════════════════════════
		// GROUP SIZE BUILDER
		// ════════════════════════════════════════════
		function tourismCoreRefreshGroupSizeSelect() {
			var $select = $('#tourism_group_size');
			var $rows = $('#tourism-core-group-size-options-list .tourism-core-group-size-option-input');
			if (!$select.length) {
				return;
			}

			var currentValue = $select.val();
			var optionsHtml =
				'<option value="">' + (L10n.selectGroupSize || '') + '</option>';

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

		function tourismCoreRenumberGroupSizeOptions() {
			$('#tourism-core-group-size-options-list .tourism-core-group-size-option-row').each(function (i) {
				$(this).find('.tourism-core-group-size-option-input').attr('name', 'tourism_group_size_options[' + i + ']');
			});
			tourismCoreRefreshGroupSizeSelect();
		}

		$(document).on('click', '#tourism-core-add-group-size-option', function (e) {
			e.preventDefault();
			$('#tourism-core-group-size-options-list').append(
				'<div class="tourism-core-group-size-option-row">' +
					'<input type="text" class="widefat tourism-core-group-size-option-input" value="" placeholder="' +
					$('<div>').text(L10n.exampleGroupSize || '').html() +
					'" />' +
					'<button type="button" class="button tourism-core-delete-group-size-option">' +
					$('<div>').text(L10n.deleteLabel || '').html() +
					'</button>' +
					'</div>'
			);
			tourismCoreRenumberGroupSizeOptions();
		});

		$(document).on('click', '.tourism-core-delete-group-size-option', function (e) {
			e.preventDefault();
			$(this).closest('.tourism-core-group-size-option-row').remove();
			tourismCoreRenumberGroupSizeOptions();
		});

		$(document).on('input change', '.tourism-core-group-size-option-input', function () {
			tourismCoreRefreshGroupSizeSelect();
		});

		tourismCoreRenumberGroupSizeOptions();

		// ════════════════════════════════════════════
		// MAX ELEVATION BUILDER
		// ════════════════════════════════════════════
		function tourismCoreRefreshMaxElevationSelect() {
			var $select = $('#tourism_max_elevation');
			var $rows = $('#tourism-core-max-elevation-options-list .tourism-core-max-elevation-option-input');
			if (!$select.length) {
				return;
			}

			var currentValue = $select.val();
			var optionsHtml =
				'<option value="">' + (L10n.selectMaxElevation || '') + '</option>';

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

		function tourismCoreRenumberMaxElevationOptions() {
			$('#tourism-core-max-elevation-options-list .tourism-core-max-elevation-option-row').each(function (i) {
				$(this).find('.tourism-core-max-elevation-option-input').attr('name', 'tourism_max_elevation_options[' + i + ']');
			});
			tourismCoreRefreshMaxElevationSelect();
		}

		$(document).on('click', '#tourism-core-add-max-elevation-option', function (e) {
			e.preventDefault();
			$('#tourism-core-max-elevation-options-list').append(
				'<div class="tourism-core-max-elevation-option-row">' +
					'<input type="text" class="widefat tourism-core-max-elevation-option-input" value="" placeholder="' +
					$('<div>').text(L10n.exampleMaxElevation || '').html() +
					'" />' +
					'<button type="button" class="button tourism-core-delete-max-elevation-option">' +
					$('<div>').text(L10n.deleteLabel || '').html() +
					'</button>' +
					'</div>'
			);
			tourismCoreRenumberMaxElevationOptions();
		});

		$(document).on('click', '.tourism-core-delete-max-elevation-option', function (e) {
			e.preventDefault();
			$(this).closest('.tourism-core-max-elevation-option-row').remove();
			tourismCoreRenumberMaxElevationOptions();
		});

		$(document).on('input change', '.tourism-core-max-elevation-option-input', function () {
			tourismCoreRefreshMaxElevationSelect();
		});

		tourismCoreRenumberMaxElevationOptions();

		// ════════════════════════════════════════════
		// GALLERY (handled by assets/js/gallery-metabox.js)
		// ════════════════════════════════════════════

		// ════════════════════════════════════════════
		// BRING ITEMS
		// ════════════════════════════════════════════
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

		// ════════════════════════════════════════════
		// QUICK EDIT ORDER
		// ════════════════════════════════════════════
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