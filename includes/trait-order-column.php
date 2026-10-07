<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';

use Tourism_Core_Metabox_Helper as Metabox_Helper;

/**
 * Shared admin list-table "Order" column + quick-edit support for CPTs.
 */
trait Tourism_Core_Order_Column {

	/**
	 * Config for the order column.
	 *
	 * @return array{post_type:string,meta_key:string,quickedit_field:string,quickedit_nonce:string,quickedit_action:string}
	 */
	abstract protected function order_column_config();

	/**
	 * Register all order-column hooks on the loader.
	 *
	 * @param Tourism_Core_Loader $loader Loader.
	 */
	public function register_order_column( Tourism_Core_Loader $loader ) {
		$config    = $this->order_column_config();
		$post_type = $config['post_type'];

		$loader->add_filter( 'manage_' . $post_type . '_posts_columns', $this, 'add_order_column' );
		$loader->add_action( 'manage_' . $post_type . '_posts_custom_column', $this, 'render_order_column', 10, 2 );
		$loader->add_filter( 'manage_edit-' . $post_type . '_sortable_columns', $this, 'make_order_column_sortable' );
		$loader->add_action( 'pre_get_posts', $this, 'apply_admin_order_sorting' );
		$loader->add_action( 'quick_edit_custom_box', $this, 'render_quick_edit_order_field', 10, 2 );
	}

	public function add_order_column( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$config    = $this->order_column_config();
		$meta_key  = $config['meta_key'];
		$label     = __( 'Order', TOURISM_CORE_TD );
		$new       = array();

		foreach ( $columns as $key => $col_label ) {
			$new[ $key ] = $col_label;
			if ( 'title' === $key ) {
				$new[ $meta_key ] = $label;
			}
		}

		if ( ! isset( $new[ $meta_key ] ) ) {
			$new[ $meta_key ] = $label;
		}

		return $new;
	}

	public function render_order_column( $column, $post_id ) {
		$config = $this->order_column_config();
		if ( $column !== $config['meta_key'] ) {
			return;
		}

		$value = (int) get_post_meta( (int) $post_id, $config['meta_key'], true );
		echo esc_html( (string) $value );
	}

	public function make_order_column_sortable( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$config                     = $this->order_column_config();
		$columns[ $config['meta_key'] ] = $config['meta_key'];
		return $columns;
	}

	public function apply_admin_order_sorting( $query ) {
		if ( ! $query instanceof WP_Query ) {
			return;
		}

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$config = $this->order_column_config();

		$screen_post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( $screen_post_type !== $config['post_type'] ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( $orderby !== $config['meta_key'] ) {
			return;
		}

		$query->set( 'meta_key', $config['meta_key'] );
		$query->set( 'orderby', 'meta_value_num' );
	}

	public function render_quick_edit_order_field( $column_name, $post_type ) {
		$config = $this->order_column_config();
		if ( $post_type !== $config['post_type'] || $column_name !== $config['meta_key'] ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<?php wp_nonce_field( $config['quickedit_action'], $config['quickedit_nonce'] ); ?>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Orden', TOURISM_CORE_TD ); ?></span>
					<input type="number" min="0" step="1" name="<?php echo esc_attr( $config['quickedit_field'] ); ?>" value="" />
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Handle quick-edit order save. Returns true when the quick-edit nonce was present
	 * (caller must return without running the full-screen save).
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	protected function maybe_save_quick_edit_order( $post_id ) {
		$config = $this->order_column_config();

		if ( ! Metabox_Helper::verify_nonce( $config['quickedit_nonce'], $config['quickedit_action'] ) ) {
			return false;
		}

		if ( Metabox_Helper::can_save( $post_id ) && isset( $_POST[ $config['quickedit_field'] ] ) ) {
			$order = (int) wp_unslash( $_POST[ $config['quickedit_field'] ] );
			if ( $order < 0 ) {
				$order = 0;
			}
			update_post_meta( $post_id, $config['meta_key'], $order );
		}

		return true;
	}
}
