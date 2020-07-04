<?php

if (!defined('ABSPATH')) {
    exit;
}

use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class WCFMmp_Gateway_Paystack extends WCFMmp_Abstract_Gateway {

	public $id;
	public $message = array();
	public $gateway_title;
	public $payment_gateway;
	public $withdrawal_id;
	public $vendor_id;
	public $withdraw_amount = 0;
	public $currency;
	public $transaction_mode;
	private $reciver_email;
	public $test_mode = false;
	public $public_key;
	public $secret_key;
	public $paystack;
	
	public function __construct() {
		global $WCFMmp;
		
		$this->id 				= WCFMpgps_GATEWAY;
		$this->gateway_title 	= __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' );
		$this->payment_gateway 	= $this->id;
		$this->currency 		= get_woocommerce_currency();
		$this->test_mode		= isset( $WCFMmp->wcfmmp_withdrawal_options['test_mode'] ) ? true : false;
		$test_public_key 		= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_test_public_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_test_public_key'] : '';
		$test_secret_key 		= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_test_secret_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_test_secret_key'] : '';

		$live_public_key 		= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_public_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_public_key'] : '';
		$live_secret_key 		= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_secret_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_secret_key'] : '';

		$this->public_key		= $this->test_mode ? $test_public_key : $live_public_key;
		$this->secret_key 		= $this->test_mode ? $test_secret_key : $live_secret_key;

		try {
			$this->paystack 	= new Paystack( $this->secret_key );
		} catch( Exception $e ) {
	    	paystack_log( $e->getMessage(), 'error' );
	    }
	}
	
	public function gateway_logo() { 
		global $WCFMmp; 
		return $WCFMmp->plugin_url . 'assets/images/'.$this->id.'.png';
	}

	public function validate_request() {		
		if ( !$this->public_key || !$this->secret_key ) {
			$this->message[] = array( 
				'message' => __( 'Paystack setting is not configured properly please contact site administrator', 'wc-multivendor-marketplace' ) 
			);
			
			return false;
		}

		$vendor_data 	= get_user_meta( $this->vendor_id, 'wcfmmp_profile_settings', true );
		$bank_code		= $vendor_data['payment'][$this->payment_gateway]['bank_code'];
		$account_number	= $vendor_data['payment'][$this->payment_gateway]['account_number'];

		if( !$bank_code || !$account_number ) {
			$this->message[] = array( 
				'message' => __( 'Vendor Paystack setting is not configured properly please configure this in Dashboard Settings->Payment', 'wc-multivendor-marketplace' ) 
			);

			return false;
		}
		
		return parent::validate_request();
	}
	
	public function process_payment( $withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges, $transaction_mode = 'auto' ) {

		$this->withdrawal_id 	= $withdrawal_id;
		$this->withdraw_amount	= intval( $withdraw_amount * 100 ); // convert NGN to kobo i.e. 1 NGN = 100 kobo
		$this->withdraw_charges	= $withdraw_charges;
		$this->transaction_mode	= $transaction_mode;
		$this->vendor_id		= $vendor_id;

		$vendor_data = get_user_meta( $this->vendor_id, 'wcfmmp_profile_settings', true );

		if( $this->validate_request() ) {
			// create transfer recipient
			try {
				$transfer_recipient = $this->paystack->transferrecipient->create( array(
					'type'				=> 'nuban',
					'currency'			=> $this->currency,
					'name'				=> $vendor_data['payment'][$this->payment_gateway]['name'],
					'email'				=> $vendor_data['payment'][$this->payment_gateway]['email'],
					'bank_code'			=> $vendor_data['payment'][$this->payment_gateway]['bank_code'],
					'account_number'	=> $vendor_data['payment'][$this->payment_gateway]['account_number'],
				) );

			} catch(ApiException $e) {
				// transfer recipient error
				paystack_log( $e->getMessage(), 'error' );
				return array(
					'message' 	=> array(
						'message' => $e->getMessage(), 
					)
				);

			}
			
			// check if Recipient created
			if( isset( $transfer_recipient->status ) && $transfer_recipient->status ) {
				if( isset( $transfer_recipient->data ) && isset( $transfer_recipient->data->recipient_code ) && $transfer_recipient->data->recipient_code ) {
					// initiate transfer
					try {
						$transfer = $this->paystack->transfer->initiate( array(
							'source'	=> 'balance',
							'amount'	=> $this->withdraw_amount, // in kobo only
							'currency'	=> $this->currency,
							'recipient'	=> $transfer_recipient->data->recipient_code,
							'reason'	=> __( 'Payout for withdrawal ID #', 'wc-multivendor-marketplace' ) . sprintf( '%06u', $this->withdrawal_id ),
						) );

						// check if transfer sucessful
						if( isset( $transfer->status ) && $transfer->status ) {
							// payout successful
							return array( 
								'status' 	=> 'success',
							);
						}

					} catch(ApiException $e) {
						// transfer error
						paystack_log( $e->getMessage(), 'error' );
						return array(
							'message' 	=> array(
								'message' => $e->getMessage(), 
							)
						);
					}
				}
			}
		}

		// configuration error
		return array(
			'message' 	=> $this->message,
		);
	}
}