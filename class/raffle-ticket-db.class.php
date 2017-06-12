<?php
class Raffle_Ticket_Db {

	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'woocommerce_raffle_tickets';
	}

	public function install() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'woocommerce_raffle_tickets';

		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name):
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				ticket_code VARCHAR(100),
				product_id integer(11),
				order_id integer(11),
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			@dbDelta( $sql );
		endif;
	}

	public function insert_ticket($data) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix.'woocommerce_raffle_tickets', $data, array( '%s', '%d', '%d' ) );
		
		return $wpdb->insert_id;
	}

	public function get_tickets_by_order($order_id) {
		global $wpdb;
		$tickets = $wpdb->get_results( "SELECT * FROM $this->table WHERE `order_id` = $order_id" );

		return $tickets;
	}

	public function get_tickets_by_product($product_id) {
		global $wpdb;
		$tickets = $wpdb->get_results( "SELECT * FROM $this->table WHERE `product_id` = $product_id" );

		return $tickets;
	}

	public function get_ticket_by_raffle_code($ticket_code) {
		global $wpdb;
		$ticket = $wpdb->get_row( "SELECT * FROM $this->table WHERE `ticket_code` = '$ticket_code'" );

		return $ticket;
	}

	public function get_total_sales_per_product( $product_id ='' ) { 
		global $wpdb;

		$post_status = array('wc-completed', 'wc-processing');	
		 
		$order_items = $wpdb->get_row( $wpdb->prepare(" SELECT SUM( order_item_meta.meta_value ) as _qty, SUM( order_item_meta_3.meta_value ) as _line_total FROM {$wpdb->prefix}woocommerce_order_items as order_items

		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_3 ON order_items.order_item_id = order_item_meta_3.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID

		WHERE posts.post_type = 'shop_order'			
		AND posts.post_status IN ( '".implode( "','", apply_filters( 'awts_include_order_statuses', $post_status ) )."' )
		AND order_items.order_item_type = 'line_item'
		AND order_item_meta.meta_key = '_qty'
		AND order_item_meta_2.meta_key = '_product_id'
		AND order_item_meta_2.meta_value = %s
		AND order_item_meta_3.meta_key = '_line_total'

		GROUP BY order_item_meta_2.meta_value

		", $product_id));
		
		return $order_items;

	}

}