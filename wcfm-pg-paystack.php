<?php
/**
 * Plugin Name: WCFM Marketplace Vendor Payment - Paystack
 * Plugin URI: https://wclovers.com/product/woocommerce-multivendor-membership
 * Description: WCFM Marketplace paystack vendor payment gateway 
 * Author: WC Lovers
 * Version: 1.0.0
 * Author URI: https://wclovers.com
 *
 * Text Domain: wcfm-pg-paystack
 * Domain Path: /lang/
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.0
 *
 */

if(!defined('ABSPATH')) exit; // Exit if accessed directly

if(!defined('WCFM_TOKEN')) return;
if(!defined('WCFM_TEXT_DOMAIN')) return;

if ( ! class_exists( 'WCFMpgps_Dependencies' ) )
	require_once 'helpers/class-wcfm-pg-paystack-dependencies.php';

if( !WCFMpgps_Dependencies::woocommerce_plugin_active_check() )
	return;

if( !WCFMpgps_Dependencies::wcfm_plugin_active_check() )
	return;

if( !WCFMpgps_Dependencies::wcfmmp_plugin_active_check() )
	return;

if( !WCFMpgps_Dependencies::woo_paystack_plugin_active_check() )
	return;

require_once 'helpers/wcfm-pg-paystack-core-functions.php';
require_once 'wcfm-pg-paystack-config.php';

if(!class_exists('WCFM_PG_Paystack')) {
	include_once( 'core/class-wcfm-pg-paystack.php' );
	global $WCFM, $WCFMpgps, $WCFM_Query;
	$WCFMpgps = new WCFM_PG_Paystack( __FILE__ );
	$GLOBALS['WCFMpgps'] = $WCFMpgps;
}