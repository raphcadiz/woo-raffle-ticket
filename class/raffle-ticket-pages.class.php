<?php
class Raffle_Ticket_Pages {

	public function __construct() {
		add_action('admin_menu', array( $this, 'admin_menus') );
	}

	public function admin_menus() {
		add_menu_page('Raffle Settings', 'Raffle Settings', 'manage_options', 'raffle-settings', array( $this,'raffle_settings_page' ));
		add_submenu_page ( 'raffle-settings' , 'Raffle Draw' , 'Raffle Draw' , 'manage_options' , 'raffle-draw' , array( $this , 'raffle_draw' ));
	}

	public function raffle_settings_page() {
		include_once(RT_PATH_INCLUDES.'/raffle-ticket-settings.php');
	}

	public function raffle_draw() {
		include_once(RT_PATH_INCLUDES.'/raffle-draw-page.php');
	}
}
new Raffle_Ticket_Pages;