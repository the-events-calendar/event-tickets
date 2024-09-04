( function( $ ) {
	'use strict';
	var selectors = {
		form: '#tec-tickets-commerce-edit-purchaser-form'
	};

	const toggleTableRow = () => {
		$( document ).on(
			'click', '.tribe-tickets-commerce-extend-order-row',
			( e ) => {
				e.stopPropagation();

			},
		);
	};

} )( jQuery );
