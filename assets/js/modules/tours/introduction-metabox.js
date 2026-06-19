/**
 * Tour introduction metabox (static WYSIWYG description).
 */
(function ($) {
	'use strict';

	var editorId = 'tourism_intro_description';

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

		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			return;
		}

		wp.editor.initialize(editorId, editorSettings);
	}

	$(function () {
		tourismCoreIntroductionInitEditor();

		$('#post').on('submit', function () {
			tourismCoreIntroductionSaveEditor();
		});
	});
})(jQuery);
