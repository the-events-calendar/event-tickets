( function( $ ) {
	const toggleTableRow = () => {
		$( document ).on(
			'click', '.tribe-tickets-commerce-extend-order-row',
			( e ) => {
				e.stopPropagation();

				const row = $( e.currentTarget ).closest( 'tr' );
				const nextRow = row.next();

				row.toggleClass( 'tec-row-expanded' );
				nextRow.toggleClass( 'tec-row-expanded' );
			},
		);
	};

	toggleTableRow();
} )( jQuery );
