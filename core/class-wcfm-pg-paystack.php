<?php

/**
 * WCFM PG Paystack plugin core
 *
 * Plugin intiate
 *
 * @author 		WC Lovers
 * @package 	wcfm-pg-paystack
 * @version   1.0.0
 */

class WCFM_PG_Paystack {
	
	public $plugin_base_name;
	public $plugin_url;
	public $plugin_path;
	public $version;
	public $token;
	public $text_domain;
	
	public function __construct($file) {

		$this->file = $file;
		$this->plugin_base_name = plugin_basename( $file );
		$this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
		$this->plugin_path = trailingslashit(dirname($file));
		$this->token = WCFMpgps_TOKEN;
		$this->text_domain = WCFMpgps_TEXT_DOMAIN;
		$this->version = WCFMpgps_VERSION;
		
		add_action( 'wcfm_init', array( &$this, 'init' ), 10 );
	}
	
	function init() {
		global $WCFM, $WCFMre;
		
		// Init Text Domain
		$this->load_plugin_textdomain();

		// Load Paystack php Library
		require_once $this->plugin_path . 'includes/vendor/yabacon/paystack-php/src/autoload.php';
		
		add_filter( 'wcfm_marketplace_withdrwal_payment_methods', array( &$this, 'wcfmmp_custom_pg' ) );
		
		add_filter( 'wcfm_marketplace_settings_fields_withdrawal_payment_keys', array( &$this, 'wcfmmp_custom_pg_api_keys' ), 50, 2 );
		
		add_filter( 'wcfm_marketplace_settings_fields_withdrawal_payment_test_keys', array( &$this, 'wcfmmp_custom_pg_api_test_keys' ), 50, 2 );
		
		add_filter( 'wcfm_marketplace_settings_fields_withdrawal_charges', array( &$this, 'wcfmmp_custom_pg_withdrawal_charges' ), 50, 3 );
		
		add_filter( 'wcfm_marketplace_settings_fields_billing', array( &$this, 'wcfmmp_custom_pg_vendor_setting' ), 50, 2 );
		
		// Load Gateway Class
		require_once $this->plugin_path . 'gateway/class-wcfmmp-gateway-paystack.php';
		
	}
	
	function wcfmmp_custom_pg( $payment_methods ) {
		$payment_methods[WCFMpgps_GATEWAY] = __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' );
		return $payment_methods;
	}
	
	function wcfmmp_custom_pg_api_keys( $payment_keys, $wcfm_withdrawal_options ) {
		$gateway_slug  = WCFMpgps_GATEWAY;
		$gateway_label = __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' ) . ' ';
		
		$withdrawal_live_secret_key = isset( $wcfm_withdrawal_options[$gateway_slug.'_secret_key'] ) ? $wcfm_withdrawal_options[$gateway_slug.'_secret_key'] : '';
		$withdrawal_live_public_key = isset( $wcfm_withdrawal_options[$gateway_slug.'_public_key'] ) ? $wcfm_withdrawal_options[$gateway_slug.'_public_key'] : '';

		$payment_live_keys = array(
			"withdrawal_".$gateway_slug."_secret_key" => array(
				'label' => __($gateway_label.'Secret Key', 'wc-multivendor-marketplace'), 
				'name' => 'wcfm_withdrawal_options['.$gateway_slug.'_secret_key]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_'.$gateway_slug, 
				'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_'.$gateway_slug, 
				'value' => $withdrawal_live_secret_key
			),
			"withdrawal_".$gateway_slug."_public_key" => array(
				'label' => __($gateway_label.'Public Key', 'wc-multivendor-marketplace'), 
				'name' => 'wcfm_withdrawal_options['.$gateway_slug.'_public_key]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_'.$gateway_slug, 
				'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_'.$gateway_slug, 
				'value' => $withdrawal_live_public_key
			)
		);
		
		$payment_keys = array_merge( $payment_keys, $payment_live_keys );
		
		return $payment_keys;
	}
	
