<?php
	$ticket_winner = get_post_meta( $post->ID, '_winner_raffle_code', true );
	$winner_order = (int)get_post_meta( $post->ID, '_winner_order_id', true );
	$order = new WC_Order($winner_order);
?>
<table>
	<tbody>
		<tr>
			<td><strong>Ticket Code:</strong></td>
			<td><?= $ticket_winner ?></td>
		</tr>
		<tr>
			<td><strong>Customer:</strong></td>
			<td>
				<a href="<?= get_edit_post_link($order->id) ?>"><?= $order->billing_first_name   ?></a>
			</td>
		</tr>
	</tbody>
</table>

