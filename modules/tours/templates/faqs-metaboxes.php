<?php
/**
 * Tour FAQs metabox markup (sortable repeater).
 *
 * Expected variables:
 * - array $tourism_core_faqs List of [ 'question' => string, 'answer' => string ].
 *
 * @package Tourism_Core
 */

if (! defined('ABSPATH')) {
	exit;
}

$tourism_core_faqs = isset($tourism_core_faqs) && is_array($tourism_core_faqs) ? $tourism_core_faqs : array();
?>

<input type="hidden" name="tourism_faqs_metabox_present" value="1" />

<div id="tourism-core-faqs-container" class="tourism-core-sortable-container">
	<?php foreach ($tourism_core_faqs as $tourism_core_index => $tourism_core_item) : ?>
		<?php
		$tourism_core_item = is_array($tourism_core_item) ? $tourism_core_item : array();
		$tourism_core_question = isset($tourism_core_item['question']) ? (string) $tourism_core_item['question'] : '';
		$tourism_core_answer = isset($tourism_core_item['answer']) ? (string) $tourism_core_item['answer'] : '';
		?>
		<div class="tourism-core-faq-item tourism-core-sortable-item active" data-tourism-core-index="<?php echo esc_attr((string) $tourism_core_index); ?>">
			<div class="tourism-core-faq-header">
				<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
				<h4>
					<?php esc_html_e('Pregunta', TOURISM_CORE_TD); ?>
					#<span class="tourism-core-item-number"><?php echo esc_html((string) ((int) $tourism_core_index + 1)); ?></span>
				</h4>
				<button type="button" class="tourism-core-delete-faq button"><?php esc_html_e('Eliminar', TOURISM_CORE_TD); ?></button>
			</div>

			<div class="tourism-core-faq-content">
				<p>
					<label for="tourism_core_question_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Pregunta:', TOURISM_CORE_TD); ?></label>
					<input
						type="text"
						id="tourism_core_question_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_faqs[<?php echo esc_attr((string) $tourism_core_index); ?>][question]"
						value="<?php echo esc_attr($tourism_core_question); ?>"
						class="widefat"
					/>
				</p>
				<p>
					<label for="tourism_core_answer_<?php echo esc_attr((string) $tourism_core_index); ?>"><?php esc_html_e('Respuesta:', TOURISM_CORE_TD); ?></label>
					<textarea
						id="tourism_core_answer_<?php echo esc_attr((string) $tourism_core_index); ?>"
						name="tourism_faqs[<?php echo esc_attr((string) $tourism_core_index); ?>][answer]"
						class="tourism-core-wysiwyg widefat"
						rows="8"
					><?php echo esc_textarea($tourism_core_answer); ?></textarea>
				</p>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<p>
	<button type="button" id="tourism-core-add-faq" class="button button-primary">
		<?php esc_html_e('Añadir pregunta', TOURISM_CORE_TD); ?>
	</button>
</p>

<script type="text/template" id="tourism-core-tmpl-faq-item">
	<div class="tourism-core-faq-item tourism-core-sortable-item active" data-tourism-core-index="{{tourism_index}}">
		<div class="tourism-core-faq-header">
			<span class="tourism-core-handle dashicons dashicons-move" aria-hidden="true"></span>
			<h4><?php echo esc_html(__('Pregunta', TOURISM_CORE_TD)); ?> #<span class="tourism-core-item-number">{{tourism_index_plus_one}}</span></h4>
			<button type="button" class="tourism-core-delete-faq button"><?php echo esc_html(__('Eliminar', TOURISM_CORE_TD)); ?></button>
		</div>
		<div class="tourism-core-faq-content">
			<p>
				<label for="tourism_core_question_{{tourism_index}}"><?php echo esc_html(__('Pregunta:', TOURISM_CORE_TD)); ?></label>
				<input type="text" id="tourism_core_question_{{tourism_index}}" name="tourism_faqs[{{tourism_index}}][question]" value="" class="widefat" />
			</p>
			<p>
				<label for="tourism_core_answer_{{tourism_index}}"><?php echo esc_html(__('Respuesta:', TOURISM_CORE_TD)); ?></label>
				<textarea id="tourism_core_answer_{{tourism_index}}" name="tourism_faqs[{{tourism_index}}][answer]" class="tourism-core-wysiwyg widefat" rows="8"></textarea>
			</p>
		</div>
	</div>
</script>
