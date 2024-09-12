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
		saveAndEmailButton: '#tec-tickets-commerce-edit-purchaser-save-and-email',
		input: '#tec-tickets-commerce-edit-purchaser-form input',
		emailField: '#tec-tickets-commerce-edit-purchaser-email',
		nameField: '#tec-tickets-commerce-edit-purchaser-name',
		errorNode: '#tec-tickets-commerce-response-error-message',
		nameErrorNode: '.tec-tickets-commerce-purchaser-name .tec-tickets-commerce-error-message',
		emailErrorNode: '.tec-tickets-commerce-purchaser-email .tec-tickets-commerce-error-message',
		loader: '.tribe-common-c-loader',
		nameDetailNode: '.purchaser-name',
		emailDetailNode: '.purchaser-email a',
	};

	obj.setErrorMessage = ( message ) => {
		$( obj.selectors.errorNode ).html( message );
		$( obj.selectors.errorNode ).show();
	}
	obj.clearErrorMessage = () => {
		$( obj.selectors.errorNode ).html( '' );
		$( obj.selectors.errorNode ).hide();
	}

	obj.hideLoader = () => {
		$( obj.selectors.loader ).addClass( 'tribe-common-a11y-hidden' );
	}
	obj.showLoader = () => {
		$( obj.selectors.loader ).removeClass( 'tribe-common-a11y-hidden' );
	}
	obj.updatePurchaser = ( post = {} ) => {
		if(!obj.isFormValid()) {
			return;
		}
		obj.showLoader();
		post.action = 'tec_commerce_purchaser_edit';
		obj.clearErrorMessage();

		return $.ajax({
			method: 'POST',
			url: TicketsEditPurchaserOptions.ajaxurl,
			data: post
		}).success(
			function ( response ) {
				if( response.success ) {
					window['dialog_obj_edit-purchaser-modal'].hide();
					$( obj.selectors.nameDetailNode ).html( response.data.name );
					$( obj.selectors.emailDetailNode ).html( response.data.email );
					$( obj.selectors.emailDetailNode ).attr( 'href', 'mailto:' + response.data.email );
					$( obj.selectors.saveButton ).attr( 'disabled', true );
					$( obj.selectors.saveAndEmailButton ).attr( 'disabled', true );
				} else if ( response.data ) {
					obj.setErrorMessage( response.data );
				}
			}
		).error( function () {
			obj.setErrorMessage( 'Error communicating with the server.' );
		}).always(() => {
			obj.hideLoader();
		});
	}
	obj.isFormValid = () => {
		return obj.checkEmailField() && obj.checkNameField();
	}
	obj.checkEmailField = () => {
		var email = $(obj.selectors.emailField).val().trim();

		if(!email) {
			$(obj.selectors.emailErrorNode).text("Email is required");
			$(obj.selectors.emailErrorNode).show();
			return false;
		}
		var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
		if(!emailRegex.test(email)) {
			$(obj.selectors.emailErrorNode).text("Email is invalid");
			$(obj.selectors.emailErrorNode).show();
			return false;
		}

		$(obj.selectors.emailErrorNode).hide();
		return true;
	}
	obj.checkNameField = () => {
		var name = $(obj.selectors.nameField).val().trim();
		if( ! name ) {
			$(obj.selectors.nameErrorNode).text("Name is required");
			$(obj.selectors.nameErrorNode).show();
			return false;
		}
		$(obj.selectors.nameErrorNode).hide();
		return true;
	}

	obj.bindForm = function () {
		$( obj.selectors.form ).on( 'submit', ( e ) => {
			e.preventDefault();
			obj.updatePurchaser(obj.getFormFields());

		});
		$( obj.selectors.saveAndEmailButton ).on( 'click', ( e ) => {
			e.preventDefault();
			var values = obj.getFormFields();
			values.send_email = true;

			obj.updatePurchaser( values );
		});
		$(obj.selectors.input).on('change keyup', (e) => {
			$( obj.selectors.saveButton ).attr( 'disabled', false );
			$( obj.selectors.saveAndEmailButton ).attr( 'disabled', false );
		});
		$(obj.selectors.emailField).on('change keyup', obj.checkEmailField );
		$(obj.selectors.nameField).on('change keyup', obj.checkNameField );
		$(obj.selectors.cancelButton ).on('click', (e) => {
			e.preventDefault();

			window['dialog_obj_edit-purchaser-modal'].hide();
		});
	};

	obj.getFormFields = () => {
		var values = $( obj.selectors.form ).serializeArray();
		var fields = {};

		$.each(values, function(i, item){
			fields[item.name] = item.value;
		});

		return fields;
	}

	$( tribe.dialogs.events ).on(
		'tecTicketsCommerceOpenPurchaserModal',
		( e ) => {
			obj.bindForm();
			obj.clearErrorMessage();
			var values = obj.getFormFields();
			obj.showLoader();

			$.ajax({
				url: TicketsEditPurchaserOptions.ajaxurl,
				data: {
					action: 'tec_commerce_purchaser_edit',
					_wpnonce: values._wpnonce,
					ID: values.ID,
				}
			}).success(
				function (response) {
					$( obj.selectors.nameField ).val( response.data.first_name + ' ' + response.data.last_name );
					$( obj.selectors.emailField ).val( response.data.email );
				}
			).always(
				function () {
					obj.hideLoader();
				}
			).error( function () {
				obj.setErrorMessage( 'Error communicating with the server.' );
			});
		},
	);


} )( jQuery, tribe.tickets.editPurchaser );
