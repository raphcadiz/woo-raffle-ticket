<?php
class Raffle_Ticket {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
            self::$instance = new Raffle_Ticket();
        }

		return self::$instance;
    }

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ) );
		add_action( 'admin_init', array( $this, 'settings_options_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'product_winner_metabox' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_raffle_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_raffle_fields' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'product_raffle_on' ) );
	}

	public function admin_scripts() {
		wp_register_style( 'raffle-admin-style', RT_URL . '/assets/css/raffle-admin-style.css', '1.0', true );
		wp_enqueue_style( 'raffle-admin-style' );

		wp_register_script( 'raffle-admin-script', RT_URL . '/assets/js/raffle-admin.js', '1.0', true );
		$raffle_info = array(
			'ajaxurl' 	=>	admin_url( 'admin-ajax.php' )
		);
		wp_localize_script( 'raffle-admin-script', 'raffle_info', $raffle_info );
		wp_enqueue_script( 'raffle-admin-script' );
	}

	public function public_scripts() {
		global $post;

		wp_register_style( 'raffle-main-style', RT_URL . '/assets/css/raffle-main-style.css', '1.0', true );
		wp_enqueue_style( 'raffle-main-style' );

		if ( is_singular() ) {
			$raffle_date = get_post_meta( $post->ID, '_raffle_date', true );
			$now = current_time( 'mysql' );
			$raffle_date_set = date("Y-m-d H:i:s", strtotime($raffle_date));

			if( $raffle_date_set > $now ) {
				wp_register_script( 'raffle-public-script', RT_URL . '/assets/js/raffle-public.js', '1.0', true );
				$raffle_date = get_post_meta( $post->ID, '_raffle_date', true );
				$date_timer = date("d M Y H:i:s", strtotime($raffle_date));
				wp_localize_script( 'raffle-public-script', 'date_timer', $date_timer );
				wp_enqueue_script( 'raffle-public-script' );
			}
		}
	}

	public function settings_options_init() {
		register_setting( 'raffle_tickets_options', 'raffle_tickets_options', '' );
	}
	
	public function product_winner_metabox() {
		global $post;
		$raffle_item = get_post_meta( $post->ID, '_raffle_item', true );
		if( $raffle_item ){
			add_meta_box(
			    'product_winner',
			    __( "Raffle Product Winner", 'raffle-ticket' ), 
			    array( $this, 'product_winner_metabox_content' ),
			    'product',
			    'normal',
			    ''
			);
		}
	}

	public function product_winner_metabox_content(){
		global $post;
		include_once(RT_PATH_INCLUDES.'/product-raffle-winner.php');
	}

	public function add_custom_raffle_fields() {
		global $woocommerce, $post;
		echo '<div class="options_group">';
		woocommerce_wp_checkbox( 
			array( 
				'id'                => '_raffle_item', 
				'label'             => __( 'Raffle Item', 'raffle-ticket' ), 
				'description'       => __( 'Check if product is for raffle.', 'raffle-ticket' ),
				'cbvalue'			=> 1
			)
		);

		$raffle_item = get_post_meta( $post->ID, '_raffle_item', true );
		$show_raffle_option = ( $raffle_item ) ? 'display:block' : 'display:none';
		echo '<div id="product-raffle-option" style="'.$show_raffle_option.'">';
			woocommerce_wp_text_input( 
				array( 
					'id'                => '_raffle_minimum_amount', 
					'label'             => __( 'Minimum Number of Tickets', 'raffle-ticket' ), 
					'placeholder'       => '', 
					'description'       => __( 'Enter minimum number of tickets before timer starts.', 'raffle-ticket' ),
					'type'              => 'number', 
					'custom_attributes' => array(
							'step' 	=> 'any',
							'min'	=> '0'
						) 
				)
			);

			woocommerce_wp_text_input( 
				array( 
					'id'                => '_raffle_date', 
					'label'             => __( 'Product Raffle Draw On', 'raffle-ticket' ), 
					'placeholder'       => '', 
					'description'       => __( 'Raffle draw on time set.', 'raffle-ticket' ),
					'type'              => 'text',
					'value'				=> get_post_meta( $post->ID, '_raffle_date', true ),
					'custom_attributes' => array(
							'readonly' 	=> 'readonly'
						) 
				)
			);
		echo '</div>';
		echo '</div>';
	}

	public function save_custom_raffle_fields($post_id) {
		$raffle_item = $_POST['_raffle_item'];
		if( !empty( $raffle_item ) )
			update_post_meta( $post_id, '_raffle_item', $raffle_item );
		else
			update_post_meta( $post_id, '_raffle_item', 0 );

		$raffle_minimum_amount = $_POST['_raffle_minimum_amount'];
		if( isset( $raffle_minimum_amount ) )
			update_post_meta( $post_id, '_raffle_minimum_amount', esc_attr( $raffle_minimum_amount ) );

		$raffle_date = $_POST['_raffle_date'];
		if( isset( $raffle_date ) && !empty($raffle_date) )
			update_post_meta( $post_id, '_raffle_date', esc_attr( $raffle_date ) );
	}

	/**
	 * Display information before product summary
	 * on single product view.
	 * - show countdown timer for purchase deadline.
	 * - show sale progress for minimum amount needed.
	 * - show product winner
	 */
	public function product_raffle_on() {
		global $post;
		$raffle_tickets_options = get_option('raffle_tickets_options');
		$raffle_item = get_post_meta( $post->ID, '_raffle_item', true );
	    if( $raffle_item ):
			$winner_order = (int)get_post_meta( $post->ID, '_winner_order_id', true );
			$order = new WC_Order($winner_order);

			if( !empty($winner_order) ) {
				$order = new WC_Order($winner_order);
				echo '<strong>'. $order->billing_first_name .'</strong> won this item.<br /><br />';
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			}
			else {
				$raffle_date = get_post_meta( $post->ID, '_raffle_date', true );
				if( !empty($raffle_date) ){
					$now = current_time( 'mysql' );
					$raffle_date_set = date("Y-m-d H:i:s", strtotime($raffle_date));
					
					if( $raffle_date_set <= $now ) {
						echo '<strong>Raffle Draw On Going.</strong>';
						remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
					}
					else {
						$countdown_js = isset($raffle_tickets_options['countdown_js']) ? $raffle_tickets_options['countdown_js'] : 0;

						if( $countdown_js ) {
							echo '
								<div id="countdown">
									<strong>Purchase will lock in:</strong> <br />
									<span class="countdown-section">
										<span class="countdown-number days">00</span> 
										<span class="countdown-label timeRefDays">days</span>
									</span>
									<span class="countdown-section">
										<span class="countdown-number hours">00</span> 
										<span class="countdown-label timeRefHours">hours</span>
									</span>
									<span class="countdown-section">
										<span class="countdown-number minutes">00</span> 
										<span class="countdown-label timeRefMinutes">minutes</span>
									</span>
									<span>
										<span class="countdown-number seconds">00</span> 
										<span class="countdown-label timeRefSeconds">seconds</span>
									</span>
								</div>';
						} else {
							$raffle_date = new DateTime($raffle_date);
							echo 'Purchase Lock-In on <strong>'.$raffle_date->format('M d, Y H:i:s').'</strong><br /><br />';
						}
					} //end check if raffle date set
				}
				else {
					$minimum_progress = isset($raffle_tickets_options['minimum_progress']) ? $raffle_tickets_options['minimum_progress'] : 0;

					if( $minimum_progress ) {
						$raffle_db = new Raffle_Ticket_Db();
						$total_tickets = $raffle_db->get_tickets_by_product($post->ID);
						$raffle_minimum_amount = get_post_meta( $post->ID, '_raffle_minimum_amount', true );					
						$total = round((count($total_tickets) / $raffle_minimum_amount) * 100);

						echo do_shortcode("[pillar_skill_bar_block layout='horizontal-thin' title='Progress before purchase countdown' amount='$total']");
					}
				}
			}
		endif;
	}
	
}