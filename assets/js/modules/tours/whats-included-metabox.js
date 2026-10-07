/**
 * Tour What's Included / Not Included metabox (sortable repeater + dynamic WYSIWYG).
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

    function saveEditors() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    }

    function initEditor(editorId) {
        if (!editorId || typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
            return;
        }

        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
            return;
        }

        wp.editor.initialize(editorId, editorSettings);
    }

    function destroyEditor(editorId) {
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

    function destroyAllEditors($container) {
        $container.find('.tourism-wysiwyg').each(function () {
            destroyEditor($(this).attr('id'));
        });
    }

    function initAllEditors($container) {
        $container.find('.tourism-wysiwyg').each(function () {
            initEditor($(this).attr('id'));
        });
    }

    function renumber($container, prefix) {
        if (!$container.length) {
            return;
        }

        saveEditors();
        destroyAllEditors($container);

        $container.children().each(function (i) {
            var $item = $(this);
            $item.find('strong').text('Item #' + (i + 1));
            $item.find('.tourism-core-label-item-num').text(String(i + 1));
            $item.find('input').attr('name', 'tourism_' + prefix + '[' + i + '][title]');
            $item.find('textarea').attr('name', 'tourism_' + prefix + '[' + i + '][content]');
            $item.find('textarea').attr('id', 'tourism_' + prefix + '_content_' + i);
            $item.find('input[type="text"]').attr('id', 'tourism_' + prefix + '_title_' + i);
        });

        initAllEditors($container);
    }

    $(function () {
        // INCLUDES
        var $incContainer = $('#tourism-includes-container');
        if ($incContainer.length) {
            initAllEditors($incContainer);

            $('#tourism-add-include').on('click', function (e) {
                e.preventDefault();

                var tmpl = $('#tmpl-include').html();
                if (!tmpl) return;

                var idx = $incContainer.children().length;
                var html = tmpl.replace(/\{\{i\}\}/g, idx).replace(/\{\{num\}\}/g, idx + 1);
                $incContainer.append(html);
                initEditor('tourism_includes_content_' + idx);
            });

            $(document).on('click', '.tourism-delete-include', function (e) {
                e.preventDefault();
                var $item = $(this).closest('.tourism-include-item');
                var editorId = $item.find('textarea').attr('id');
                destroyEditor(editorId);
                $item.remove();
                renumber($incContainer, 'includes');
            });
        }

        // EXCLUDES
        var $excContainer = $('#tourism-excludes-container');
        if ($excContainer.length) {
            initAllEditors($excContainer);

            $('#tourism-add-exclude').on('click', function (e) {
                e.preventDefault();

                var tmpl = $('#tmpl-exclude').html();
                if (!tmpl) return;

                var idx = $excContainer.children().length;
                var html = tmpl.replace(/\{\{i\}\}/g, idx).replace(/\{\{num\}\}/g, idx + 1);
                $excContainer.append(html);
                initEditor('tourism_excludes_content_' + idx);
            });

            $(document).on('click', '.tourism-delete-exclude', function (e) {
                e.preventDefault();
                var $item = $(this).closest('.tourism-exclude-item');
                var editorId = $item.find('textarea').attr('id');
                destroyEditor(editorId);
                $item.remove();
                renumber($excContainer, 'excludes');
            });
        }

        $('#post').on('submit', function () {
            saveEditors();
        });
    });
})(jQuery);