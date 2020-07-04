<?php

if( !function_exists( 'paystack_log' ) ) {
	function paystack_log( $message, $level = 'debug' ) {
		wcfm_create_log( $message, $level, 'paystack' );
	}
}