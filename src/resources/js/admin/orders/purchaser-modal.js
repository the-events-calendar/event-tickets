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

/**
 * Closure to initialize the purchaser edit modal.
 * 
 * @since TBD
 */
( ( $, obj ) => {
	'use strict';

	/**
	 * HTML selectors.
	 *
	 * @since TBD
	 *
	 * @type {{nameDetailNode: string, loader: string, emailDetailNode: string, nameField: string, emailField: string, errorNode: string, input: string, cancelButton: string, form: string, nameErrorNode: string, emailErrorNode: string, saveAndEmailButton: string, saveButton: string}}
	 */
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

	/**
	 * Utility to set displayed message.
	 *
	 * @since TBD
	 *
	 * @param {string} message The message string.
	 */
	obj.setErrorMessage = ( message ) => {
		$( obj.selectors.errorNode ).text( message );
		$( obj.selectors.errorNode ).show();
	}

	/**
	 * Resets the displayed message.
	 *
	 * @since TBD
	 */
	obj.clearErrorMessage = () => {
		$( obj.selectors.errorNode ).text( '' );
		$( obj.selectors.errorNode ).hide();
	}

	/**
	 * Hides the modal spinner.
	 *
	 * @since TBD
	 */
	obj.hideLoader = () => {
		$( obj.selectors.loader ).addClass( 'tribe-common-a11y-hidden' );
	}

	/**
	 * Show the modal spinner.
	 *
	 * @since TBD
	 */
	obj.showLoader = () => {
		$( obj.selectors.loader ).removeClass( 'tribe-common-a11y-hidden' );
	}

	/**
	 * The success callback on the update purchaser POST request.
	 *
	 * @since TBD
	 *
	 * @param {object} response The response object.
	 */
	obj.updateSuccessCallback = ( response ) => {
		if ( response.success ) {
			window['dialog_obj_edit-purchaser-modal'].hide();
			$( obj.selectors.nameDetailNode ).text( response.data.name );
			$( obj.selectors.emailDetailNode ).text( response.data.email );
			$( obj.selectors.emailDetailNode ).attr( 'href', 'mailto:' + response.data.email );
			$( obj.selectors.saveButton ).attr( 'disabled', true );
			$( obj.selectors.saveAndEmailButton ).attr( 'disabled', true );
		} else if ( response.data ) {
			obj.setErrorMessage( response.data );
		}
	}

	/**
	 * Attempts to update the purchaser based on the fields passed.
	 *
	 * @since TBD
	 *
	 * @param {object} post The purchaser fields to be sent in the request.
	 * @returns {*}
	 */
	obj.updatePurchaser = ( post = {} ) => {
		if ( ! obj.isFormValid() ) {
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
			obj.updateSuccessCallback
		).error( () => {
			obj.setErrorMessage( 'Error communicating with the server.' );
		}).always(() => {
			obj.hideLoader();
		});
	}

	/**
	 * Will run validator on the form.
	 *
	 * @since TBD
	 *
	 * @returns {boolean}
	 */
	obj.isFormValid = () => {
		return obj.checkEmailField() && obj.checkNameField();
	}

	/**
	 * Run validator on the email field.
	 *
	 * @since TBD
	 *
	 * @returns {boolean}
	 */
	obj.checkEmailField = () => {
		var email = $(obj.selectors.emailField).val().trim();

		if (!email) {
			$(obj.selectors.emailErrorNode).text("Email is required");
			$(obj.selectors.emailErrorNode).show();
			$(obj.selectors.emailField).addClass('tec-tickets-commerce-error-field');
			return false;
		}
		var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
		if (!emailRegex.test(email)) {
			$(obj.selectors.emailErrorNode).text("Email is invalid");
			$(obj.selectors.emailErrorNode).show();
			$(obj.selectors.emailField).addClass('tec-tickets-commerce-error-field');

			return false;
		}

		$(obj.selectors.emailField).removeClass('tec-tickets-commerce-error-field');
		$(obj.selectors.emailErrorNode).hide();
		return true;
	}

	/**
	 * Runn validator on the name field.
	 *
	 * @since TBD
	 *
	 * @returns {boolean}
	 */
	obj.checkNameField = () => {
		var name = $( obj.selectors.nameField ).val().trim();

		if (!name) {
			$( obj.selectors.nameErrorNode ).text( "Name is required" );
			$( obj.selectors.nameField ).addClass( 'tec-tickets-commerce-error-field' );
			$( obj.selectors.nameErrorNode ).show();

			return false;
		}

		$( obj.selectors.nameErrorNode ).hide();
		$( obj.selectors.nameField ).removeClass( 'tec-tickets-commerce-error-field' );

		return true;
	}

	/**
	 * Binds the form events.
	 *
	 * @since TBD
	 */
	obj.bindForm = () => {
		$( obj.selectors.form ).on( 'submit', ( e ) => {
			e.preventDefault();
			obj.updatePurchaser( obj.getFormFields() );
		});
		$( obj.selectors.saveAndEmailButton ).on( 'click', ( e ) => {
			e.preventDefault();
			var values = obj.getFormFields();
			values.send_email = true;

			obj.updatePurchaser( values );
		});
		$( obj.selectors.input ).on('change keyup', () => {
			$( obj.selectors.saveButton ).attr( 'disabled', false );
			$( obj.selectors.saveAndEmailButton ).attr( 'disabled', false );
		});
		$( obj.selectors.emailField ).on('change keyup', obj.checkEmailField );
		$( obj.selectors.nameField ).on('change keyup', obj.checkNameField );
		$( obj.selectors.cancelButton ).on('click', (e) => {
			e.preventDefault();

			window['dialog_obj_edit-purchaser-modal'].hide();
		});
	}

	/**
	 * Pulls the form fields from the form into an object.
	 *
	 * @since TBD
	 *
	 * @returns {{}}
	 */
	obj.getFormFields = () => {
		var values = $( obj.selectors.form ).serializeArray();
		var fields = {};

		$.each(
			values,
			(i, item) => {
				fields[item.name] = item.value;
			}
		);

		return fields;
	}

	/**
	 * Triggers the loading of the form fields for the purchaser on open of the dialog.
	 *
	 * @since TBD
	 */
	$( tribe.dialogs.events ).on(
		'tecTicketsCommerceOpenPurchaserModal',
		() => {
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
				(response) => {
					$( obj.selectors.nameField ).val( response.data.first_name + ' ' + response.data.last_name );
					$( obj.selectors.emailField ).val( response.data.email );
				}
			).always(
				() => {
					obj.hideLoader();
				}
			).error( () => {
				obj.setErrorMessage( 'Error communicating with the server.' );
			});
		},
	);
} )( jQuery, tribe.tickets.editPurchaser );
