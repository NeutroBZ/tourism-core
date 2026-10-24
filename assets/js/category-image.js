jQuery(function ($) {
    'use strict';
    function clearImage(field) {
        field.find('input').val('');
        field.find('.tourism-category-image-preview').empty();
        field.find('.tourism-category-image-remove').prop('hidden', true);
    }
    $(document).on('click', '.tourism-category-image-select', function () {
        const field = $(this).closest('.tourism-category-image');
        const frame = wp.media({ title: $(this).text(), library: { type: 'image' }, multiple: false });
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            const url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            field.find('input').val(attachment.id);
            field.find('.tourism-category-image-preview').empty().append($('<img>', { src: url, alt: '', style: 'max-width:150px;height:auto;' }));
            field.find('.tourism-category-image-remove').prop('hidden', false);
        });
        frame.open();
    });
    $(document).on('click', '.tourism-category-image-remove', function () {
        clearImage($(this).closest('.tourism-category-image'));
    });
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (typeof settings.data !== 'string' || !/(^|&)action=add-tag(&|$)/.test(settings.data)) return;
        const response = xhr.responseXML;
        if (response && response.getElementsByTagName('term_id').length && !response.getElementsByTagName('wp_error').length) {
            $('#addtag .tourism-category-image').each(function () { clearImage($(this)); });
        }
    });
});
