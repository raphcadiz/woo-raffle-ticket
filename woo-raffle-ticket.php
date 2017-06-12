<?php
/**
 * Plugin Name: Raffle Ticket - WooCommerce
 * Plugin URI:  https://github.com/raphcadiz/
 * Description: Generate Raffle tickets and Lottery
 * Version:     1.0
 * Author:      raphcadiz
 * Author URI:  https://github.com/raphcadiz/
 * Text Domain: raffle-ticket
 */

define( 'RT_PATH', dirname( __FILE__ ) );
define( 'RT_PATH_INCLUDES', dirname( __FILE__ ) . '/includes' );
define( 'RT_PATH_CLASS', dirname( __FILE__ ) . '/class' );
define( 'RT_FOLDER', basename( RT_PATH ) );
define( 'RT_URL', plugins_url() . '/' . RT_FOLDER );
define( 'RT_URL_INCLUDES', RT_URL . '/includes' );

if( !class_exists('Raffle_Ticket') ):

	register_activation_hook( __FILE__, 'woo_raffle_activation' );
	function woo_raffle_activation(){
		if ( ! class_exists('WooCommerce') ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) );
	        wp_die('Sorry, but this plugin requires the WooCommerce to be installed and active.');
	    }

		$raffle_ticket_db = new Raffle_Ticket_Db;
		$raffle_ticket_db->install();

		if ( !wp_next_scheduled( 'run_cron_raffle_draw' ) ) {
			wp_schedule_event( time() , '5min', 'run_cron_raffle_draw');
	    }
	}
	
	register_deactivation_hook( __FILE__, 'woo_raffle_deactivation' );
	function woo_raffle_deactivation(){
		wp_clear_scheduled_hook('run_cron_raffle_draw');
	}

	add_action( 'admin_init', 'woo_raffle_plugin_activate' );
	function woo_raffle_plugin_activate(){
	    if ( ! class_exists('WooCommerce') ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) );
	    }
	}

	/**
	 * Include classes
	 */
	include_once(RT_PATH_CLASS.'/raffle-ticket-main.class.php');
	include_once(RT_PATH_CLASS.'/raffle-ticket-db.class.php');
	include_once(RT_PATH_CLASS.'/raffle-ticket-pages.class.php');
	include_once(RT_PATH_CLASS.'/raffle-ticket-processing.class.php');

	add_action( 'plugins_loaded', array( 'Raffle_Ticket', 'get_instance' ) );

endif;
