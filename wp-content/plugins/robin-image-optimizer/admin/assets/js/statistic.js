jQuery(function($) {
	
	var chat_html_id = 'wio-main-chart';
	var ctx = document.getElementById( chat_html_id );

	window.wio_chart = new Chart( ctx, {
		type:    'doughnut',
		data: {
			datasets: [{
				data: [
					$( '#' + chat_html_id ).attr( 'data-unoptimized' ),
					$( '#' + chat_html_id ).attr( 'data-optimized' ),
					$( '#' + chat_html_id ).attr( 'data-errors' ),
				],
				backgroundColor: [
					'#d6d6d6',
					'#8bc34a',
					'#f1b1b6',
				],
				borderWidth: 0,
				label: 'Dataset 1'
			}]
		},
		options: {
			legend: {
				display: false
			},
			events:    [],
			animation: {
				easing: 'easeOutBounce'
			},
			responsive:       false,
			cutoutPercentage: 80
		}
	} );
});