	function wcfmmp_custom_pg_api_test_keys( $payment_keys, $wcfm_withdrawal_options ) {
		$gateway_slug  = WCFMpgps_GATEWAY;
		$gateway_label = __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' ) . ' ';
		
		$withdrawal_test_secret_key = isset( $wcfm_withdrawal_options[$gateway_slug.'_test_secret_key'] ) ? $wcfm_withdrawal_options[$gateway_slug.'_test_secret_key'] : '';
		$withdrawal_test_public_key = isset( $wcfm_withdrawal_options[$gateway_slug.'_test_public_key'] ) ? $wcfm_withdrawal_options[$gateway_slug.'_test_public_key'] : '';

		$payment_test_keys = array(
			"withdrawal_".$gateway_slug."_test_secret_key" => array(
				'label' => __($gateway_label.'Test Secret Key', 'wc-multivendor-marketplace'), 
				'name' => 'wcfm_withdrawal_options['.$gateway_slug.'_test_secret_key]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele withdrawal_mode withdrawal_mode_test withdrawal_mode_'.$gateway_slug, 
				'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_test withdrawal_mode_'.$gateway_slug, 
				'value' => $withdrawal_test_secret_key
			),
			"withdrawal_".$gateway_slug."_test_public_key" => array(
				'label' => __($gateway_label.'Test Public Key', 'wc-multivendor-marketplace'), 
				'name' => 'wcfm_withdrawal_options['.$gateway_slug.'_test_public_key]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele withdrawal_mode withdrawal_mode_test withdrawal_mode_'.$gateway_slug, 
				'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_test withdrawal_mode_'.$gateway_slug, 
				'value' => $withdrawal_test_public_key
			)
		);
		
		$payment_keys = array_merge( $payment_keys, $payment_test_keys );
		
		return $payment_keys;
	}
	
	function wcfmmp_custom_pg_withdrawal_charges( $withdrawal_charges, $wcfm_withdrawal_options, $withdrawal_charge ) {
		$gateway_slug  = WCFMpgps_GATEWAY;
		$gateway_label = __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' ) . ' ';
		
		$withdrawal_charge_paystack = isset( $withdrawal_charge[$gateway_slug] ) ? $withdrawal_charge[$gateway_slug] : array();
		$payment_withdrawal_charges = array(  "withdrawal_charge_".$gateway_slug => array( 'label' => $gateway_label . __('Charge', 'wcfm-pg-paystack'), 'type' => 'multiinput', 'name' => 'wcfm_withdrawal_options[withdrawal_charge]['.$gateway_slug.']', 'class' => 'withdraw_charge_block withdraw_charge_'.$gateway_slug, 'label_class' => 'wcfm_title wcfm_ele wcfm_fill_ele withdraw_charge_block withdraw_charge_'.$gateway_slug, 'value' => $withdrawal_charge_paystack, 'custom_attributes' => array( 'limit' => 1 ), 'options' => array(
			"percent" => array('label' => __('Percent Charge(%)', 'wcfm-pg-paystack'), 'type' => 'number', 'class' => 'wcfm-text wcfm_ele withdraw_charge_field withdraw_charge_percent withdraw_charge_percent_fixed', 'label_class' => 'wcfm_title wcfm_ele withdraw_charge_field withdraw_charge_percent withdraw_charge_percent_fixed', 'attributes' => array( 'min' => '0.1', 'step' => '0.1') ),
			"fixed" => array('label' => __('Fixed Charge', 'wcfm-pg-paystack'), 'type' => 'number', 'class' => 'wcfm-text wcfm_ele withdraw_charge_field withdraw_charge_fixed withdraw_charge_percent_fixed', 'label_class' => 'wcfm_title wcfm_ele withdraw_charge_field withdraw_charge_fixed withdraw_charge_percent_fixed', 'attributes' => array( 'min' => '0.1', 'step' => '0.1') ),
			"tax" => array('label' => __('Charge Tax', 'wcfm-pg-paystack'), 'type' => 'number', 'class' => 'wcfm-text wcfm_ele', 'label_class' => 'wcfm_title wcfm_ele', 'attributes' => array( 'min' => '0.1', 'step' => '0.1'), 'hints' => __( 'Tax for withdrawal charge, calculate in percent.', 'wcfm-pg-paystack' ) ),
		) ) );
		$withdrawal_charges = array_merge( $withdrawal_charges, $payment_withdrawal_charges );
		return $withdrawal_charges;
	}
	
