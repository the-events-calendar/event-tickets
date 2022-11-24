/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 * @type   {object}
 */
tribe.tickets = tribe.tickets || {};
tribe.dialogs = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};

/**
 * Configures ET emails Object in the Global Tribe variable
 *
 * @since TBD
 * @type   {object}
 */
tribe.tickets.emails = {};

/**
 * Initializes in a Strict env the code that manages the plugin Emails library.
 *
 * @since TBD
 * @param  {object} $   jQuery
 * @param  {object} obj tribe.tickets.emails
 * @return {void}
 */
( function( $, obj ) {
	const $document = $( document );

	/*
	 * Manual Attendees Selectors.
	 *
	 * @since TBD
	 */
	obj.selectors = {
		modalWrapper: '.tribe-modal__wrapper--emails-preview',
		modalTitle: '.tribe-modal__title',
		modalContent: '.tribe-modal__content',
		form: '.tribe-tickets__manual-attendees-form',
		hiddenElement: '.tribe-common-a11y-hidden',
		validationNotice: '.tribe-tickets__notice--error',
		formTicketBgColorName: 'tec-tickets-emails-ticket-bg-color',
		formHeaderBgColorName: 'tec-tickets-emails-header-bg-color',
	};

	/**
	 * Handler for when the modal is being "closed".
	 *
	 * @since TBD
	 * @param {object} event The close event.
	 * @param {object} dialogEl The dialog element.
	 * @return {void}
	 */
	obj.modalClose = function( event, dialogEl ) {
		const $modal = $( dialogEl );
		const $modalContent = $modal.find( obj.selectors.modalContent );

		obj.unbindModalEvents( $modalContent );
	};

	/**
	 * Bind handler for when the modal is being "closed".
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.bindModalClose = function() {
		$( tribe.dialogs.events ).on(
			'tribeDialogCloseEmailsPreviewModal.tribeTickets',
			obj.modalClose,
		);
	};

	/**
	 * Unbinds events for the modal content container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the container.
	 */
	obj.unbindModalEvents = function( $container ) {
		$container.off( 'afterAjaxSuccess.tribeTicketsAdmin', obj.bindModalEvents );
		$container.off();
	};

	/**
	 * Handler for when the modal is opened.
	 *
	 * @since TBD
	 * @param {object} event The show event.
	 * @param {object} dialogEl The dialog element.
	 * @param {object} trigger The event.
	 * @return {void}
	 */
	obj.modalOpen = function( event, dialogEl, trigger ) {
		const $modal = $( dialogEl );
		const $trigger = $( trigger.target ).closest( 'button' );
		const title = $trigger.data( 'modal-title' );
		const request = 'tec_tickets_preview_email';

		if ( title ) {
			const $modalTitle = $modal.find( obj.selectors.modalTitle );
			$modalTitle.html( title );
		}

		// And replace the content.
		const $modalContent = $modal.find( obj.selectors.modalContent );
		const requestData = {
			action: 'tribe_tickets_admin_manager',
			request: request,
		};

		const contextData = obj.getSettingsContext();

		const data = {
			...requestData,
			...contextData,
		};

		tribe.tickets.admin.manager.request( data, $modalContent );

		// Bind the modal events after AJAX success.
		$modalContent.on(
			'afterAjaxSuccess.tribeTicketsAdmin',
			{ container: $modalContent, requestData: data },
			obj.bindModalEvents,
		);
	};

	/**
	 * Get context to send on the request.
	 *
	 * @since TBD
	 * @return {object}
	 */
	obj.getSettingsContext = function() {
		const context = {};
		// Get email.
		// get colors and image.
		// Get alignment.

		// @todo @juanfra: check if the elements are found in the DOM.
		const ticketBgColor = $document
			.find( 'input[name=' + obj.selectors.formTicketBgColorName + ']' ).val();

		context.ticketBgColor = ticketBgColor;

		const headerBgColor = $document
			.find( 'input[name=' + obj.selectors.formHeaderBgColorName + ']' ).val();

		context.headerBgColor = headerBgColor;

		return context;
	};

	/**
	 * Bind handler for when the modal is being "opened".
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.bindModalOpen = function() {
		$( tribe.dialogs.events ).on(
			'tribeDialogShowEmailsPreviewModal.tribeTickets',
			obj.modalOpen,
		);
	};

	/**
	 * Handles the initialization of the scripts when Document is ready.
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.ready = function() {
		obj.bindModalOpen();
		obj.bindModalClose();

		//console.log( 'PASARELLA' );
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.emails );
