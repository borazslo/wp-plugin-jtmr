<?php

add_action( 'woocommerce_checkout_process', function() {
	// Nonce verification before doing anything
	check_ajax_referer( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );

	
	wc_add_notice( SURBMA_HC_PLUGIN_DIR, 'error' );
	
	
	if ( !empty( $_POST['billing_country'] ) && 'HU' == $_POST['billing_country'] && !empty( $_POST['billing_tax_number'] )) {
		
		$vat = sanitize_text_field( $_POST['billing_tax_number'] );
		
		if( !preg_match('/^\d{11}$/',$vat) AND !preg_match('/^\d{8}-\d{1}-\d{2}$/',$vat) AND !preg_match('/^HU\d{8}$/',$vat) ) {
			$noticeError = __( '<strong>Billing VAT number</strong> field is invalid: Bad format.', 'surbma-magyar-woocommerce' );
			wc_add_notice( $noticeError, 'error' );
		}
		
		$_POST['billing_tax_number'] = preg_replace('/^(\d{8})-(\d{1})-(\d{2})$/','$1$2$3',$vat);	
	
		
		
	}
} );



add_action( 'wp_enqueue_scripts', function() {


		ob_start();
		?>
jQuery(document).ready(function($){
	// Mask the Billing fields
	function HCmaskcheckoutbillingfields2(){

		var options =  {
			translation : {'H': {pattern: /[0-9]|H/}, 'U': {pattern: /[0-9U]/}},
			onKeyPress: function(cep, e, field, options) {
				var masks = ['HU00000000', '00000000-0-00'];
				if ( typeof cep == 'undefined' || cep.length < 1 ) var mask = masks[0];
				else var mask = (cep[0].match(/\d/)) ? masks[1] : masks[0];	
    				
				$('#billing_tax_number').mask(mask, options);	
			}
		};
		$('#billing_tax_number').mask('HU00000000', options);

	}
	// Unmask the Billing fields
	function HCunmaskcheckoutbillingfields(){
		$('#billing_tax_number').unmask();
		$('#billing_postcode').unmask();
		$('#billing_phone').unmask();
	}
	
	// Mask the Billing fields if Country is HU
	if( $('#billing_country').val() == 'HU' ){
		HCmaskcheckoutbillingfields2();
	}
	// Check if Billing Country has changed
	$('#billing_country').change(function() {
		if( $('#billing_country').val() == 'HU' ){
			HCmaskcheckoutbillingfields2();
		} else {
			HCunmaskcheckoutbillingfields();
		}
	});
	
});
<?php
		$script = ob_get_contents();
		ob_end_clean();

wp_register_script( 'myprefix-dummy-js-footer',  '', array("cps-jquery-fix"), '', true );
wp_enqueue_script( 'myprefix-dummy-js-footer' );
wp_add_inline_script( 'myprefix-dummy-js-footer', $script);
});