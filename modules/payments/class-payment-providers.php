<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Show configured external checkouts, or WhatsApp when none are available. */
class Tourism_Core_Payment_Providers {
	const OPTION = 'tourism_core_payments';

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'menu' );
		$loader->add_action( 'admin_init', $this, 'register_settings' );
		$loader->add_action( 'init', $this, 'shortcode' );
	}

	public static function all() {
		return apply_filters( 'tourism_core_payment_providers', array(
			'wetravel' => array( 'label' => 'WeTravel', 'render' => array( __CLASS__, 'render_wetravel' ) ),
			'paypal'   => array( 'label' => 'PayPal', 'render' => array( __CLASS__, 'render_paypal' ) ),
		) );
	}

	public static function settings() {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), array(
			'wetravel_uid' => '',
			'paypal_email' => '',
			'paypal_me_url' => '',
			'paypal_currency' => 'USD',
		) );
	}

	public function menu() {
		add_submenu_page( 'tourism-core', __( 'Payments', TOURISM_CORE_TD ), __( 'Payments', TOURISM_CORE_TD ), 'manage_options', 'tourism-core-payments', array( $this, 'page' ) );
	}

	public function register_settings() {
		register_setting( 'tourism_core_payments', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) ) );
	}

	public static function valid_email( $value ) {
		return is_string( $value ) && is_email( trim( $value ) ) ? sanitize_email( trim( $value ) ) : '';
	}

	public static function valid_paypal_link( $value ) {
		if ( ! is_string( $value ) ) { return ''; }
		$value = trim( $value );
		return preg_match( '~^https://(?:(?:www\.)?paypal\.com/paypalme/|paypal\.me/)[A-Za-z0-9_-]+/?$~D', $value ) ? $value : '';
	}

	public function sanitize_settings( $raw ) {
		$previous = self::settings();
		if ( ! is_array( $raw ) ) {
			return $previous;
		}
		$uid = array_key_exists( 'wetravel_uid', $raw ) ? $raw['wetravel_uid'] : $previous['wetravel_uid'];
		if ( ! is_string( $uid ) || ( '' !== trim( $uid ) && ! preg_match( '/^[1-9][0-9]*$/D', trim( $uid ) ) ) ) {
			add_settings_error( self::OPTION, 'invalid_uid', __( 'Enter a valid numeric WeTravel account ID.', TOURISM_CORE_TD ) );
			$uid = $previous['wetravel_uid'];
		}
		$email = array_key_exists( 'paypal_email', $raw ) ? self::valid_email( $raw['paypal_email'] ) : $previous['paypal_email'];
		if ( array_key_exists( 'paypal_email', $raw ) && '' === $email && ( ! is_string( $raw['paypal_email'] ) || '' !== trim( $raw['paypal_email'] ) ) ) {
			add_settings_error( self::OPTION, 'invalid_email', __( 'Enter a valid PayPal email address.', TOURISM_CORE_TD ) );
			$email = $previous['paypal_email'];
		}
		$currency = $raw['paypal_currency'] ?? $previous['paypal_currency'];
		$paypal_link = array_key_exists( 'paypal_me_url', $raw ) ? self::valid_paypal_link( $raw['paypal_me_url'] ) : $previous['paypal_me_url'];
		if ( array_key_exists( 'paypal_me_url', $raw ) && '' === $paypal_link && ( ! is_string( $raw['paypal_me_url'] ) || '' !== trim( $raw['paypal_me_url'] ) ) ) {
			add_settings_error( self::OPTION, 'invalid_paypal_link', __( 'Enter a base PayPal.me profile URL without an amount.', TOURISM_CORE_TD ) );
			$paypal_link = $previous['paypal_me_url'];
		}
		return array(
			'wetravel_uid' => trim( $uid ),
			'paypal_email' => $email,
			'paypal_me_url' => $paypal_link,
			'paypal_currency' => in_array( $currency, array( 'USD', 'EUR' ), true ) ? $currency : $previous['paypal_currency'],
		);
	}

	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Payments', TOURISM_CORE_TD ); ?></h1>
			<p><?php esc_html_e( 'Complete the methods you want to offer. Leave a method blank to hide it. When both methods are available, both buttons appear; otherwise, WhatsApp is used when no payment method is available.', TOURISM_CORE_TD ); ?></p>
			<?php settings_errors( self::OPTION ); ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'tourism_core_payments' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="tc-wetravel-uid"><?php esc_html_e( 'WeTravel account ID (data-uid)', TOURISM_CORE_TD ); ?></label></th><td>
						<input id="tc-wetravel-uid" name="tourism_core_payments[wetravel_uid]" type="text" inputmode="numeric" pattern="[1-9][0-9]*" value="<?php echo esc_attr( $settings['wetravel_uid'] ); ?>" />
						<p class="description"><?php esc_html_e( 'The company account ID is shared. Enter each tour’s data-uuid in that tour’s WeTravel box. Leave this account field blank to hide WeTravel on all tours.', TOURISM_CORE_TD ); ?></p>
					</td></tr>
					<tr><th><label for="tc-paypal-email"><?php esc_html_e( 'PayPal email', TOURISM_CORE_TD ); ?></label></th><td>
						<input id="tc-paypal-email" name="tourism_core_payments[paypal_email]" type="email" class="regular-text" value="<?php echo esc_attr( $settings['paypal_email'] ); ?>" />
						<p class="description"><?php esc_html_e( 'One receiving email for all tours. Leave blank to use PayPal.me, if provided. Leave both fields blank to hide PayPal.', TOURISM_CORE_TD ); ?></p>
					</td></tr>
					<tr><th><label for="tc-paypal-currency"><?php esc_html_e( 'PayPal currency', TOURISM_CORE_TD ); ?></label></th><td>
						<select id="tc-paypal-currency" name="tourism_core_payments[paypal_currency]">
							<?php foreach ( array( 'USD', 'EUR' ) as $currency ) : ?>
								<option value="<?php echo esc_attr( $currency ); ?>" <?php selected( $settings['paypal_currency'], $currency ); ?>><?php echo esc_html( $currency ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Email checkout uses the current tour price in this currency; no currency conversion is performed. With PayPal.me, the traveler enters the agreed amount.', TOURISM_CORE_TD ); ?></p>
					</td></tr>
					<tr><th><label for="tc-paypal-me">PayPal.me</label></th><td>
						<input id="tc-paypal-me" name="tourism_core_payments[paypal_me_url]" type="url" class="regular-text" value="<?php echo esc_attr( $settings['paypal_me_url'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional existing PayPal.me link when no receiving email is configured. The traveler enters the agreed amount on PayPal; no amount is charged automatically.', TOURISM_CORE_TD ); ?></p>
					</td></tr>
				</table>
				<p><?php esc_html_e( 'Set the WhatsApp number, including country code, in Tourism Core → Configuration → Agency details. It is used when no payment method is available.', TOURISM_CORE_TD ); ?></p>
				<?php if ( '' === self::whatsapp_number() ) : ?>
					<p class="notice notice-warning inline"><?php esc_html_e( 'Add the agency WhatsApp number to enable the fallback button.', TOURISM_CORE_TD ); ?></p>
				<?php endif; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function paypal_url( $post_id ) {
		$settings = self::settings();
		$email = self::valid_email( $settings['paypal_email'] );
		if ( '' === $email ) { return self::valid_paypal_link( $settings['paypal_me_url'] ); }
		$price = get_post_meta( $post_id, 'tourism_price_from', true );
		$currency = tourism_core_get_tour_currency( $post_id );
		// Old per-tour emails and links must not override the general settings.
		if ( '' === $email || ! is_numeric( $price ) || ! is_finite( (float) $price ) || round( (float) $price, 2 ) <= 0 || ! in_array( $currency, array( 'USD', 'EUR' ), true ) ) {
			return '';
		}
		return 'https://www.paypal.com/cgi-bin/webscr?' . http_build_query( array(
			'cmd' => '_xclick',
			'business' => $email,
			'item_name' => get_the_title( $post_id ),
			'item_number' => (string) $post_id,
			'amount' => number_format( (float) $price, 2, '.', '' ),
			'currency_code' => $currency,
			'no_shipping' => '1',
		), '', '&', PHP_QUERY_RFC3986 );
	}

	public static function whatsapp_number() {
		$number = preg_replace( '/\D/', '', tourism_core_get_agency( 'whatsapp' ) );
		return preg_match( '/^[1-9][0-9]{6,14}$/D', $number ) ? $number : '';
	}

	public function shortcode() {
		add_shortcode( 'tour_payment_button', array( __CLASS__, 'render_button' ) );
	}

	public static function render_button( $args = array() ) {
		if ( ! tourism_core_is_module_enabled( 'tours' ) ) {
			return '';
		}
		$args = shortcode_atts( array( 'post_id' => get_the_ID(), 'label' => '', 'class' => 'tourism-core-payment-button' ), (array) $args );
		$id = absint( $args['post_id'] );
		if ( 'tour' !== get_post_type( $id ) ) {
			return '';
		}
		$args['post_id'] = $id;
		$args['echo'] = false;
		$buttons = array();
		foreach ( self::all() as $key => $provider ) {
			if ( ! is_callable( $provider['render'] ) ) {
				continue;
			}
			$provider_args = $args;
			$provider_args['label'] = '' !== $args['label'] ? $args['label'] : sprintf( __( 'Book with %s', TOURISM_CORE_TD ), $provider['label'] );
			$html = (string) call_user_func( $provider['render'], $provider_args );
			if ( '' !== $html ) {
				$buttons[] = $html;
			}
		}
		if ( empty( $buttons ) ) {
			return self::render_whatsapp( $args );
		}
		return '<div class="tourism-core-payment-actions">' . implode( '', $buttons ) . '</div>';
	}

	public static function render_wetravel( $args ) {
		return tourism_core_render_wetravel_checkout_button( $args );
	}

	public static function render_paypal( $args ) {
		$url = self::paypal_url( $args['post_id'] );
		if ( $url && preg_match( '~paypalme/|paypal\.me/~', $url ) ) {
			return sprintf( '<a class="%s" href="%s" target="_blank" rel="noopener noreferrer">%s</a><p class="tourism-paypal-note">%s</p>', esc_attr( $args['class'] ), esc_url( $url ), esc_html__( 'Pay with PayPal', TOURISM_CORE_TD ), esc_html__( 'Confirm the agreed amount before paying on PayPal.', TOURISM_CORE_TD ) );
		}
		return '' === $url ? '' : sprintf( '<a class="%s" href="%s">%s</a>', esc_attr( $args['class'] ), esc_url( $url ), esc_html( $args['label'] ) );
	}

	public static function render_whatsapp( $args ) {
		$number = self::whatsapp_number();
		if ( '' === $number ) {
			return '';
		}
		$message = sprintf( __( 'Hello, I would like information about: %s', TOURISM_CORE_TD ), get_the_title( $args['post_id'] ) );
		$url = 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
		return sprintf( '<a class="%s" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_attr( $args['class'] ), esc_url( $url ), esc_html__( 'Consultar por WhatsApp', TOURISM_CORE_TD ) );
	}
}
