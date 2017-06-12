<div class="wrap" id="raffle-ticket-draw-wrap">
	<div class="content-wrap">
		<h2>Raffle Draw</h2>
		<?php
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
							'key'     => '_winner_raffle_code',
							'compare' => 'NOT EXISTS'
						),
					),
				'meta_key'			=> '_raffle_date',
				'orderby'			=> 'meta_value',
				'order' 			=> 'ASC',
			);
			$unraffle_products = get_posts( $args );
		?>
		<h4>Unraffled Products</h4>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr>
					<th>Product</th>
					<th>Raffle Date</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php
					if( count($unraffle_products) < 1 ):
						?>
						<tr>
							<td colspan="3"><strong>No Data to show</strong></td>
						</tr>
						<?php
					else:
						foreach($unraffle_products as $product):
							?>
							<tr>
								<td><a href="<?= get_edit_post_link($product->ID) ?>"><?= $product->post_title ?></a></td>
								<td>
									<?php 
									$raffle_date = new DateTime(get_post_meta( $product->ID, '_raffle_date', true ));
									echo $raffle_date->format('M d, Y H:i:s');
									?>
								</td>
								<td>
									<a href="javascript:void(0)" class="run-draw" data-id="<?= $product->ID?>">Run Raffle Draw</a>
								</td>
							</tr>
							<?php
						endforeach;
					endif;
				?>
			</tbody>
		</table>
		
		<br />
		<br />

		<?php
			$args = array(
				'post_type' 		=> 'product',
				'posts_per_page' 	=> 15,
				'meta_query'		=> array(
						'relation' => 'AND',
						array(
							'key'     => '_raffle_item',
							'value'   => 1,
							'compare' => '='
						),
						array(
							'key'     => '_winner_raffle_code',
							'value'   => '',
							'compare' => '!='
						),
					),
				'meta_key'			=> '_raffle_date',
				'orderby'			=> 'meta_value',
				'order' 			=> 'ASC',
			);
			$past_raffle_products = get_posts( $args );
		?>
		<h4>Recent Raflled Products and Winners</h4>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr>
					<th>Product</th>
					<th>Raffled Date</th>
					<th>Winner</th>
				</tr>
			</thead>
			<tbody>
				<?php
					if( count($past_raffle_products) < 1 ):
						?>
						<tr>
							<td colspan="3"><strong>No Data to show</strong></td>
						</tr>
						<?php
					else:
						foreach($past_raffle_products as $product):
							$highlighted  = (isset($_GET['product-id']) && $_GET['product-id'] == $product->ID) ? 'highlighted' : '';
							?>
							<tr class="<?= $highlighted ?>">
								<td><a href="<?= get_edit_post_link($product->ID) ?>"><?= $product->post_title ?></a></td>
								<td>
									<?php 
									$raffle_date = new DateTime(get_post_meta( $product->ID, '_raffle_date', true ));
									echo $raffle_date->format('M d, Y H:i:s');
									?>
								</td>
								<td>
									<?php
									$winner_order = (int)get_post_meta( $product->ID, '_winner_order_id', true );
									$order = new WC_Order($winner_order);
									?>
									<a href="<?= get_edit_post_link($order->id) ?>"><?= $order->billing_first_name   ?></a>
								</td>
							</tr>
							<?php
						endforeach;
					endif;
				?>
			</tbody>
		</table>

		<div id="raffle-loading" style="display:none">
			<img src="<?=  RT_URL . '/assets/images/roulette.gif' ?>" width="250">
		</div>
	</div>
</div>