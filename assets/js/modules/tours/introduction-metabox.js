/**
 * Tour introduction metabox (static WYSIWYG description).
 */
(function ($) {
	'use strict';

	var editorId = 'tourism_intro_description';


	function tourismCoreIntroductionSaveEditor() {
		if (typeof tinymce !== 'undefined') {
			tinymce.triggerSave();
		}
	}

	function tourismCoreIntroductionInitEditor() {
		if (!$('#' + editorId).length) {
			return;
		}

		if (typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
			return;
		}

		if ($('#' + editorId).closest('.wp-editor-wrap').length || (typeof tinymce !== 'undefined' && tinymce.get(editorId))) {
			return;
		}

		wp.editor.initialize(editorId, window.TourismCoreRichEditorSettings());
	}

	$(function () {
		tourismCoreIntroductionInitEditor();

		$('#post').on('submit', function () {
			tourismCoreIntroductionSaveEditor();
		});
	});
})(jQuery);
