<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for admin metabox boilerplate (nonce, save guards, templates, sanitizers).
 */
class Tourism_Core_Metabox_Helper {

	/**
	 * Verify a save nonce from $_POST.
	 *
	 * @param string $field  Nonce field name.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public static function verify_nonce( $field, $action ) {
		if ( ! isset( $_POST[ $field ] ) || ! is_string( $_POST[ $field ] ) ) {
			return false;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Common save guards: autosave + capability.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $cap     Capability (default edit_post).
	 * @return bool
	 */
	public static function can_save( $post_id, $cap = 'edit_post' ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}
		return (bool) current_user_can( $cap, $post_id );
	}

	/**
	 * Emit the hidden "metabox is on screen" input.
	 *
	 * @param string $key Input name.
	 */
	public static function emit_metabox_present( $key ) {
		printf( '<input type="hidden" name="%s" value="1" />', esc_attr( $key ) );
	}

	/**
	 * Whether a metabox was present on the submitted screen.
	 *
	 * @param string $key Input name.
	 * @return bool
	 */
	public static function metabox_present( $key ) {
		return isset( $_POST[ $key ] );
	}

	/**
	 * Include a plugin template with variables in scope.
	 *
	 * @param string $relative_path Path relative to TOURISM_CORE_PATH (e.g. modules/tours/templates/foo.php).
	 * @param array  $vars          Variables to extract into the template scope.
	 */
	public static function include_template( $relative_path, array $vars = array() ) {
		$path = TOURISM_CORE_PATH . ltrim( (string) $relative_path, '/\\' );
		if ( ! is_readable( $path ) ) {
			return;
		}
		extract( $vars, EXTR_SKIP );
		include $path;
	}

	/**
	 * Normalize a "select + editable options list" meta for the editor.
	 *
	 * @param int          $post_id         Post ID.
	 * @param string       $options_meta_key Meta key holding the options array.
	 * @param array<int,string> $defaults   Fallback options when meta is empty.
	 * @param string       $selected        Currently selected value (appended if missing).
	 * @return array<int,string>
	 */
	public static function normalize_select_options( $post_id, $options_meta_key, array $defaults, $selected = '' ) {
		$stored  = get_post_meta( $post_id, $options_meta_key, true );
		$options = array();

		if ( is_array( $stored ) ) {
			foreach ( $stored as $option ) {
				if ( ! is_string( $option ) ) {
					continue;
				}
				$option = trim( $option );
				if ( $option !== '' ) {
					$options[] = $option;
				}
			}
		}

		if ( $options === array() ) {
			$options = $defaults;
		}

		if ( is_string( $selected ) && trim( $selected ) !== '' && ! in_array( trim( $selected ), $options, true ) ) {
			$options[] = trim( $selected );
		}

		return array_values( array_unique( $options ) );
	}

	/**
	 * Sanitize a raw options array from $_POST (after unslash).
	 *
	 * @param mixed $raw Raw value.
	 * @return array<int,string>
	 */
	public static function sanitize_options_array( $raw ) {
		$out = array();
		if ( ! is_array( $raw ) ) {
			return $out;
		}
		foreach ( $raw as $option ) {
			$option = sanitize_text_field( (string) $option );
			if ( $option !== '' ) {
				$out[] = $option;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Sanitize a "title + WYSIWYG content" repeater array coming from $_POST (unslashed).
	 * Shared by: Includes, Excludes, Highlights, Pricing Details, What to Expect.
	 *
	 * @param mixed $items Unslashed $_POST array (or anything else, defensively).
	 * @return array<int, array{title: string, content: string}>
	 */
	public static function sanitize_title_content_items( $items ) {
		$out = array();

		if ( ! is_array( $items ) ) {
			return $out;
		}

		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$title   = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
			$content = isset( $row['content'] ) ? wp_kses_post( $row['content'] ) : '';

			if ( $title === '' && trim( wp_strip_all_tags( $content ) ) === '' ) {
				continue;
			}

			$out[] = array(
				'title'   => $title,
				'content' => $content,
			);
		}

		return $out;
	}

	/**
	 * Sanitize generic repeater rows with a per-field callback map.
	 * Rows where every field is empty (after strip_tags for strings) are dropped.
	 *
	 * @param mixed                $raw        Unslashed rows.
	 * @param array<string,string> $field_map  field_key => sanitize callback name.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_rows( $raw, array $field_map ) {
		$out = array();
		if ( ! is_array( $raw ) || $field_map === array() ) {
			return $out;
		}

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			foreach ( $field_map as $key => $callback ) {
				$value         = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$clean[ $key ] = is_string( $value ) && is_callable( $callback ) ? call_user_func( $callback, $value ) : ( is_callable( $callback ) ? call_user_func( $callback, $value ) : $value );
			}

			$has_value = false;
			foreach ( $clean as $value ) {
				if ( is_string( $value ) ) {
					if ( trim( wp_strip_all_tags( $value ) ) !== '' ) {
						$has_value = true;
						break;
					}
				} elseif ( $value !== '' && $value !== null ) {
					$has_value = true;
					break;
				}
			}

			if ( $has_value ) {
				$out[] = $clean;
			}
		}

		return $out;
	}

	/**
	 * Sanitize gallery rows from $_POST: [id, url, title, caption].
	 * Syncs attachment URL when id > 0.
	 *
	 * @param string $post_key $_POST key holding the rows array.
	 * @return array{rows: array<int, array{id:int,url:string,title:string,caption:string}>, ids: array<int,int>}
	 */
	public static function sanitize_gallery_post_rows( $post_key ) {
		$rows     = array();
		$sync_ids = array();

		if ( ! isset( $_POST[ $post_key ] ) || ! is_array( $_POST[ $post_key ] ) ) {
			return array(
				'rows' => $rows,
				'ids'  => $sync_ids,
			);
		}

		foreach ( wp_unslash( $_POST[ $post_key ] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$gid    = isset( $row['id'] ) ? (int) $row['id'] : 0;
			$gurl   = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
			$gtitle = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
			$gcap   = isset( $row['caption'] ) ? sanitize_textarea_field( $row['caption'] ) : '';

			if ( $gid <= 0 && $gurl === '' ) {
				continue;
			}
			if ( $gid > 0 ) {
				$att_url = wp_get_attachment_url( $gid );
				if ( $att_url ) {
					$gurl = $att_url;
				}
				$sync_ids[] = $gid;
			}

			$rows[] = array(
				'id'      => $gid,
				'url'     => $gurl,
				'title'   => $gtitle,
				'caption' => $gcap,
			);
		}

		return array(
			'rows' => $rows,
			'ids'  => array_values( array_unique( array_filter( $sync_ids ) ) ),
		);
	}
}
