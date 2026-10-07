/**
 * Tour pricing info metabox (sortable repeater + dynamic WYSIWYG).
 */
(function ($) {
	'use strict';

	var editorSettings = {
		tinymce: {
			wpautop: true,
			plugins: 'lists link paste wordpress',
			toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink',
			toolbar2: '',
			height: 150,
		},
		quicktags: true,
		mediaButtons: false,
	};

	function tourismCorePricingInfoSaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCorePricingInfoInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
	}

	function tourismCorePricingInfoDestroyEditor(editorId) {
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

	function tourismCorePricingInfoDestroyAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCorePricingInfoDestroyEditor($(this).attr('id'));
		});
	}

	function tourismCorePricingInfoInitAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCorePricingInfoInitEditor($(this).attr('id'));
		});
	}

	function tourismCoreRenumberPricingInfo($container, reinitEditors) {
		if (!$container.length) {
			return;
		}

		tourismCorePricingInfoSaveEditors();
		tourismCorePricingInfoDestroyAllEditors($container);

		$container.children('.tourism-core-pricing-info-item').each(function (i) {
			var $item = $(this);
			$item.find('.tourism-core-label-item-num').text(String(i + 1));
			$item.find('.tourism-core-pricing-info-title-input').attr('name', 'tourism_pricing_info_items[' + i + '][title]');
			$item.find('.tourism-core-pricing-info-title-input').attr('id', 'tourism_pricing_info_title_' + i);
			$item.find('label[for^="tourism_pricing_info_title_"]').attr('for', 'tourism_pricing_info_title_' + i);
			$item.find('.tourism-core-wysiwyg').attr('name', 'tourism_pricing_info_items[' + i + '][content]');
			$item.find('.tourism-core-wysiwyg').attr('id', 'tourism_pricing_info_content_' + i);
			$item.find('label[for^="tourism_pricing_info_content_"]').attr('for', 'tourism_pricing_info_content_' + i);
		});

		if (reinitEditors !== false) {
			tourismCorePricingInfoInitAllEditors($container);
		}
	}

	$(function () {
		var $pricingInfoContainer = $('#tourism-core-pricing-info-container');
		if (!$pricingInfoContainer.length) {
			return;
		}

		tourismCorePricingInfoInitAllEditors($pricingInfoContainer);

		$pricingInfoContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-pricing-info-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			update: function () {
				tourismCoreRenumberPricingInfo($pricingInfoContainer);
			},
		});

		$(document).on('click', '#tourism-core-add-pricing-info', function (e) {
			e.preventDefault();

			var tmpl = $('#tourism-core-tmpl-pricing-info-item').html();
			if (!tmpl) {
				return;
			}

			var idx = $pricingInfoContainer.children('.tourism-core-pricing-info-item').length;
			var html = tmpl
				.split('{{tourism_index}}')
				.join(String(idx))
				.split('{{tourism_index_plus_one}}')
				.join(String(idx + 1));

			$pricingInfoContainer.append(html);
			tourismCorePricingInfoInitEditor('tourism_pricing_info_content_' + idx);
		});

		$(document).on('click', '.tourism-core-delete-pricing-info', function (e) {
			e.preventDefault();

			var $item = $(this).closest('.tourism-core-pricing-info-item');
			var editorId = $item.find('.tourism-core-wysiwyg').attr('id');

			tourismCorePricingInfoDestroyEditor(editorId);
			$item.remove();
			tourismCoreRenumberPricingInfo($pricingInfoContainer);
		});

		$('#post').on('submit', function () {
			tourismCorePricingInfoSaveEditors();
		});
	});
})(jQuery);