/**
 * Tour "What to Bring" metabox (sortable repeater + dynamic WYSIWYG).
 */
(function ($) {
	'use strict';

	var editorSettings = {
		tinymce: true,
		quicktags: true,
		mediaButtons: true,
	};

	function tourismCoreBringSaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreBringGetContentField($item) {
		// El campo de contenido es el único <textarea> dentro de ".inside",
		// tanto si viene del wp_editor() inicial como del template dinámico.
		return $item.find('.inside textarea').first();
	}

	function tourismCoreBringInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
	}

	function tourismCoreBringDestroyEditor(editorId) {
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

	function tourismCoreBringDestroyAllEditors($container) {
		$container.children('.tourism-core-what-to-bring-item').each(function () {
			var $item = $(this);
			var $field = tourismCoreBringGetContentField($item);
			tourismCoreBringDestroyEditor($field.attr('id'));
		});
	}

	function tourismCoreBringInitAllEditors($container) {
		$container.children('.tourism-core-what-to-bring-item').each(function () {
			var $item = $(this);
			var $field = tourismCoreBringGetContentField($item);
			tourismCoreBringInitEditor($field.attr('id'));
		});
	}

	function tourismCoreRenumberBring($container) {
		if (!$container.length) {
			return;
		}

		tourismCoreBringSaveEditors();
		tourismCoreBringDestroyAllEditors($container);

		$container.children('.tourism-core-what-to-bring-item').each(function (i) {
			var $item = $(this);

			$item.find('.tourism-core-what-to-bring-item-num').text(String(i + 1));

			var $title = $item.find('.tourism-core-what-to-bring-title-input');
			$title.attr('name', 'tourism_what_to_bring_items[' + i + '][title]');
			$title.attr('id', 'tourism_what_to_bring_title_' + i);

			var $content = tourismCoreBringGetContentField($item);
			$content.attr('name', 'tourism_what_to_bring_items[' + i + '][content]');
			$content.attr('id', 'tourism_what_to_bring_content_' + i);
		});

		tourismCoreBringInitAllEditors($container);
	}

	$(function () {
		var $bringContainer = $('#tourism-core-what-to-bring-container');
		if (!$bringContainer.length) {
			return;
		}

		// NOTA: los items ya renderizados por wp_editor() se auto-inicializan
		// solos con el bootstrap estándar de WordPress; no hace falta (ni
		// conviene) volver a inicializarlos aquí.

		if ($bringContainer.hasClass('ui-sortable')) {
			$bringContainer.sortable('destroy');
		}

		$bringContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-what-to-bring-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			update: function () {
				tourismCoreRenumberBring($bringContainer);
			},
		});

		$(document)
			.off('click.tourismCoreBring', '#tourism-core-add-what-to-bring')
			.on('click.tourismCoreBring', '#tourism-core-add-what-to-bring', function (e) {
				e.preventDefault();

				var tmpl = $('#tourism-core-tmpl-what-to-bring-item').html();
				if (!tmpl) {
					return;
				}

				var idx = $bringContainer.children('.tourism-core-what-to-bring-item').length;
				var html = tmpl
					.split('{{tourism_index}}')
					.join(String(idx))
					.split('{{tourism_index_plus_one}}')
					.join(String(idx + 1));

				var $newItem = $(html);
				var $newContent = tourismCoreBringGetContentField($newItem);
				$newContent.attr('id', 'tourism_what_to_bring_content_' + idx);

				$bringContainer.append($newItem);
				tourismCoreBringInitEditor('tourism_what_to_bring_content_' + idx);
			});

		$(document)
			.off('click.tourismCoreBring', '.tourism-core-delete-what-to-bring')
			.on('click.tourismCoreBring', '.tourism-core-delete-what-to-bring', function (e) {
				e.preventDefault();

				var $item = $(this).closest('.tourism-core-what-to-bring-item');
				var $content = tourismCoreBringGetContentField($item);

				tourismCoreBringDestroyEditor($content.attr('id'));
				$item.remove();
				tourismCoreRenumberBring($bringContainer);
			});

		$('#post').off('submit.tourismCoreBring').on('submit.tourismCoreBring', function () {
			tourismCoreBringSaveEditors();
		});
	});
})(jQuery);