( async function () {
    if ( window.TECFtEditorData === undefined || window.TECFtEditorData.seriesRelationship === undefined || window.wp.data === undefined || window.__tribe_common_store__ === undefined ) {
        return;
    }

    // Get the current file name and its minified status.
    const file = ( new Error ).stack.split ( '/' ).slice ( -1 ).join ();
    const min = file.includes ( '.min.js' ) ? '.min' : '';

    const wpData = window.wp.data;
    const tecStore = window.__tribe_common_store__;
    const lockId = 'tec.flexible-tickets.different-ticket-provider';

    const {
        getSeriesProviderFromEvent,
        getSeriesTitleFromEvent,
        removeDiscordantProviderNotice,
        showDiscordantProviderNotice,
        getSeriesProviderFromSelection,
        getSeriesTitleFromSelection,
        subscribeToSeriesChange
    } = await import ( `./modules/series-relationship${min}.js` );

	/**
	 * Prevents Series Passes from being saved by the Block Editor when editing Events.
	 *
	 * @since TBD
	 *
	 * @param {bool} saveTicketFromPost Whether or not to save the Ticket from the Post.
	 * @param {object} ticket The Ticket object that is being saved, the format is the one retruned by the Tickets REST API.
	 * @param {object} post The Post object that is being saved, the format is the one retruned by the WP REST API.
	 *
	 * @returns {boolean} Whether or not to save the Ticket from the Post.
	 */
	function doNotSaveSeriesPassFromEvent( saveTicketFromPost, ticket, post ) {
		const ticketType = ticket?.details?.type;
		const postType = post?.type;

		if ( !( typeof ticketType === 'string' && typeof postType === 'string' ) ) {
			return saveTicketFromPost;
		}

		if ( ticketType === 'series_pass' && postType !== 'tribe_event_series' ) {
			return false;
		}

		return saveTicketFromPost;
	}

	// Series Passes will appear in the tickets list of Events, but should not be saved by the Event.
	wp.hooks.addFilter (
		'tec_tickets_save_ticket_from_post',
		'tec_tickets_flexible_tickets',
		doNotSaveSeriesPassFromEvent
	);

    /**
     * Get the event ticket provider from the TEC store state.
     *
     * @returns {string|null} The ticket provider of the event read from the current state, or `null` if not found.
     */
    function getEventProviderFromStore() {
        const state = tecStore.getState ();
        if ( !( state.tickets && state.tickets.blocks && state.tickets.blocks.ticket && state.tickets.blocks.ticket.provider ) ) {
            return null;
        }

        return state.tickets.blocks.ticket.provider;
    }

    /**
     * Lock the post publish button when the event and series have different ticket providers.
     */
    function lockPostPublish() {
        wpData.dispatch ( 'core/editor' ).lockPostSaving ( lockId );
    }

    /**
     * Unlock the post publish button.
     */
    function unlockPostPublish() {
        wpData.dispatch ( 'core/editor' ).unlockPostSaving ( lockId );
    }

    /**
     * Get the event title from the current state.
     *
     * This is not "live" but pretty close to it.
     *
     * @returns {string} The title of the event read from the current state.
     */
    function getEventTitleFromState() {
        return wpData.select ( 'core/editor' ).getEditedPostAttribute ( 'title' );
    }

    /**
     * Toggle the publish lock based on the event and series providers.
     *
     * @param {string|null} eventProvider The current event ticket provider.
     * @param {string|null} seriesProvider The current series ticket provider.
     * @param {string} seriesTitle The title of the series.
     */
    function togglePublishLock( eventProvider, seriesProvider, seriesTitle ) {
        if ( eventProvider === seriesProvider || eventProvider === null || seriesProvider === null ) {
            unlockPostPublish ();
            removeDiscordantProviderNotice ();

            return;
        }

        lockPostPublish ();
        showDiscordantProviderNotice ( getEventTitleFromState (), seriesTitle );
    }

    /**
     * Toggle the publish lock when the series is changed in the metabox dropdown.
     *
     * @param {Event} event The 'change' event dispatched by Select2.
     */
    function togglePublishLockFromMetaboxEvent( event ) {
        const seriesProvider = getSeriesProviderFromEvent ( event );
        const eventProvider = getEventProviderFromStore ();
        togglePublishLock ( eventProvider, seriesProvider, getSeriesTitleFromEvent ( event ) );
    }

    /**
     * Toggle the publish lock when the ticket provider is changed in the Ticket Settings section of the Editor.
     */
    function togglePublishLockFromTicketSettings() {
        const seriesProvider = getSeriesProviderFromSelection ();
        const eventProvider = getEventProviderFromStore ();
        const seriesTitle = getSeriesTitleFromSelection ();
        togglePublishLock ( eventProvider, seriesProvider, seriesTitle );
    }

    /**
     * Subscribe to the series change event when the metabox is rendered.
     */
    function subscribeToSeriesChangeOnStateUpdate() {
        if ( !wpData.select ( 'core/edit-post' ).areMetaBoxesInitialized () ) {
            // Before metaboxes are initialized, the series metabox is not yet rendered.
            return;
        }

        // Subscribe to the ticket provider change in the ticket settings metabox.
        tecStore.subscribe ( togglePublishLockFromTicketSettings );

        subscribeToSeriesChange ( togglePublishLockFromMetaboxEvent );
    }

    // Start by subscribing to core/edit-post section of the WP store.
    wpData.subscribe ( subscribeToSeriesChangeOnStateUpdate, 'core/edit-post' );
} ) ();