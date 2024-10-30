/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.10.0
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.dialogs = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};

/**
 * Configures ET Attendees Object in the Global Tribe variable
 *
 * @since 5.10.0
 * @type   {Object}
 */
tribe.tickets.attendees = {};

/**
 * Initializes in a Strict env the code that manages the plugin Attendees library.
 *
 * @since 5.10.0
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.attendees
 * @return {void}
 */
( function( $, obj ) {
	const $document = $( document );

	/*
	 * Manual Attendees Selectors.
	 *
	 * @since 5.10.0
	 */
	obj.selectors = {
		modalWrapper: '.tribe-modal__wrapper--attendee-details',
		modalTitle: '.tribe-modal__title',
		modalContent: '.tribe-modal__content',
		hiddenElement: '.tribe-common-a11y-hidden',
	};

	/**
	 * Handler for when the modal is being "closed".
	 *
	 * @since 5.10.0
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
	 * @since 5.10.0
	 * @return {void}
	 */
	obj.bindModalClose = function() {
		$( tribe.dialogs.events ).on(
			'tribeDialogCloseAttendeeDetailsModal.tribeTickets',
			obj.modalClose,
		);
	};

	/**
	 * Unbinds events for the modal content container.
	 *
	 * @since 5.10.0
	 * @param {jQuery} $container jQuery object of the container.
	 */
	obj.unbindModalEvents = function( $container ) {
		$container.off( 'afterAjaxSuccess.tribeTicketsAdmin', obj.bindModalEvents );
		$container.off();
	};

	/**
	 * Handler for when the modal is opened.
	 *
	 * @since 5.10.0
	 * @param {Object} event The show event.
	 * @param {Object} dialogEl The dialog element.
	 * @param {Object} trigger The event.
	 * @return {void}
	 */
	obj.modalOpen = function( event, dialogEl, trigger ) {
		const $modal = $( dialogEl );
		const $trigger = $( trigger.target ).closest( 'button' );
		const title = $trigger.data( 'modal-title' );
		const request = 'tec_tickets_attendee_details';
		const attendeeId = $trigger.data( 'attendee-id' ) || null;
		const eventId = $trigger.data( 'event-id' ) || null;
		const ticketId = $trigger.data( 'ticket-id' ) || null;
		const provider = $trigger.data( 'provider' ) || null;
		const attendeeName = $trigger.data( 'attendee-name' ) || null;
		const attendeeEmail = $trigger.data( 'attendee-email' ) || null;

		if ( title ) {
			const $modalTitle = $modal.find( obj.selectors.modalTitle );
			$modalTitle.text( title );
		}

		// And replace the content.
		const $modalContent = $modal.find( obj.selectors.modalContent );
		const data = {
			action: 'tribe_tickets_admin_manager',
			request: request,
			attendeeId: attendeeId,
			eventId: eventId,
			ticketId: ticketId,
			provider: provider,
			attendeeName: attendeeName,
			attendeeEmail: attendeeEmail,
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
	 * @since 5.10.0
	 * @return {Object}
	 */
	obj.getContext = function() {
		const context = {};

		return context;
	};

	/**
	 * Bind handler for when the modal is being "opened".
	 *
	 * @since 5.10.0
	 * @return {void}
	 */
	obj.bindModalOpen = function() {
		$( tribe.dialogs.events ).on(
			'tribeDialogShowAttendeeDetailsModal.tribeTickets',
			obj.modalOpen,
		);
	};

	/**
	 * Handles the initialization of the scripts when Document is ready.
	 *
	 * @since 5.10.0
	 * @return {void}
	 */
	obj.ready = function() {
		obj.bindModalOpen();
		obj.bindModalClose();
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.attendees );
