jQuery(function($){

	setTimeout(function(){
		$('.highlighted').removeClass('highlighted');
	},8000); 

	$('input[name=_raffle_item]').on('click', function(e){
		if( $(this).is(':checked') ){
			$('#product-raffle-option').show();
		} else {
			$('#product-raffle-option').hide();
		}
	});

	$('.run-draw').on('click', function(e){
		product_id = $(this).attr('data-id');
		$('#raffle-loading').show();

		$.post(
			raffle_info.ajaxurl,
			{ 
				data : { 
					'product_id' : product_id, 
				},
				action : 'run_raffle_draw_for_prouduct'
			}, 
			function( result, textStatus, xhr ) {
				window.location = location.href + "&product-id=" + result;
				$('#raffle-loading').hide();
			}).fail(function() {
			console.log('Something went wrong. Try again later.');
			$('#raffle-loading').hide();
		});

	});

});