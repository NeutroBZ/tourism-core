/** One full WordPress editor per Experience field, including media insertion. */
(function ($) {
	'use strict';
	var mounted = {};
	window.TourismCoreExperienceEditors = {
		mount: function (config) {
			$(function () {
				var $container = $('#' + config.containerId);
				if (!$container.length || mounted[config.containerId]) return;
				mounted[config.containerId] = true;
				function fields() { return $container.find('.tourism-experience-content'); }
				function save() {
					fields().each(function () { if (wp.editor.getContent) wp.editor.getContent(this.id); });
				}
				function initialize($field) {
					// PHP-rendered wp_editor() already has its own bootstrap and controls.
					// Initialize only a bare textarea created by adding or moving a row.
					if (!$field.length || $field.closest('.wp-editor-wrap').length || (window.tinymce && tinymce.get($field.attr('id')))) return;
					wp.editor.initialize($field.attr('id'), { tinymce: true, quicktags: true, mediaButtons: true });
				}
				function initializeAll() { fields().each(function () { initialize($(this)); }); }
				function removeAll() {
					save();
					fields().each(function () { wp.editor.remove(this.id); });
				}
				function renumber() {
					$container.children('.tourism-experience-item').each(function (index) {
						var $item = $(this);
						var titleId = config.idPrefix + '_title_' + index;
						var contentId = config.idPrefix + '_content_' + index;
						$item.attr('data-tourism-core-index', String(index));
						$item.find('.tourism-core-item-number').text(String(index + 1));
						$item.find('.tourism-experience-title').attr({ id: titleId, name: config.postKey + '[' + index + '][title]' });
						$item.find('.tourism-experience-content').attr({ id: contentId, name: config.postKey + '[' + index + '][content]' });
						$item.find('label').first().attr('for', titleId);
						$item.find('label').last().attr('for', contentId);
					});
					initializeAll();
					$(document).trigger('tourism:content-changed');
				}
				initializeAll();
				if ($container.hasClass('ui-sortable')) $container.sortable('destroy');
				$container.sortable({
					handle: '.tourism-core-handle',
					items: '> .tourism-experience-item',
					cancel: 'input,textarea,button,select,option,a,.wp-editor-wrap,.mce-container',
					start: removeAll,
					stop: renumber
				});
				$('#' + config.addId).on('click.tourismExperience', function () {
					var template = $('#' + config.templateId).html();
					if (!template) return;
					var index = $container.children('.tourism-experience-item').length;
					var $item = $(template.split('{{tourism_index}}').join(String(index)).split('{{tourism_index_plus_one}}').join(String(index + 1)));
					$container.append($item);
					initialize($item.find('.tourism-experience-content'));
					$item.find('.tourism-experience-title').trigger('focus');
					$(document).trigger('tourism:content-changed');
				});
				$container.on('click.tourismExperience', '.' + config.deleteClass, function () {
					removeAll();
					$(this).closest('.tourism-experience-item').remove();
					renumber();
				});
				$('#post').on('submit.tourismExperience-' + config.containerId, save);
			});
		}
	};
})(jQuery);
