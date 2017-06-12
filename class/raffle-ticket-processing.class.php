<?php
class Raffle_Ticket_Processing {

	public function __construct() {
		add_action( 'woocommerce_order_status_processing', array( $this, 'generate_ticket_for_order' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'show_tickets_under_order' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'show_generated_tickets_on_order_summary') );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_generated_tickets_on_order_summary') );
		add_action( 'wp_ajax_run_raffle_draw_for_prouduct' , array( $this , 'run_raffle_draw_for_prouduct' ) );
		add_filter( 'cron_schedules',array( $this, 'cron_schedules' ) );
		add_action( 'run_cron_raffle_draw', array( $this, 'run_cron_raffle_draw' ) );
	}

	/**
	 * Generate tickets base on quantity
	 * for raffle products in the order.
	 */
	public function generate_ticket_for_order( $order_id ) {
		$order = new WC_Order($order_id);
		$raffle_db = new Raffle_Ticket_Db();

		$raffle_tickets_options = get_option('raffle_tickets_options');
		$hours_before_raffle = isset($raffle_tickets_options['hours_before_raffle']) ? $raffle_tickets_options['hours_before_raffle'] : 72;

		$items = $order->get_items();
	    foreach ($items as $item) {
	    	$raffle_item = get_post_meta( $item['product_id'], '_raffle_item', true );
	    	if( $raffle_item ):
		    	//generate tickets base on item's quantity
		        for ( $i = 0; $i < $item['qty']; $i++ ) { 
		        	$data = array(
							'ticket_code' 	=> str_replace(" ", "-", strtoupper($item['name'])).'-'.strtoupper(bin2hex(openssl_random_pseudo_bytes(6))),
							'product_id'	=> $item['product_id'],
							'order_id'		=> $order_id
						);

					$ticket_id = $raffle_db->insert_ticket($data);
		        }

		        $raffle_minimum_amount = get_post_meta( $item['product_id'], '_raffle_minimum_amount', true );
				$raffle_date = get_post_meta( $item['product_id'], '_raffle_date', true );
				$total_tickets = $raffle_db->get_tickets_by_product($item['product_id']);

				if( $raffle_minimum_amount <= count($total_tickets) ) {
					if( empty($raffle_date) ){
						$new_raffle_date = date("Y-m-d H:i:s", strtotime("+$hours_before_raffle hours"));
						update_post_meta( $item['product_id'], '_raffle_date', esc_attr( (string)$new_raffle_date ) );
					}
				}
	        endif;
	    }
		
	}

	public function show_tickets_under_order($order) {
		$raffle_db = new Raffle_Ticket_Db();
		$tickets = $raffle_db->get_tickets_by_order($order->id);

		echo '<p>&nbsp;</p><h2>Ticket Numbers</h2>';
		foreach ($tickets as $row => $ticket) {
			echo ($row + 1).'. <strong>'.$ticket->ticket_code.'</strong><br />';
		}
	}

	public function show_generated_tickets_on_order_summary($order) {
		$raffle_db = new Raffle_Ticket_Db();
		$tickets = $raffle_db->get_tickets_by_order($order->id);

		echo '<h2>Ticket Numbers</h2>';
		foreach ($tickets as $row => $ticket) {
			echo ($row + 1).'. <strong>'.$ticket->ticket_code.'</strong><br />';
		}
		echo '<br />';
	}

	/**
	 * Run raffle for specific product.
	 */
	public function raffle_winner_for_product($product_id) {
		$raffle_db = new Raffle_Ticket_Db();
		$tickets = $raffle_db->get_tickets_by_product($product_id);

		$tickets_array = array();
		foreach ($tickets as $ticket) {
			$tickets_array[] = $ticket->ticket_code;
		}

		shuffle($tickets_array);
		$ticket = array_rand($tickets_array);
		$ticket_winner = $raffle_db->get_ticket_by_raffle_code($tickets_array[$ticket]);

		$order = new WC_Order($ticket_winner->order_id);
		update_post_meta( $ticket_winner->product_id, '_raffle_date', date("Y-m-d H:i:s") );
		update_post_meta( $ticket_winner->product_id, '_winner_raffle_code', esc_attr( $ticket_winner->ticket_code ) );
		update_post_meta( $ticket_winner->product_id, '_stock_status', 'outofstock' );

		update_post_meta( $ticket_winner->product_id, '_winner_order_id', $ticket_winner->order_id);

		$this->email_winner($order->billing_email, $ticket_winner->product_id, $ticket_winner->ticket_code);

		return $ticket_winner->product_id;
	}

