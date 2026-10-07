<?php
/**
 * Template for What to Bring metabox (repeater: title + content).
 *
 * @var array<int, array{title: string, content: string}> $tourism_core_what_to_bring_items
 */

if (!defined('ABSPATH')) {
    exit;
}

// Asegurar que siempre haya al menos un item
if (empty($tourism_core_what_to_bring_items) || !is_array($tourism_core_what_to_bring_items)) {
    $tourism_core_what_to_bring_items = array(
        array(
            'title'   => '',
            'content' => '',
        ),
    );
}

// Mark that this metabox is present so the save handler can detect it.
echo '<input type="hidden" name="tourism_what_to_bring_metabox_present" value="1" />';
?>

<div class="tourism-core-what-to-bring-wrapper">
    <div id="tourism-core-what-to-bring-container">
        <?php foreach ($tourism_core_what_to_bring_items as $tourism_core_index => $tourism_core_item) :
            $item_title   = isset($tourism_core_item['title']) ? (string) $tourism_core_item['title'] : '';
            $item_content = isset($tourism_core_item['content']) ? (string) $tourism_core_item['content'] : '';
            $editor_id    = 'tourism_what_to_bring_content_' . $tourism_core_index;
        ?>
            <div class="tourism-core-what-to-bring-item postbox">
                <div class="postbox-header">
                    <span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
                    <span class="tourism-core-what-to-bring-item-title-text">
                        <?php esc_html_e('Section', TOURISM_CORE_TD); ?>
                        #<span class="tourism-core-what-to-bring-item-num"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
                    </span>
                    <button type="button" class="button tourism-core-delete-what-to-bring">
                        <?php esc_html_e('Delete', TOURISM_CORE_TD); ?>
                    </button>
                </div>
                <div class="inside">
                    <p>
                        <label for="tourism_what_to_bring_title_<?php echo esc_attr((string) $tourism_core_index); ?>">
                            <strong><?php esc_html_e('Section Title', TOURISM_CORE_TD); ?></strong>
                        </label>
                        <input
                            type="text"
                            id="tourism_what_to_bring_title_<?php echo esc_attr((string) $tourism_core_index); ?>"
                            class="widefat tourism-core-what-to-bring-title-input"
                            name="tourism_what_to_bring_items[<?php echo esc_attr((string) $tourism_core_index); ?>][title]"
                            value="<?php echo esc_attr($item_title); ?>"
                            placeholder="<?php esc_attr_e('Ej: ¿Qué llevar?', TOURISM_CORE_TD); ?>"
                        />
                    </p>
                    <p>
                        <label for="<?php echo esc_attr($editor_id); ?>">
                            <strong><?php esc_html_e('Content', TOURISM_CORE_TD); ?></strong>
                        </label>
                    </p>
                    <?php
                    wp_editor(
                        $item_content,
                        $editor_id,
                        array(
                            'textarea_name' => 'tourism_what_to_bring_items[' . esc_attr((string) $tourism_core_index) . '][content]',
                            'textarea_rows' => 8,
                            'media_buttons' => true,
                            'teeny'         => false,
                            'quicktags'     => true,
                        )
                    );
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p>
        <button type="button" id="tourism-core-add-what-to-bring" class="button button-secondary">
            <?php esc_html_e('Add Section', TOURISM_CORE_TD); ?>
        </button>
    </p>
</div>

<!-- Template para nuevas secciones (usado por JS) -->
<script type="text/html" id="tourism-core-tmpl-what-to-bring-item">
    <div class="tourism-core-what-to-bring-item postbox">
        <div class="postbox-header">
            <span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
            <span class="tourism-core-what-to-bring-item-title-text">
                <?php echo esc_html(__('Section', TOURISM_CORE_TD)); ?>
                #<span class="tourism-core-what-to-bring-item-num">{{tourism_index_plus_one}}</span>
            </span>
            <button type="button" class="button tourism-core-delete-what-to-bring">
                <?php echo esc_html(__('Delete', TOURISM_CORE_TD)); ?>
            </button>
        </div>
        <div class="inside">
            <p>
                <label>
                    <strong><?php echo esc_html(__('Section Title', TOURISM_CORE_TD)); ?></strong>
                </label>
                <input
                    type="text"
                    class="widefat tourism-core-what-to-bring-title-input"
                    name="tourism_what_to_bring_items[{{tourism_index}}][title]"
                    value=""
                    placeholder="<?php echo esc_attr(__('Ej: ¿Qué llevar?', TOURISM_CORE_TD)); ?>"
                />
            </p>
            <p>
                <label>
                    <strong><?php echo esc_html(__('Content', TOURISM_CORE_TD)); ?></strong>
                </label>
            </p>
            <textarea
                class="widefat tourism-core-what-to-bring-content-textarea"
                name="tourism_what_to_bring_items[{{tourism_index}}][content]"
                rows="8"
            ></textarea>
        </div>
    </div>
</script>
