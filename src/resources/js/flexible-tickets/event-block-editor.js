( function ( $ ) {
    if ( window.TECFtEditorData === undefined || window.TECFtEditorData.seriesRelationship === undefined || window.wp.data === undefined || window.__tribe_common_store__ === undefined ) {
        return;
    }

    const wpData = window.wp.data;
    const tecStore = window.__tribe_common_store__;
    const lockId = 'tec.flexible-tickets.different-ticket-provider';
    const {
        fieldSelector,
        containerSelector,
        differentProviderNoticeSelector,
        differentProviderNoticeTemplate
    } = window.TECFtEditorData.seriesRelationship;
    const noticeSelector = containerSelector + ' ' + differentProviderNoticeSelector;

    /**
     * Get the series data from the metabox dropdown element's value attribute.
     *
     * @param {Element|null} element The metabox dropdown element.
     * @param {string} key The key of the series data to retrieve.
     *
     * @returns {string|null} The series data read from the element's value attribute, `null` if not found.
     */
    const getSeriesDataFromElement = function ( element, key ) {
        if ( !( element && element.value ) ) {
            return null;
        }

        const seriesJsonData = element.value;

        try {
            return ( JSON.parse ( seriesJsonData ) )[ key ] || null;
        } catch ( e ) {
            return null;
        }
    };

    /**
     * Get the series data from the `change` event dispatched by Select2 when the series is changed
     *
     * @param {Event} event The `change` event dispatched by Select2.
     * @param {string} key The key of the series data to retrieve.
     *
     * @returns {string|null} The series data read from the selected option data, `null` if not found.
     */
    const getSeriesDataFromEvent = function ( event, key ) {
        if ( !event.currentTarget ) {
            return null;
        }

        return getSeriesDataFromElement ( event.currentTarget, key );
    };

    /**
     * Get the series ticket provider from the `change` event dispatched by Select2 when the series is changed
     *
     * @param {Event} event The `change` event dispatched by Select2.
     *
     * @returns {string|null} The ticket provider of the series read from the selected option data, `null` if not found.
     */
    const getSeriesProviderFromEvent = function ( event ) {
        return getSeriesDataFromEvent ( event, 'ticket_provider' );
    };

    /**
     * Get the event ticket provider from the TEC store state.
     *
     * @returns {string|null} The ticket provider of the event read from the current state, or `null` if not found.
     */
    const getEventProviderFromStore = function () {
        const state = tecStore.getState ();
        if ( !(
            state.tickets && state.tickets.blocks && state.tickets.blocks.ticket && state.tickets.blocks.ticket.provider
        ) ) {
            return null;
        }

        return state.tickets.blocks.ticket.provider;
    };

    /**
     * Lock the post publish button when the event and series have different ticket providers.
     */
    const lockPostPublish = function () {
        wpData.dispatch ( 'core/editor' ).lockPostSaving ( lockId );
    };

    /**
     * Unlock the post publish button.
     */
    const unlockPostPublish = function () {
        wpData.dispatch ( 'core/editor' ).unlockPostSaving ( lockId );
    };

    /**
     * Get the event title from the current state.
     *
     * This is not "live" but pretty close to it.
     *
     * @returns {string} The title of the event read from the current state.
     */
    const getEventTitleFromState = function () {
        return wpData.select ( 'core/editor' ).getEditedPostAttribute ( 'title' );
    };

    /**
     * Get the series title from the `change` event dispatched by Select2 when the series is changed
     * by the user in the metabox dropdown.
     *
     * @param {Event} event The `change` event dispatched by Select2.
     *
     * @returns {string} The title of the series read from the selected option data.
     */
    const getSeriesTitleFromEvent = function ( event ) {
        return getSeriesDataFromEvent ( event, 'title' ) || '';
    };

    /**
     * Show a notice that the event and series have different ticket providers.
     *
     * @param {string} eventTitle The title of the event.
     * @param {string} seriesTitle The title of the series.
     */
    const showDiscordantProviderNotice = function ( eventTitle, seriesTitle ) {
        removeDiscordantProviderNotice ();

        const noticeElement = document.createElement ( 'div' );
        noticeElement.classList.add ( differentProviderNoticeSelector.substring ( 1 ) );
        noticeElement.style[ 'margin-top' ] = 'var(--tec-spacer-1)';
        noticeElement.textContent = differentProviderNoticeTemplate
            .replace ( '%1$s', eventTitle ).replace ( '%2$s', seriesTitle );
        document.querySelector ( containerSelector ).append ( noticeElement );
    };

    /**
     * Remove the notice that the event and series have different ticket providers.
     */
    const removeDiscordantProviderNotice = function () {
        Array.from ( document.querySelectorAll ( noticeSelector ) ).map ( el => el.remove ( true ) );
    };

    /**
     * Toggle the publish lock based on the event and series providers.
     *
     * @param {string|null} eventProvider The current event ticket provider.
     * @param {string|null} seriesProvider The current series ticket provider.
     */
    const togglePublishLock = function ( eventProvider, seriesProvider ) {
        if ( eventProvider === seriesProvider || eventProvider === null || seriesProvider === null ) {
            unlockPostPublish ();
            removeDiscordantProviderNotice ();

            return;
        }

        lockPostPublish ();
        showDiscordantProviderNotice ( getEventTitleFromState (), getSeriesTitleFromEvent ( event ) );
    }

    /**
     * Toggle the publish lock when the series is changed in the metabox dropdown.
     *
     * @param {Event} event The 'change' event dispatched by Select2.
     */
    const togglePublishLockFromMetaboxEvent = function ( event ) {
        const seriesProvider = getSeriesProviderFromEvent ( event );
        const eventProvider = getEventProviderFromStore ();
        togglePublishLock ( eventProvider, seriesProvider );
    };

    /**
     * Subscribe to the series change event.
     *
     * This is the event triggered by the user selecting a series in the metabox dropdown.
     */
    const subscribeToSeriesChange = function () {
        $ ( fieldSelector ).on ( 'change', togglePublishLockFromMetaboxEvent );
    };

    /**
     * Unsubscribe from the series change event to avoid memory leaks.
     */
    const unsubscribeToSeriesChange = function () {
        $ ( fieldSelector ).off ( 'change', togglePublishLockFromMetaboxEvent );
    };

    /**
     * Toggle the publish lock when the ticket provider is changed in the Ticket Settings section of the Editor.
     */
    const togglePublishLockFromTicketSettings = function () {
        const seriesSelect = document.getElementById ( fieldSelector.substring ( 1 ) );
        const seriesProvider = getSeriesDataFromElement ( seriesSelect, 'ticket_provider' );
        const eventProvider = getEventProviderFromStore ();
        togglePublishLock ( eventProvider, seriesProvider );
    };

    /**
     * Subscribe to the series change event when the metabox is rendered.
     */
    const subscribeToSeriesChangeOnStateUpdate = function () {
        if ( !wpData.select ( 'core/edit-post' ).areMetaBoxesInitialized () ) {
            // Before metaboxes are initialized, the series metabox is not yet rendered.
            return;
        }

        // Subscribe to the ticket provider change in the ticket settings metabox.
        tecStore.subscribe ( togglePublishLockFromTicketSettings );

        // Let's make sure we're not subscribed twice.
        unsubscribeToSeriesChange ();
        subscribeToSeriesChange ();
    };

    // Start by subscribing to core/edit-post section of the WP store.
    wpData.subscribe ( subscribeToSeriesChangeOnStateUpdate, 'core/edit-post' );

} ) ( jQuery );