	/**
	 * Send email notification after raffle
	 * draw to Winner and Administrator.
	 */
	private function email_winner($to, $product_id, $ticket_code){
		$product = get_post($product_id);
		$item_name = $product->post_title;
		$item_link = get_permalink( $product->ID );
		$winner_order = (int)get_post_meta( $product_id, '_winner_order_id', true );
		$order = new WC_Order($winner_order);

		$raffle_tickets_options = get_option('raffle_tickets_options');
		$email_subject = !empty($raffle_tickets_options['email_subject']) ? $raffle_tickets_options['email_subject'] : 'Raffle Winner';
		$email_template_content = "Congratulations you are the lucky winner for the item <a href='$item_link'>$item_name</a> with your raffle ticket <strong>$ticket_code</strong>.";
		if( !empty($raffle_tickets_options['email_template_content'])  ) {
			$email_template_content = str_replace('{product}', "<a href='$item_link'>$item_name</a>", $raffle_tickets_options['email_template_content']); //raffle product name and link
			$email_template_content = str_replace('{raffle-code}', "$ticket_code", $email_template_content); //raffle ticket code
			$email_template_content = str_replace('{first_name}', "$order->billing_first_name", $email_template_content); //order firstname
			$email_template_content = str_replace('{last_name}', "$order->billing_last_name", $email_template_content); //order lastname
			$email_template_content = str_replace('{city}', "$order->billing_city", $email_template_content); //raffle order city
			$email_template_content = str_replace('{country}', "$order->billing_country", $email_template_content); //raffle order country
		}

		$subject 	 = $email_subject;
		$body 		 = $email_template_content;
		$headers 	 = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $to, $subject, $body, $headers );

		/**
		 * send email to administrator
		 * on the product winner
		 */
		$admin_email = get_option( 'admin_email' );
		$winner = "<a href='".get_edit_post_link($order->id)."'>$order->billing_first_name</a>";

		$subject 	 = 'Raffle Winner';
		$body 		 = "$winner won the item <a href='$item_link'>$item_name</a> with the raffle ticket <strong>$ticket_code</strong>.";
		$headers 	 = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( $admin_email, $subject, $body, $headers );
	}
	
	public function run_raffle_draw_for_prouduct() {
		if( isset( $_POST['data'] ) ):
			$product_id = isset($_POST['data']['product_id']) ? $_POST['data']['product_id'] : '';
			$product = $this->raffle_winner_for_product($product_id);

			echo $product;
		endif;

		die();
	}

	/**
	 * Add 5 minutes on wp schedules
	 * to be use to run cron on raffles.
	 */
	public function cron_schedules($schedules){
	    if(!isset($schedules["5min"])){
	        $schedules["5min"] = array(
	            'interval' => 5*60,
	            'display' => __('Once every 5 minutes'));
	    }

	    return $schedules;
	}

	/**
	 * Run raffle draw to all product
	 * sceduled for raffle.
	 */
	public function run_cron_raffle_draw() {
		$now = current_time( 'mysql' );

		$args = array(
			'post_type' 		=> 'product',
			'posts_per_page' 	=> 500,
			'meta_query'		=> array(
					'relation' => 'AND',
					array(
						'key'     => '_raffle_item',
						'value'   => 1,
						'compare' => '='
					),
					array(
						'key'     => '_raffle_date',
						'value'   => $now,
						'compare' => '<',
						'type'	  => 'DATE'
					),
					array(
						'key'     => '_winner_raffle_code',
						'compare' => 'NOT EXISTS'
					)
				),
			'meta_key'			=> '_raffle_date',
			'orderby'			=> 'meta_value',
			'order' 			=> 'ASC',
		);
		$products = get_posts( $args );
		

		foreach($products as $product){
			$ticket_code = $this->raffle_winner_for_product($product->ID);
		}
		//exit
	} // end run_cron_raffle_draw 
}
new Raffle_Ticket_Processing;