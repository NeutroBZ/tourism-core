/**
	 * Shared gallery metabox behaviour (sortable images + WP media selection).
 * Configured via window.TourismCoreGalleries array of config objects.
 */
(function ($) {
	'use strict';

	function renumber(config) {
		var $c = $('#' + config.containerId);
		if (!$c.length) {
			return;
		}
		$c.children('.' + config.itemClass).each(function (i) {
			var $item = $(this);
			$item.find('.' + config.fieldIdClass).attr('name', config.postKey + '[' + i + '][id]');
			$item.find('.' + config.fieldUrlClass).attr('name', config.postKey + '[' + i + '][url]');
			$item.find('.' + config.detailsClass + ' input[type="text"]').attr('name', config.postKey + '[' + i + '][title]');
			$item.find('.' + config.detailsClass + ' textarea').attr('name', config.postKey + '[' + i + '][caption]');
		});
	}

	function bindGallery(config) {
		var $c = $('#' + config.containerId);
		if (!$c.length) {
			return;
		}

		function setImage($item, att) {
			var url = att.sizes && att.sizes.medium && att.sizes.medium.url ? att.sizes.medium.url : att.url;
			$item.find('.' + config.fieldIdClass).val(String(att.id));
			$item.find('.' + config.fieldUrlClass).val(att.url);
			var $preview = $item.find('.' + config.previewClass);
			$preview.find('.' + config.noImageClass + ', img').remove();
			$preview.prepend($('<img>', { src: url, alt: '' }));
		}

		$c.sortable({
			items: '> .' + config.itemClass,
			cancel: 'input,textarea,button,select,option',
			update: function () {
				renumber(config);
			}
		});

		$(document).on('click', '#' + config.addId, function (e) {
			e.preventDefault();
			var $tpl = $('#' + config.templateId).children('.' + config.itemClass).first();
			if (!$tpl.length) {
				return;
			}
			var media = wp.media({
				title: 'Seleccionar imágenes',
				button: { text: 'Añadir a la galería' },
				library: { type: 'image' },
				multiple: true
			});
			media.on('select', function () {
				media.state().get('selection').each(function (attachment) {
					var att = attachment.toJSON();
					var $item = $tpl.clone();
					setImage($item, att);
					$item.find('.' + config.detailsClass + ' input[type="text"]').val(att.title || '');
					$item.find('.' + config.detailsClass + ' textarea').val(att.caption || '');
					$c.append($item);
				});
				renumber(config);
			});
			media.open();
		});

		$(document).on('click', '.' + config.removeClass, function (e) {
			e.preventDefault();
			$(this).closest('.' + config.itemClass).remove();
			renumber(config);
		});

		$(document).on('click', '.' + config.replaceClass, function (e) {
			e.preventDefault();
			var $item = $(this).closest('.' + config.itemClass);
			var media = wp.media({
				title: 'Select image',
				button: { text: 'Use image' },
				library: { type: 'image' },
				multiple: false
			});
			media.on('select', function () {
				var att = media.state().get('selection').first().toJSON();
				setImage($item, att);
			});
			media.open();
		});

		renumber(config);
	}

	$(function () {
		var configs = window.TourismCoreGalleries || [];
		if (!Array.isArray(configs)) {
			configs = configs ? [configs] : [];
		}
		$.each(configs, function (_, config) {
			if (config && config.containerId) {
				bindGallery(config);
			}
		});
	});
})(jQuery);
