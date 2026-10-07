/**
 * Tour highlights metabox (sortable repeater + dynamic WYSIWYG).
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

	function tourismCoreHighlightsSaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreHighlightsInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
	}

	function tourismCoreHighlightsDestroyEditor(editorId) {
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

	function tourismCoreHighlightsDestroyAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreHighlightsDestroyEditor($(this).attr('id'));
		});
	}

	function tourismCoreHighlightsInitAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreHighlightsInitEditor($(this).attr('id'));
		});
	}

	function tourismCoreRenumberHighlights($container, reinitEditors) {
		if (!$container.length) {
			return;
		}

		tourismCoreHighlightsSaveEditors();
		tourismCoreHighlightsDestroyAllEditors($container);

		$container.children('.tourism-core-highlight-item').each(function (i) {
			var $item = $(this);
			$item.attr('data-tourism-core-index', String(i));
			$item.find('.tourism-core-item-number').text(String(i + 1));

			// Título: la clase real en el HTML es "tourism-core-repeater-title".
			$item.find('.tourism-core-repeater-title').attr('name', 'tourism_highlights_items[' + i + '][title]');
			$item.find('.tourism-core-repeater-title').attr('id', 'tourism_core_te_highlights_title_' + i);
			$item.find('label[for^="tourism_core_te_highlights_title_"]').attr('for', 'tourism_core_te_highlights_title_' + i);

			// Contenido: debe llevar el sufijo "_items" para que el backend lo reciba (tourism_highlights_items).
			$item.find('.tourism-core-wysiwyg').attr('name', 'tourism_highlights_items[' + i + '][content]');
			$item.find('.tourism-core-wysiwyg').attr('id', 'tourism_core_te_highlights_content_' + i);
			$item.find('label[for^="tourism_core_te_highlights_content_"]').attr('for', 'tourism_core_te_highlights_content_' + i);
		});

		if (reinitEditors !== false) {
			tourismCoreHighlightsInitAllEditors($container);
		}
	}

	$(function () {
		var $highlightsContainer = $('#tourism-core-highlights-container');
		if (!$highlightsContainer.length) {
			return;
		}

		tourismCoreHighlightsInitAllEditors($highlightsContainer);

		// Evita re-inicializar sortable si el script se carga más de una vez.
		if ($highlightsContainer.hasClass('ui-sortable')) {
			$highlightsContainer.sortable('destroy');
		}

		$highlightsContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-highlight-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			update: function () {
				tourismCoreRenumberHighlights($highlightsContainer);
			},
		});

		// Namespacing + .off() antes de .on(): evita que si este script se
		// carga dos veces (enqueue duplicado), el click quede registrado
		// dos veces y se agreguen/eliminen 2 items en vez de 1.
		$(document)
			.off('click.tourismCoreHighlights', '#tourism-core-add-highlight')
			.on('click.tourismCoreHighlights', '#tourism-core-add-highlight', function (e) {
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
				tourismCoreHighlightsInitEditor('tourism_core_te_highlights_content_' + idx);
			});

		$(document)
			.off('click.tourismCoreHighlights', '.tourism-core-delete-highlight')
			.on('click.tourismCoreHighlights', '.tourism-core-delete-highlight', function (e) {
				e.preventDefault();

				var $item = $(this).closest('.tourism-core-highlight-item');
				var editorId = $item.find('.tourism-core-wysiwyg').attr('id');

				tourismCoreHighlightsDestroyEditor(editorId);
				$item.remove();
				tourismCoreRenumberHighlights($highlightsContainer);
			});

		$('#post').off('submit.tourismCoreHighlights').on('submit.tourismCoreHighlights', function () {
			tourismCoreHighlightsSaveEditors();
		});
	});
})(jQuery);