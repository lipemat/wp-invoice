<?php

/**
 * Name: Venmo
 * Class: wpi_venmo
 * Internal Slug: wpi_venmo
 * JS Slug: wpi_venmo
 * Version: 1.0
 * Description: Provides Venmo gateway. No recurring payments support.
 */
class wpi_venmo extends wpi_gateway_base {
	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct();

		$this->options = [
			'name'           => 'Venmo',
			'allow'          => '',
			'default_option' => '',
			'settings'       => [
				'qrcode' => [
					'label' => 'QR Code',
					'value' => '',
				],
			],
		];

		//** Fields for front-end. */
		$this->front_end_fields = [];
	}


	/**
	 * Show settings for RB. Nothing in case of Venmo
	 *
	 * @param WPI_Invoice $invoice
	 */
	public function recurring_settings( $invoice ) : void {
		?>
		<h4><?php _e( 'Venmo Recurring Billing', ud_get_wp_invoice()->domain ); ?></h4>
		<p><?php _e( 'Currently Venmo gateway does not support Recurring Billing', ud_get_wp_invoice()->domain ); ?></p>
		<?php
	}


	/**
	 * Fields renderer
	 *
	 * @param type $invoice
	 */
	public function wpi_payment_fields( $invoice ) {
		?>
		
		<?php
	}


	/**
	 * Merchant CB handler
	 */
	static function server_callback() {
		echo 'OK';
	}


	/**
	 * Payment Processor
	 */
	static function process_payment() {
		echo json_encode(
			[ 'success' => 1 ]
		);
	}
}
