/** Match dynamic editors to the native editor used for detailed recommendations. */
(function () {
	'use strict';
	window.TourismCoreRichEditorSettings = function () {
		var initial = window.tinyMCEPreInit || {};
		var native = (initial.mceInit || {}).tourism_what_to_bring_content_0 || {};
		var tags = (initial.qtInit || {}).tourism_what_to_bring_content_0 || {};
		var defaults = wp.editor.getDefaultSettings();
		return {
			tinymce: {
				plugins: native.plugins || defaults.tinymce.plugins,
				toolbar1: native.toolbar1 || 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,wp_adv',
				toolbar2: native.toolbar2 || 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
				height: 220
			},
			quicktags: { buttons: tags.buttons || 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close' },
			mediaButtons: true
		};
	};
}());
