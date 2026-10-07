/**
 * Tour FAQs metabox (sortable repeater + dynamic WYSIWYG answers).
 */
(function ($) {
	'use strict';

	var L10n =
		typeof TourismCoreTourFaqs !== 'undefined' && TourismCoreTourFaqs
			? TourismCoreTourFaqs
			: {};


	function tourismCoreFaqSaveEditors() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreFaqInitEditor(editorId) {
		if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if ($('#' + editorId).closest('.wp-editor-wrap').length || (typeof tinymce !== 'undefined' && tinymce.get(editorId))) {
			return;
		}

		wp.editor.initialize(editorId, window.TourismCoreRichEditorSettings());
	}

	function tourismCoreFaqDestroyEditor(editorId) {
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

	function tourismCoreFaqDestroyAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreFaqDestroyEditor($(this).attr('id'));
		});
	}

	function tourismCoreFaqInitAllEditors($container) {
		$container.find('.tourism-core-wysiwyg').each(function () {
			tourismCoreFaqInitEditor($(this).attr('id'));
		});
	}

	function tourismCoreRenumberFaqs($container, reinitEditors) {
		if (!$container.length) {
			return;
		}

		tourismCoreFaqSaveEditors();
		tourismCoreFaqDestroyAllEditors($container);

		$container.children('.tourism-core-faq-item').each(function (i) {
			var $item = $(this);

			$item.attr('data-tourism-core-index', String(i));
			$item.find('.tourism-core-item-number').text(String(i + 1));
			$item.find('input[name^="tourism_faqs["]').attr('name', 'tourism_faqs[' + i + '][question]');
			$item.find('textarea[name^="tourism_faqs["]').attr('name', 'tourism_faqs[' + i + '][answer]');
			$item.find('label[for^="tourism_core_question_"]').attr('for', 'tourism_core_question_' + i);
			$item.find('input[id^="tourism_core_question_"]').attr('id', 'tourism_core_question_' + i);
			$item.find('label[for^="tourism_core_answer_"]').attr('for', 'tourism_core_answer_' + i);
			$item.find('textarea[id^="tourism_core_answer_"]').attr('id', 'tourism_core_answer_' + i);
		});

		if (reinitEditors !== false) {
			tourismCoreFaqInitAllEditors($container);
		}
	}

	$(function () {
		var $faqContainer = $('#tourism-core-faqs-container');
		if (!$faqContainer.length) {
			return;
		}

		tourismCoreFaqInitAllEditors($faqContainer);

		$faqContainer.sortable({
			handle: '.tourism-core-handle',
			items: '> .tourism-core-faq-item',
			cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
			start: function () {
				tourismCoreFaqSaveEditors(); tourismCoreFaqDestroyAllEditors($faqContainer);
			},
			stop: function () { tourismCoreRenumberFaqs($faqContainer); $(document).trigger('tourism:content-changed'); },
		});

		$(document).on('click', '#tourism-core-add-faq', function (e) {
			e.preventDefault();

			var tmpl = $('#tourism-core-tmpl-faq-item').html();
			if (!tmpl) {
				return;
			}

			var idx = $faqContainer.children('.tourism-core-faq-item').length;
			var html = tmpl
				.split('{{tourism_index}}')
				.join(String(idx))
				.split('{{tourism_index_plus_one}}')
				.join(String(idx + 1));

			$faqContainer.append(html);
			tourismCoreFaqInitEditor('tourism_core_answer_' + idx);
		});

		$(document).on('click', '.tourism-core-delete-faq', function (e) {
			e.preventDefault();

			var confirmMessage =
				L10n.deleteConfirm || 'Are you sure you want to delete this FAQ?';
			if (!window.confirm(confirmMessage)) {
				return;
			}

			var $item = $(this).closest('.tourism-core-faq-item');
			var editorId = $item.find('.tourism-core-wysiwyg').attr('id');

			tourismCoreFaqDestroyEditor(editorId);
			$item.remove();
			tourismCoreRenumberFaqs($faqContainer);
		});

		$('#post').on('submit', function () {
			tourismCoreFaqSaveEditors();
		});
	});
})(jQuery);
