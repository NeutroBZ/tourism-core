jQuery(document).ready(function ($) {

    let devgolang_itemCount = $('#devgolang-faqs-container .devgolang-faq-item').length;

    function devgolang_initEditor(editorId) {

        if (typeof wp === 'undefined' || !wp.editor) {
            return;
        }

        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
            return;
        }

        wp.editor.initialize(editorId, {
            tinymce: {
                wpautop: true,
                plugins: 'lists link paste wordpress',
                toolbar1: 'formatselect,bold,italic,bullist,numlist,link,unlink',
                toolbar2: '',
                height: 250
            },
            quicktags: true,
            mediaButtons: false
        });
    }

    function devgolang_destroyEditor(editorId) {

        if (typeof tinymce !== 'undefined') {

            const editor = tinymce.get(editorId);

            if (editor) {
                editor.remove();
            }
        }

        const quicktagToolbar = document.getElementById(
            'qt_' + editorId + '_toolbar'
        );

        if (quicktagToolbar) {
            quicktagToolbar.remove();
        }
    }

    function devgolang_initComponents() {

        devgolang_initSortableFAQs();

        devgolang_bindEventHandlers();

        $('.devgolang-wysiwyg').each(function () {
            devgolang_initEditor($(this).attr('id'));
        });

        devgolang_renumberItems();
    }

    function devgolang_initSortableFAQs() {

        $("#devgolang-faqs-container").sortable({
            handle: ".devgolang-handle",
            axis: "y",
            opacity: 0.7,
            update: function () {
                devgolang_renumberItems();
            }
        });
    }

    function devgolang_bindEventHandlers() {

        $("#devgolang-add-faq").on(
            "click",
            devgolang_handleAddFAQ
        );

        $(document).on(
            "click",
            ".devgolang-delete-faq",
            devgolang_handleDeleteFAQ
        );

        $('#post').on('submit', function () {

            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }

        });
    }

    function devgolang_handleAddFAQ() {

        const $devgolang_container = $("#devgolang-faqs-container");

        const devgolang_template = $("#devgolang-tmpl-faq-item").html()
            .replace(/{{devgolang_index}}/g, devgolang_itemCount)
            .replace(/{{devgolang_indexPlusOne}}/g, devgolang_itemCount + 1);

        $devgolang_container.append(devgolang_template);

        const editorId =
            'devgolang_answer_' + devgolang_itemCount;

        devgolang_initEditor(editorId);

        devgolang_itemCount++;

        devgolang_renumberItems();
    }

    function devgolang_handleDeleteFAQ() {

        if (!confirm("Are you sure you want to delete this FAQ?")) {
            return;
        }

        const $item = $(this).closest(".devgolang-faq-item");

        const editorId = $item
            .find('.devgolang-wysiwyg')
            .attr('id');

        devgolang_destroyEditor(editorId);

        $item.remove();

        devgolang_renumberItems();
    }

    function devgolang_renumberItems() {

        $("#devgolang-faqs-container .devgolang-faq-item").each(function (devgolang_index) {

            $(this)
                .find(".devgolang-item-number")
                .text(devgolang_index + 1);

            const devgolang_newIndex = devgolang_index;

            $(this).attr(
                'data-devgolang-index',
                devgolang_newIndex
            );

            $(this)
                .find('[name*="[question]"]')
                .attr(
                    'name',
                    `devgolang_faqs[${devgolang_newIndex}][question]`
                );

            $(this)
                .find('[name*="[answer]"]')
                .attr(
                    'name',
                    `devgolang_faqs[${devgolang_newIndex}][answer]`
                );
        });

        devgolang_itemCount =
            $("#devgolang-faqs-container .devgolang-faq-item").length;
    }

    devgolang_initComponents();

    document.addEventListener('click', function (e) {

        const header = e.target.closest('.devgolang-faq-header');

        if (!header) return;

        const item = header.closest('.devgolang-faq-item');

        item.classList.toggle('active');
    });

});