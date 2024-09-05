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
		form: '#tec-tickets-commerce-edit-purchaser-form',
		cancelButton: '#tec-tickets-commerce-edit-purchaser-cancel',
		saveButton: '#tec-tickets-commerce-edit-purchaser-save',
		input: '#tec-tickets-commerce-edit-purchaser-form input',
		emailField: '#tec-tickets-commerce-edit-purchaser-email',
		nameField: '#tec-tickets-commerce-edit-purchaser-name',
	};

	obj.bindForm = function () {
		$(obj.selectors.form).on('submit', (e) => {
			e.preventDefault();
			var values = obj.getFormFields();
			// @todo ajaxurl
			$.ajax({
				type: 'POST',
				url: '/wp-admin/admin-ajax.php',
				data: {
					action: 'tec_commerce_purchaser_edit',
					_wpnonce: values._wpnonce,
					ID: values.ID,
					name:values.name,
					email:values.email,
				}
			}).success(
				function (response) {
					
				}
			);
		});
		$(obj.selectors.input).on('change', (e) => {
			$(obj.selectors.saveButton).attr('disabled', false);

		});
		$(obj.selectors.cancelButton ).on('click', (e) => {
			e.preventDefault();

			window['dialog_obj_edit-purchaser-modal'].hide()
		});
	};

	obj.getFormFields = () => {
		var values = $( obj.selectors.form ).serializeArray();

		$.each(values, function(i, item){
			values[item.name] = item.value;
		});

		return values;
	}

	$( tribe.dialogs.events ).on(
		'tecTicketsCommerceOpenPurchaserModal',
		( e ) => {
			obj.bindForm();
//wp_ajax_tec_commerce_purchaser_edit
			var values = obj.getFormFields();
			// @todo ajaxurl
			$.ajax({
				url: '/wp-admin/admin-ajax.php',
				data: {
					action: 'tec_commerce_purchaser_edit',
					_wpnonce: values._wpnonce,
					ID: values.ID,
				}
			}).success(
				function (response) {
					$(obj.selectors.nameField).val(response.data.name);
					$(obj.selectors.emailField).val(response.data.email);
				}
			);
		},
	);


} )( jQuery, tribe.tickets.editPurchaser );
