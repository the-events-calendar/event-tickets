/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.5.7
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.dialogs = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};

/**
 * Configures ET emails Object in the Global Tribe variable
 *
 * @since 5.5.7
 * @type   {Object}
 */
tribe.tickets.emails = {};

/**
 * Initializes in a Strict env the code that manages the plugin Emails library.
 *
 * @since 5.5.7
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.emails
 * @return {void}
 */
( function( $, obj ) {
	const $document = $( document );

	/*
	 * Manual Attendees Selectors.
	 *
	 * @since 5.5.7
	 */
	obj.selectors = {
		modalWrapper: '.tribe-modal__wrapper--emails-preview',
		modalTitle: '.tribe-modal__title',
		modalContent: '.tribe-modal__content',
		form: '.tribe-tickets__manual-attendees-form',
		hiddenElement: '.tribe-common-a11y-hidden',
		formCurrentEmail: 'tec_tickets_emails_current_section',
		formHeaderImageUrl: 'tec-tickets-emails-header-image-url',
		formHeaderImageAlignment: 'tec-tickets-emails-header-image-alignment',
		formTicketBgColorName: 'tec-tickets-emails-ticket-bg-color',
		formHeaderBgColorName: 'tec-tickets-emails-header-bg-color',
		formFooterContent: 'tec-tickets-emails-footer-content',
		formFooterCredit: 'tec-tickets-emails-footer-credit',
		formQrCodes: 'tec-tickets-emails-ticket-include-qr-codes',
	};

	/**
	 * Handler for when the modal is being "closed".
	 *
	 * @since 5.5.7
	 * @param {Object} event The close event.
	 * @param {Object} dialogEl The dialog element.
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
	 * @since 5.5.7
	 * @return {void}
	 */
	obj.bindModalClose = function() {
		$( tribe.dialogs.events ).on(
			'tribeDialogCloseEmailsPreviewModal.tribeTickets',
			obj.modalClose,
		);
	};

	/**
	 * Binds events for the modal content container.
	 *
	 * @since 5.6.0
	 * @param  {Event}  event    event object for 'afterAjaxSuccess.tribeTicketsAdmin' event.
	 * @param  {jqXHR}  jqXHR    Request object.
	 * @param  {Object} settings Settings that this request was made with.
	 */
	obj.bindModalEvents = ( event, jqXHR, settings ) => {
		const $container = event.data.container;
		const data = event.data.requestData;
	};

	/**
	 * Unbinds events for the modal content container.
	 *
	 * @since 5.5.7
	 * @param {jQuery} $container jQuery object of the container.
	 */
	obj.unbindModalEvents = function( $container ) {
		$container.off( 'afterAjaxSuccess.tribeTicketsAdmin', obj.bindModalEvents );
		$container.off();
	};

	/**
	 * Handler for when the modal is opened.
	 *
	 * @since 5.5.7
	 * @param {Object} event The show event.
	 * @param {Object} dialogEl The dialog element.
	 * @param {Object} trigger The event.
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
	 * @since 5.5.7
	 * @return {Object}
	 */
	obj.getSettingsContext = function() {
		const context = {};
		const tinyMCE = window.tinyMCE || undefined;

		const currentEmail = $document
			.find( 'input[name=' + obj.selectors.formCurrentEmail + ']' ).val();

		context.currentEmail = currentEmail;

		if ( currentEmail ) {
			const currentEmailOptionPrefix = currentEmail.replace( /_/g, '-' );

			const heading = $document
				.find( 'input[name=' + currentEmailOptionPrefix + '-heading]' ).val();

			context.heading = heading;

			const qrCodes = $document
				.find( 'input[name=' + currentEmailOptionPrefix + '-include-qr-codes]' ).is( ':checked' );

			context.qrCodes = qrCodes;

			const eventLinks = $document
				.find( 'input[name=' + currentEmailOptionPrefix + '-add-event-links]' ).is( ':checked' );

			context.eventLinks = eventLinks;

			const arFields = $document
				.find( 'input[name=' + currentEmailOptionPrefix + '-include-ar-fields]' ).is( ':checked' );

			context.arFields = arFields;

			const usingTicketEmail = $document
				.find( 'input[name=tec-tickets-emails-rsvp-use-ticket-email]' ).is( ':checked' );

			context.useTicketEmail = usingTicketEmail;

			// fetch additional content from the editor.
			context.addContent = tinyMCE !== undefined ? tinyMCE.get( currentEmailOptionPrefix + '-additional-content' ).getContent() : ''; // eslint-disable-line max-len
		} else {
			const ticketBgColor = $document
				.find( 'input[name=' + obj.selectors.formTicketBgColorName + ']' ).val();

			context.ticketBgColor = ticketBgColor;

			const headerBgColor = $document
				.find( 'input[name=' + obj.selectors.formHeaderBgColorName + ']' ).val();

			context.headerBgColor = headerBgColor;

			const headerImageUrl = $document
				.find( 'input[name=' + obj.selectors.formHeaderImageUrl + ']' ).val();

			context.headerImageUrl = headerImageUrl;

			const headerImageAlignment = $document
				.find( 'select[name=' + obj.selectors.formHeaderImageAlignment + ']' ).val();

			context.headerImageAlignment = headerImageAlignment;

			const footerCredit = $document
				.find( 'input[name=' + obj.selectors.formFooterCredit + ']' ).is( ':checked' );

			context.footerCredit = footerCredit;

			// fetch footer content from the editor.
			context.footerContent = tinyMCE !== undefined ? tinyMCE.get( obj.selectors.formFooterContent ).getContent() : ''; // eslint-disable-line max-len

			// If we're in the main Emails settings, we show the all options.
			context.eventLinks = true;
			context.qrCodes = true;
			context.arFields = true;
		}

		return context;
	};

	/**
	 * Bind handler for when the modal is being "opened".
	 *
	 * @since 5.5.7
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
	 * @since 5.5.7
	 * @return {void}
	 */
	obj.ready = function() {
		obj.bindModalOpen();
		obj.bindModalClose();
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.emails );
