<div class="wrap" id="raffle-ticket-settings-wrap">
	<?php
		settings_errors();
		$raffle_tickets_options = get_option('raffle_tickets_options');
		$hours_before_raffle = isset($raffle_tickets_options['hours_before_raffle']) ? $raffle_tickets_options['hours_before_raffle'] : 72;
		$email_subject = isset($raffle_tickets_options['email_subject']) ? $raffle_tickets_options['email_subject'] : '';
		$email_template_content = isset($raffle_tickets_options['email_template_content']) ? $raffle_tickets_options['email_template_content'] : '';
		$minimum_progress = isset($raffle_tickets_options['minimum_progress']) ? $raffle_tickets_options['minimum_progress'] : 0;
		$countdown_js = isset($raffle_tickets_options['countdown_js']) ? $raffle_tickets_options['countdown_js'] : 0;
	?>
	<div class="content-wrap">
		<h2>Raffle Ticket Settings</h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'raffle_tickets_options' ); ?>
			<?php do_settings_sections( 'raffle_tickets_options' ); ?> 
			<table class="form-table">
				<tbody>
					<tr class="form-field form-required term-name-wrap">
						<th scope="row">
							<label>Raffle Hours</label>
						</th>
						<td>
							<input type="number" name="raffle_tickets_options[hours_before_raffle]" min="0" step="1" value="<?= $hours_before_raffle; ?>" placeholder="72hrs as default" /><br />
							<em>Set number of ours countdown upon reaching minimum amount before raffle.</em>
						</td>
					</tr>
					<tr class="form-field form-required term-name-wrap">
						<th scope="row">
							Email Template
						</th>
						<td>
							<input type="text" name="raffle_tickets_options[email_subject]" value="<?= $email_subject; ?>" placeholder="Email Subject" />
							<br />
							<br />
							<?php
								$email_template_id 	= 'raffle-ticket-email-template';
								$email_template_settings = array(
									'textarea_name'	=> 'raffle_tickets_options[email_template_content]',
									'textarea_rows'	=> 4,
								);
								wp_editor( $email_template_content, $email_template_id , $email_template_settings );
							?>
							<em>Available variables: {product}, {raffle-code}, {first_name}, {last_name}, {city}, {country}</em>
						</td>
					</tr>
					<tr class="form-field form-required term-name-wrap">
						<th scope="row">
							<label>Enable Sale Progress bar</label>
						</th>
						<td>
							<input type="checkbox" name="raffle_tickets_options[minimum_progress]" value="1" <?php checked( $minimum_progress, 1 ); ?>> <br />
							<em>The progress bar will show how many tickets have been bought out of the minimum amount required.</em>
						</td>
					</tr>
					<tr class="form-field form-required term-name-wrap">
						<th scope="row">
							<label>Enable JS Countdown</label>
						</th>
						<td>
							<input type="checkbox" name="raffle_tickets_options[countdown_js]" value="1" <?php checked( $countdown_js, 1 ); ?>>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<input type="submit" name="save_settings" class="button button-primary" value="Save">
			</p>
		</form>
	</div>
</div>