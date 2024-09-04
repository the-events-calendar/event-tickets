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


//

/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.dialogs = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};

/**
 * Configures ET Edit Purchaser Modal Object in the Global Tribe variable
 *
 * @since TBD
 * @type   {Object}
 */
tribe.tickets.editPurchaser = {};
( function( $, obj ) {
	'use strict';

	obj.selectors = {
		form: '#tec-tickets-commerce-edit-purchaser-form'
	};

	obj.bindForm = function () {
/*		$(obj.selectors.form).on('submit', (e) => {
			e.preventDefault();

		});*/
	};


	$( tribe.dialogs.events ).on(
		'tecTicketsCommerceOpenPurchaserModal',
		( e ) => {
			obj.bindForm();
		},
	);


} )( jQuery, tribe.tickets.editPurchaser );
