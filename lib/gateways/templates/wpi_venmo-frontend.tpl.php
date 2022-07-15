<?php

global $invoice, $wpi_settings;

?>
<input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?= es( $invoice['invoice_id'] ) ?>" />
<p>&nbsp;</p>
<h3 style="text-align: center;">
	Memo: Invoice #<?= es( $invoice['ID'] ) ?>
</h3>
<p style="text-align: center;">
	<img
		style="width: 30%; height: auto;"
		src="<?= es( $wpi_settings['billing']['wpi_venmo']['settings']['qrcode']['value'] ) ?>" />
</p>
