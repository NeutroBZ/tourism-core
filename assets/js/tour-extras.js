/** Repeatable tour videos/reviews. Keep original indices until the form is saved. */
(function ($) {
	'use strict';
	$(function () {
		var $box = $('#tourism-tour-extras');
		if (!$box.length) return;
		$box.find('.tourism-extras-no-js, .tourism-extras-remove-fallback').prop('hidden', true);
		$box.find('.tourism-extras-row[data-placeholder="true"]').remove();
		$box.find('.tourism-extras-add, .tourism-extras-remove').prop('hidden', false);
		$box.on('click', '.tourism-extras-add', function () {
			var $group = $(this).closest('.tourism-extras-group');
			var index = Number($group.attr('data-next-index'));
			var html = $group.find('.tourism-extras-template').html().replace(/__INDEX__/g, String(index));
			var $row = $(html).appendTo($group.find('.tourism-extras-rows'));
			$group.attr('data-next-index', index + 1);
			$row.find('.tourism-extras-remove-fallback').prop('hidden', true);
			$row.find('.tourism-extras-remove').prop('hidden', false);
			$row.find('input, textarea, select').first().trigger('focus');
			$group.find('.tourism-extras-status').text('Elemento añadido. Guarda el tour para conservarlo.');
			$(document).trigger('tourism:content-changed');
		});
		$box.on('click', '.tourism-extras-remove', function () {
			var $row = $(this).closest('.tourism-extras-row');
			var $group = $row.closest('.tourism-extras-group');
			// Retain a deletion marker at the original index, so imported IDs and invalid-row fallback stay attached to the correct item.
			$row.find('input, textarea, select').prop('disabled', true);
			$row.find('.tourism-extras-remove-flag').prop({ disabled: false, checked: true });
			$row.prop('hidden', true);
			$group.find('.tourism-extras-add').trigger('focus');
			$group.find('.tourism-extras-status').text('Elemento eliminado de la lista. Guarda el tour para aplicar el cambio.');
			$(document).trigger('tourism:content-changed');
		});
	});
})(jQuery);
