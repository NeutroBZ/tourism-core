/**
 * Tour what to expect metabox (sortable repeater + dynamic WYSIWYG).
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

	function tourismCoreWhatToExpectSaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreWhatToExpectInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
	}

	function tourismCoreWhatToExpectDestroyEditor(editorId) {
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

	function tourismCoreWhatToExpectDestroyAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreWhatToExpectDestroyEditor($(this).attr('id'));
		});
	}

	function tourismCoreWhatToExpectInitAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreWhatToExpectInitEditor($(this).attr('id'));
		});
	}

	function tourismCoreRenumberWhatToExpect($container, reinitEditors) {
		if (!$container.length) {
			return;
		}

		tourismCoreWhatToExpectSaveEditors();
		tourismCoreWhatToExpectDestroyAllEditors($container);

		$container.children('.tourism-core-what-to-expect-item').each(function (i) {
			var $item = $(this);
			$item.find('.tourism-core-what-to-expect-item-num').text(String(i + 1));
			$item.find('.tourism-core-what-to-expect-title-input').attr('name', 'tourism_what_to_expect_items[' + i + '][title]');
			$item.find('.tourism-core-what-to-expect-title-input').attr('id', 'tourism_what_to_expect_title_' + i);
			$item.find('label[for^="tourism_what_to_expect_title_"]').attr('for', 'tourism_what_to_expect_title_' + i);
			$item.find('.tourism-core-wysiwyg').attr('name', 'tourism_what_to_expect_items[' + i + '][content]');
			$item.find('.tourism-core-wysiwyg').attr('id', 'tourism_what_to_expect_content_' + i);
			$item.find('label[for^="tourism_what_to_expect_content_"]').attr('for', 'tourism_what_to_expect_content_' + i);
		});

		if (reinitEditors !== false) {
			tourismCoreWhatToExpectInitAllEditors($container);
		}
	}

	$(function () {
		var $whatToExpectContainer = $('#tourism-core-what-to-expect-container');
		if (!$whatToExpectContainer.length) {
			return;
		}

		tourismCoreWhatToExpectInitAllEditors($whatToExpectContainer);

		$whatToExpectContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-what-to-expect-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			update: function () {
				tourismCoreRenumberWhatToExpect($whatToExpectContainer);
			},
		});

		$(document).on('click', '#tourism-core-add-what-to-expect', function (e) {
			e.preventDefault();

			var tmpl = $('#tourism-core-tmpl-what-to-expect-item').html();
			if (!tmpl) {
				return;
			}

			var idx = $whatToExpectContainer.children('.tourism-core-what-to-expect-item').length;
			var html = tmpl
				.split('{{tourism_index}}')
				.join(String(idx))
				.split('{{tourism_index_plus_one}}')
				.join(String(idx + 1));

			$whatToExpectContainer.append(html);
			tourismCoreWhatToExpectInitEditor('tourism_what_to_expect_content_' + idx);
		});

		$(document).on('click', '.tourism-core-delete-what-to-expect', function (e) {
			e.preventDefault();

			var $item = $(this).closest('.tourism-core-what-to-expect-item');
			var editorId = $item.find('.tourism-core-wysiwyg').attr('id');

			tourismCoreWhatToExpectDestroyEditor(editorId);
			$item.remove();
			tourismCoreRenumberWhatToExpect($whatToExpectContainer);
		});

		$('#post').on('submit', function () {
			tourismCoreWhatToExpectSaveEditors();
		});
	});
})(jQuery);