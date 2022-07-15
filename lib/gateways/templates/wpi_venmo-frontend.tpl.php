<?php

global $invoice, $wpi_settings;

?>
<input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?= es( $invoice['invoice_id'] ) ?>" />
<input
	type="hidden"
	id="payment_amount"
	name="amount"
	value="<?= esc_attr( number_format( (float) $invoice['net'], 2, '.', '' ) ) ?>">
<input
	type="hidden"
	id="my_amount"
	name="amount"
	value="<?= esc_attr( number_format( (float) $invoice['net'], 2, '.', '' ) ) ?>">


<p>&nbsp;</p>
<h3 style="text-align: center;">
	Memo: Invoice #<?= es( $invoice['ID'] ) ?>
</h3>
<p style="text-align: center;">
	<img
		style="width: 30%; height: auto;"
		src="<?= esc_attr( $wpi_settings['billing']['wpi_venmo']['settings']['qrcode']['value'] ) ?>" />
</p>