	function wcfmmp_custom_pg_vendor_setting( $vendor_billing_fileds, $vendor_id ) {
		global $WCFMmp;

		$gateway_slug  = WCFMpgps_GATEWAY;
		$gateway_label = __( WCFMpgps_GATEWAY_LABEL, 'wcfm-pg-paystack' ) . ' ';
		
		$is_testmode		= isset( $WCFMmp->wcfmmp_withdrawal_options['test_mode'] ) ? true : false;
		$test_secret_key 	= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_test_secret_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_test_secret_key'] : '';
		$live_secret_key 	= isset( $WCFMmp->wcfmmp_withdrawal_options['paystack_secret_key'] ) ? $WCFMmp->wcfmmp_withdrawal_options['paystack_secret_key'] : '';

		$secret_key 		= $is_testmode ? $test_secret_key : $live_secret_key;

		try {
			$paystack = new Yabacon\Paystack( $secret_key );
		} catch( Exception $e ) {
	    	paystack_log( $e->getMessage(), 'error' );
	    	return $vendor_billing_fileds;
	    }

	    try {
			$options = get_option( 'paystack_bank_list' );
			if( !$options ) {
				$bank_list = $paystack->bank->getList();
				$options = is_array( $bank_list->data ) ? wp_list_pluck( $bank_list->data, 'name', 'code' ) : array();
				$options = array_merge( array( '' => __('select', 'wc-frontend-manager') ), $options );
				update_option( 'paystack_bank_list', $options );
			}
	    } catch(\Yabacon\Paystack\Exception\ApiException $e) {
			paystack_log( $e->getMessage(), 'error' );
	    }
		
		$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		
		if( !$vendor_data ) $vendor_data = array();
		
		$settings = array();
		$settings['name'] = isset( $vendor_data['payment'][$gateway_slug]['name'] ) ? esc_attr( $vendor_data['payment'][$gateway_slug]['name'] ) : '' ;
		$settings['email'] = isset( $vendor_data['payment'][$gateway_slug]['email'] ) ? esc_attr( $vendor_data['payment'][$gateway_slug]['email'] ) : '' ;
		$settings['account_number'] = isset( $vendor_data['payment'][$gateway_slug]['account_number'] ) ? esc_attr( $vendor_data['payment'][$gateway_slug]['account_number'] ) : '' ;
		$settings['bank_code'] = isset( $vendor_data['payment'][$gateway_slug]['bank_code'] ) ? esc_attr( $vendor_data['payment'][$gateway_slug]['bank_code'] ) : '' ;
		
		$new_billing_fileds = array(
			$gateway_slug.'_name' => array(
				'label' => __('Name', 'wc-frontend-manager'), 
				'name' => 'payment['.$gateway_slug.'][name]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'value' => $settings['name']
			),
			$gateway_slug.'_email' => array(
				'label' => __($gateway_label.'Email', 'wc-frontend-manager'), 
				'name' => 'payment['.$gateway_slug.'][email]', 
				'type' => 'text', 
				'class' => 'wcfm-text wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'value' => $settings['email']
			),
			$gateway_slug.'_account_number' => array(
				'label' => __('Account Number', 'wc-frontend-manager'), 
				'name' => 'payment['.$gateway_slug.'][account_number]', 
				'type' => 'text',
				'class' => 'wcfm-text wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'value' => $settings['account_number'],
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
			$gateway_slug.'_bank_code' => array(
				'label' => __('Bank', 'wc-frontend-manager'), 
				'name' => 'payment['.$gateway_slug.'][bank_code]', 
				'type' => 'select',
				'class' => 'wcfm-select wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_'.$gateway_slug, 
				'value' => $settings['bank_code'],
				'options' => $options,
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
		);
		
		$vendor_billing_fileds = array_merge( $vendor_billing_fileds, $new_billing_fileds );
		
		return $vendor_billing_fileds;
	}

	
	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'wcfm-pg-paystack' );
		
		//load_plugin_textdomain( 'wcfm-tuneer-orders' );
		//load_textdomain( 'wcfm-pg-paystack', WP_LANG_DIR . "/wcfm-pg-paystack/wcfm-pg-paystack-$locale.mo");
		load_textdomain( 'wcfm-pg-paystack', $this->plugin_path . "lang/wcfm-pg-paystack-$locale.mo");
		load_textdomain( 'wcfm-pg-paystack', ABSPATH . "wp-content/languages/plugins/wcfm-pg-paystack-$locale.mo");
	}
	
	public function load_class($class_name = '') {
		if ('' != $class_name && '' != $this->token) {
			require_once ('class-' . esc_attr($this->token) . '-' . esc_attr($class_name) . '.php');
		} // End If Statement
	}
}