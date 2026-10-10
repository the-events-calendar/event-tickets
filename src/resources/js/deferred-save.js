/**
 * Stages ticket changes in the classic editor instead of saving them at once.
 *
 * Loaded only on a post that uses deferred save, where the server printed the
 * `#tec-tickets-deferred-save` container, the nonce and the row templates. It answers the
 * `tec.tickets.admin.ticket.intercepted` filter the panel script asks before it saves or deletes,
 * keeps the staged state, writes it as hidden `tec_tickets[...]` inputs into the container, and
 * re-renders staged rows and markers after every panel refresh.
 *
 * @since TBD
 */

import { addFilter, addAction } from '@wordpress/hooks';
import { createState, buildHiddenFields, validateFields, duplicateFields, copyOfSaved } from './deferred-save/utils';

const CONTAINER = '#tec-tickets-deferred-save';
const NAMESPACE = 'tec/tickets/deferred-save';

( function ( $, obj ) {
	'use strict';

	const $container = $( CONTAINER );

	if ( ! $container.length ) {
		return;
	}

	const strings = window.tecTicketsDeferredSave || {};
	const state = createState();
	let editing = null; // { position } while a staged new ticket is open in the panel.
	let ticketTypeBeingAdded = '';

	const editor = () => tribe.tickets.editor;
	const $panelBase = () => $( '#tribe_panel_base' );
	const $panelEdit = () => $( '#tribe_panel_edit' );
	const $tickets = () => $( '#event_tickets' );

	obj.isEnabled = () => true;
	// Asked by the panel script before a delete, instead of its "cannot be undone" question.
	obj.deleteConfirm = strings.deleteConfirm || '';
	obj.state = state;
	obj.strings = strings;

	/**
	 * Reads the edit panel into a field set, adding what the AJAX save adds.
	 *
	 * @return {Array<Array<string>>} The field set.
	 */
	const readPanel = () => {
		const $panel = $panelEdit();
		const fields = $panel
			.find( 'input,textarea,select' )
			.serializeArray()
			.map( ( { name, value } ) => [ name, value ] );
		const has = ( name ) => fields.some( ( [ fieldName ] ) => fieldName === name );

		if ( ! has( 'ticket_provider' ) ) {
			fields.push( [
				'ticket_provider',
				$panel.find( '#tec_tickets_ticket_provider' ).val() || $panel.data( 'default-provider' ) || '',
			] );
		}

		if ( ! has( 'ticket_type' ) ) {
			fields.push( [ 'ticket_type', $panel.find( '#ticket_type' ).val() || ticketTypeBeingAdded || 'default' ] );
		}

		return fields;
	};

	const ticketIdInPanel = () => parseInt( $panelEdit().find( '#ticket_id' ).val(), 10 ) || 0;

	const maxInputVars = parseInt( strings.maxInputVars, 10 ) || 0;

	// The site's datepicker format index: staged dates are typed in it.
	const dateFormat = parseInt( strings.dateFormat, 10 ) || 0;
	// The suffix the server's own duplicate gives a copy's name, translated.
	const copySuffix = strings.copySuffix || '(copy)';
	// The decimal separator the panel script's price fields accept, localized for it as `price_format`.
	const decimal = ( window.price_format && window.price_format.decimal ) || '';

	/**
	 * How many fields the post form submits, without the edit panel's, which are disabled on submit.
	 *
	 * @return {number} The count.
	 */
	const submittedFieldCount = () =>
		$( '#post' )
			.find( 'input,select,textarea' )
			.not( $panelEdit().find( 'input,select,textarea' ) )
			.serializeArray().length + 1; // The submit button the browser sends, which `serializeArray()` leaves out.

	/**
	 * Shows an error above the post form, in the style of `tribe.validation`.
	 *
	 * @param {string}        heading The message.
	 * @param {Array<string>} items   Lines under it.
	 */
	const showNotice = ( heading, items = [] ) => {
		$( '.tec-tickets-deferred-save-validation' ).remove();
		const $notice = $(
			'<div class="notice notice-error is-dismissible tec-tickets-deferred-save-validation" role="alert"><p></p><ul></ul></div>'
		);
		$notice.find( 'p' ).text( heading );
		items.forEach( ( item ) => $( '<li>' ).text( item ).appendTo( $notice.find( 'ul' ) ) );
		$( '.wp-header-end' ).after( $notice );
		window.scrollTo( { top: 0 } );
	};

	/**
	 * Hands back the publish buttons WordPress disables before the form submits.
	 */
	const handBackSubmit = () => {
		$( '#publish, #save-post' ).prop( 'disabled', false ).removeClass( 'disabled' );
		$( '#publishing-action .spinner, #save-action .spinner' ).removeClass( 'is-active' );
	};

	/**
	 * Writes the staged state into the container as hidden inputs.
	 */
	const renderHiddenFields = () => {
		$container.empty();
		buildHiddenFields( state, { decimal } ).forEach( ( [ name, value ] ) => {
			$( '<input>', { type: 'hidden', name, value } ).appendTo( $container );
		} );
	};

	const templateContent = ( id ) => {
		const template = document.getElementById( id );

		return template ? document.importNode( template.content, true ) : null;
	};

	const fillSlot = ( root, slot, text ) => {
		root.querySelectorAll( `[data-tec-slot="${ slot }"]` ).forEach( ( el ) => {
			el.textContent = text;
		} );
	};

	/**
	 * Finds the saved table of the ticket's type, or finds or creates from the template the table staged rows go into.
	 *
	 * Each saved list table holds one ticket type, RSVPs in their own. The template is the real list table; it goes
	 * where the saved lists are printed, inside the list container, and is marked so it can be removed once nothing
	 * is staged.
	 *
	 * @param {string} type The staged ticket's type.
	 *
	 * @return {jQuery} The table body.
	 */
	const stagedTbody = ( type ) => {
		const $ofType = $panelBase()
			.find( '.tribe-tickets-editor-table-tickets-body' )
			.filter( ( _, tbody ) => tbody.getAttribute( 'data-ticket-type' ) === type )
			.first();

		if ( $ofType.length ) {
			return $ofType;
		}

		let $tbody = $panelBase().find( '.tec-tickets-deferred-save-table .tribe-tickets-editor-table-tickets-body' );

		if ( $tbody.length ) {
			return $tbody;
		}

		const table = templateContent( 'tec-tickets-deferred-save-table' );

		if ( ! table ) {
			return $tbody;
		}

		const wrapper = table.querySelector( '.ticket_list_wrapper' ) || table.querySelector( 'table' );
		if ( wrapper ) {
			wrapper.classList.add( 'tec-tickets-deferred-save-table' );
		}

		const $listContainer = $panelBase().find( '.ticket_list_container' ).first();
		( $listContainer.length ? $listContainer : $panelBase() ).append( table );
		$tbody = $panelBase().find( '.tec-tickets-deferred-save-table .tribe-tickets-editor-table-tickets-body' );

		return $tbody;
	};

	const priceLabel = ( price ) => ( '' === price || '0' === price ? strings.free || '' : price );
	const capacityLabel = ( capacity ) => ( '' === capacity ? strings.unlimited || '' : capacity );

	/**
	 * Appends a row for every staged new ticket.
	 */
	const renderStagedRows = () => {
		$panelBase().find( '.tec-tickets-deferred-save-row' ).remove();
		const { create } = state.toPayload();

		if ( ! create.length ) {
			$panelBase().find( '.tec-tickets-deferred-save-table' ).remove();
			return;
		}

		create.forEach( ( { summary }, position ) => {
			const row = templateContent( 'tec-tickets-deferred-save-row' );
			if ( ! row ) {
				return;
			}
			const tr = row.querySelector( 'tr' );
			tr.setAttribute( 'data-tec-deferred-save-position', String( position ) );
			tr.setAttribute( 'data-ticket-type', summary.type || 'default' );
			fillSlot( tr, 'name', summary.name );
			fillSlot( tr, 'price', priceLabel( summary.price ) );
			fillSlot( tr, 'capacity', capacityLabel( summary.capacity ) );
			stagedTbody( summary.type || 'default' ).append( tr );
		} );
	};

	const markerText = ( kind ) => {
		const template = document.getElementById( 'tec-tickets-deferred-save-marker' );
		const el = template ? template.content.querySelector( `[data-tec-marker-text="${ kind }"]` ) : null;

		return el ? el.textContent : '';
	};

	/**
	 * Puts a marker on a saved ticket's row.
	 *
	 * @param {jQuery}  $row   The row.
	 * @param {string}  kind   `staged`, `delete` or `move`.
	 * @param {string}  target The destination title for a move.
	 * @param {boolean} undo   Whether to offer Undo.
	 */
	const markRow = ( $row, kind, target = '', undo = false ) => {
		const marker = templateContent( 'tec-tickets-deferred-save-marker' );
		if ( ! marker ) {
			return;
		}
		const badge = marker.querySelector( '[data-tec-slot="marker"]' );
		badge.classList.add( `tec-tickets-deferred-save-badge--${ kind }` );
		const text = markerText( kind );
		fillSlot( badge, 'marker-text', text );
		fillSlot( badge, 'marker-text-sr', `${ text }${ target ? ` ${ target }` : '' }.` );
		fillSlot( badge, 'marker-target', target );
		if ( ! undo ) {
			badge.querySelector( '.tec-tickets-deferred-save-badge__undo' ).remove();
		}
		badge.setAttribute( 'data-tec-marker-kind', kind );
		$row.addClass( `tec-tickets-deferred-save-row--${ kind }` );
		$row.find( '.tribe-tickets__tickets-editor-ticket-name-title' ).first().append( badge );
	};

	/**
	 * Disables a saved row's buttons for a staged change, remembering which ones and their title.
	 *
	 * Only buttons that are enabled are touched, so a button something else disabled stays disabled.
	 *
	 * @param {jQuery} $buttons The buttons.
	 * @param {string} reason   Why, shown as the title.
	 */
	const disableForStaging = ( $buttons, reason = '' ) => {
		$buttons.filter( ':enabled' ).each( function () {
			const $button = $( this );
			$button.attr( 'data-tec-deferred-disabled', $button.attr( 'title' ) || '' ).prop( 'disabled', true );

			if ( reason ) {
				$button.attr( 'title', reason );
			}
		} );
	};

	/**
	 * Gives back the buttons a staged change disabled.
	 */
	const restoreDisabledButtons = () => {
		$panelBase()
			.find( '[data-tec-deferred-disabled]' )
			.each( function () {
				const $button = $( this );
				const title = $button.attr( 'data-tec-deferred-disabled' );
				$button.prop( 'disabled', false ).removeAttr( 'data-tec-deferred-disabled' );

				if ( title ) {
					$button.attr( 'title', title );
				} else {
					$button.removeAttr( 'title' );
				}
			} );
	};

	/**
	 * Marks saved rows that have a staged edit, deletion or move.
	 */
	const renderMarkers = () => {
		restoreDisabledButtons();
		// Only the markers put on saved rows; a staged row's own badge is part of its template.
		$panelBase().find( '.tec-tickets-deferred-save-badge[data-tec-slot="marker"]' ).remove();
		$panelBase()
			.find( '[class*="tec-tickets-deferred-save-row--"]' )
			.removeClass( ( _, classes ) =>
				( classes.match( /tec-tickets-deferred-save-row--\S+/g ) || [] ).join( ' ' )
			);
		const { update, delete: deleted, move } = state.toPayload();

		Object.keys( update ).forEach( ( ticketId ) => {
			markRow( $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` ), 'staged' );
		} );

		deleted.forEach( ( ticketId ) => {
			const $row = $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` );
			markRow( $row, 'delete', '', true );
			disableForStaging( $row.find( '.ticket_edit_button, .ticket_duplicate, .ticket_delete' ) );
		} );

		Object.keys( move ).forEach( ( ticketId ) => {
			const { destinationTitle } = state.getMove( parseInt( ticketId, 10 ) );
			const $row = $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` );
			markRow( $row, 'move', destinationTitle, true );
			// One staged change per ticket: an edit waits until the move is undone.
			disableForStaging( $row.find( '.ticket_edit_button' ), strings.editBlocked || '' );
		} );
	};

	const render = () => {
		renderHiddenFields();
		renderStagedRows();
		renderMarkers();
		bindUnload();
	};

	/**
	 * Warns before leaving while anything is staged.
	 */
	const bindUnload = () => {
		$( window ).off( 'beforeunload.tecDeferredSave' );

		if ( state.hasChanges() ) {
			$( window ).on( 'beforeunload.tecDeferredSave', () => strings.leaveMessage || '' );
		}
	};

	/**
	 * Lays a field set over the open edit panel.
	 *
	 * @param {Array<Array<string>>} fields The field set.
	 */
	const fillPanel = ( fields ) => {
		const $panel = $panelEdit();
		const byName = {};
		fields.forEach( ( [ name, value ] ) => {
			byName[ name ] = byName[ name ] || [];
			byName[ name ].push( value );
		} );

		$panel.find( 'input[type="checkbox"], input[type="radio"]' ).each( function () {
			const values = byName[ this.name ] || [];
			this.checked = values.includes( this.value );
		} );

		Object.entries( byName ).forEach( ( [ name, values ] ) => {
			const $fields = $panel
				.find( '[name]' )
				.filter( ( _, el ) => el.name === name )
				.not( ':checkbox, :radio' );
			if ( $fields.is( 'select[multiple]' ) ) {
				$fields.val( values );
			} else {
				$fields.val( values[ values.length - 1 ] ).trigger( 'change' );
			}
		} );

		// The panel script checked the dependent fields against the empty form; check them against these values.
		$panel.find( '.tribe-dependency' ).trigger( 'verify.dependency' );
	};

	/**
	 * Stages the open edit panel: an edit of a saved ticket, a re-edit of a staged one, or a new ticket.
	 */
	const stageSave = () => {
		const fields = readPanel();
		const ticketId = ticketIdInPanel();

		if ( ticketId ) {
			state.stageUpdate( ticketId, fields );
		} else if ( editing ) {
			state.restageCreate( editing.position, fields );
		} else {
			// Kept until the refresh to the list lands: if it fails the panel stays open, and saving again restages this one.
			editing = { position: state.stageCreate( fields ) };
		}

		// The form carries the change now, whether or not the refresh below succeeds.
		render();
		$tickets().trigger( 'tec-deferred-save-staged.tribe', [ state.toPayload() ] );
		editor().fetchPanels( null, 'list' );
	};

	const stageDelete = ( ticketId ) => {
		state.stageDelete( ticketId );
		render();
	};

	/**
	 * Reads a saved ticket's form through the read-only edit request and stages a copy of it.
	 *
	 * @param {number} ticketId The saved ticket to copy.
	 */
	const stageDuplicateOfSaved = ( ticketId ) => {
		// A staged edit is what the admin sees for this ticket: copy that, not what the server has.
		const staged = state.getUpdate( ticketId );

		if ( staged ) {
			state.stageCreate( copyOfSaved( staged.fields, ticketId, copySuffix ) );
			render();
			return;
		}

		const failed = () => showNotice( strings.duplicateFailed || '' );

		$.post(
			window.ajaxurl,
			{
				action: 'tribe-ticket-edit',
				post_id: $( '#post_ID' ).val(),
				ticket_id: ticketId,
				nonce: window.TribeTickets.edit_ticket_nonce,
				is_admin: true,
			},
			( response ) => {
				if ( ! response || ! response.success || ! response.data || ! response.data.ticket ) {
					failed();
					return;
				}
				// Parse inertly: the markup is only read for its fields, so no script in it may run and no asset may load.
				const doc = new window.DOMParser().parseFromString( response.data.ticket, 'text/html' );
				const fields = $( doc.querySelectorAll( 'input,textarea,select' ) )
					.serializeArray()
					.map( ( { name, value } ) => [ name, value ] );
				state.stageCreate( copyOfSaved( fields, ticketId, copySuffix ) );
				render();
			},
			'json'
		).fail( failed );
	};

	addFilter( 'tec.tickets.admin.ticket.intercepted', NAMESPACE, ( intercepted, action, context = {} ) => {
		if ( 'save' === action ) {
			ticketTypeBeingAdded = context.ticketType || ticketTypeBeingAdded;
			stageSave();
			return true;
		}

		if ( 'delete' === action ) {
			const ticketId = parseInt( context.ticketId, 10 );
			if ( ticketId ) {
				stageDelete( ticketId );
			}
			return true;
		}

		if ( 'duplicate' === action ) {
			const ticketId = parseInt( context.ticketId, 10 );
			if ( ticketId ) {
				stageDuplicateOfSaved( ticketId );
			}
			return true;
		}

		return intercepted;
	} );

	$tickets().on( 'click', '.tec-tickets-deferred-save-row__duplicate', function () {
		const position = parseInt( $( this ).closest( 'tr' ).attr( 'data-tec-deferred-save-position' ), 10 );
		const staged = state.getCreate( position );
		if ( staged ) {
			state.stageCreate( duplicateFields( staged.fields, copySuffix ) );
			render();
		}
	} );

	/**
	 * How many of a saved ticket the row says are sold.
	 *
	 * Read from the count the row prints, not worked out from its capacity and availability: with shared
	 * capacity, availability is the whole pool's, so other tickets' sales would count against this one.
	 *
	 * @param {number} ticketId The ticket ID.
	 *
	 * @return {number|undefined} The sold count, or `undefined` when the row does not say.
	 */
	const soldFromRow = ( ticketId ) => {
		const sold = parseInt(
			$panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` ).attr( 'data-ticket-sold' ),
			10
		);

		return Number.isNaN( sold ) ? undefined : sold;
	};

	const showValidationNotice = ( problems ) => {
		showNotice(
			strings.invalidHeading || '',
			problems.map( ( { name, rules } ) => {
				const reasons = rules
					.map( ( rule ) => ( strings.rules && strings.rules[ rule ] ) || rule )
					.join( ', ' );

				return `${ name }: ${ reasons }`;
			} )
		);
	};

	/**
	 * Validates every staged create and update before the post form submits.
	 *
	 * @param {Event} event The submit event.
	 *
	 * @return {boolean} Whether the submit may continue.
	 */
	const validateBeforeSubmit = ( event ) => {
		// The server ignores staged changes on a preview, so nothing invalid can be saved by one.
		if ( 'dopreview' === $( '#wp-preview' ).val() ) {
			return true;
		}

		$( '.tec-tickets-deferred-save-row--invalid' ).removeClass( 'tec-tickets-deferred-save-row--invalid' );
		const { create, update } = state.toPayload();
		const problems = [];

		create.forEach( ( { fields, summary }, position ) => {
			const rules = validateFields( fields, { dateFormat, decimal } );
			if ( rules.length ) {
				problems.push( { name: summary.name || `#${ position + 1 }`, rules } );
				$panelBase()
					.find( `tr[data-tec-deferred-save-position="${ position }"]` )
					.addClass( 'tec-tickets-deferred-save-row--invalid' );
			}
		} );

		Object.entries( update ).forEach( ( [ ticketId, { fields, summary } ] ) => {
			const rules = validateFields( fields, { sold: soldFromRow( ticketId ), dateFormat, decimal } );
			if ( rules.length ) {
				problems.push( { name: summary.name || `#${ ticketId }`, rules } );
				$panelBase()
					.find( `tr[data-ticket-type-id="${ ticketId }"]` )
					.addClass( 'tec-tickets-deferred-save-row--invalid' );
			}
		} );

		if ( ! problems.length ) {
			$( '.tec-tickets-deferred-save-validation' ).remove();
			return true;
		}

		event.preventDefault();
		event.stopImmediatePropagation();
		showValidationNotice( problems );
		// WordPress disables the publish button and shows its spinner before the form submits; hand them back.
		$( '#publish, #save-post' ).prop( 'disabled', false ).removeClass( 'disabled' );
		$( '#publishing-action .spinner, #save-action .spinner' ).removeClass( 'is-active' );
		$( '.tec-tickets-deferred-save-row--invalid' ).first().find( 'button' ).first().trigger( 'focus' );

		return false;
	};

	$( '#post' ).on( 'submit.tecDeferredSaveValidation', validateBeforeSubmit );

	// After every panel refresh: re-render the staged rows and markers, and lay staged values over an opened form.
	addAction( 'tec.tickets.admin.panels.refreshed', NAMESPACE, ( { swapTo } ) => {
		render();

		if ( 'ticket' !== swapTo ) {
			editing = null;
			return;
		}

		if ( editing ) {
			const staged = state.getCreate( editing.position );
			if ( staged ) {
				fillPanel( staged.fields );
			}
			$panelEdit().find( '.tribe-ticket-move-link' ).hide();
			return;
		}

		const ticketId = ticketIdInPanel();
		const update = ticketId ? state.getUpdate( ticketId ) : null;
		if ( update ) {
			fillPanel( update.fields );
			// One staged change per ticket: a move waits until the edit is saved with the post.
			const $moveLink = $panelEdit().find( '.tribe-ticket-move-link' ).hide();
			$( '<p class="tec-tickets-deferred-save-move-blocked">' )
				.text( strings.moveBlocked || '' )
				.insertAfter( $moveLink.length ? $moveLink.last() : $panelEdit().children().last() );
		}
	} );

	// Staged row actions.
	$tickets().on( 'click', '.tec-tickets-deferred-save-row__edit', function () {
		const $row = $( this ).closest( 'tr' );
		const position = parseInt( $row.attr( 'data-tec-deferred-save-position' ), 10 );
		const staged = state.getCreate( position );
		if ( ! staged ) {
			return;
		}
		editing = { position };
		ticketTypeBeingAdded = staged.summary.type || 'default';
		editor().fetchPanels( null, 'ticket', ticketTypeBeingAdded );
	} );

	$tickets().on( 'click', '.tec-tickets-deferred-save-row__remove', function () {
		const position = parseInt( $( this ).closest( 'tr' ).attr( 'data-tec-deferred-save-position' ), 10 );
		state.dropCreate( position );
		render();
	} );

	$tickets().on( 'click', '.tec-tickets-deferred-save-badge__undo', function () {
		const $row = $( this ).closest( 'tr' );
		const ticketId = parseInt( $row.attr( 'data-ticket-type-id' ), 10 );
		const kind = $( this ).closest( '[data-tec-marker-kind]' ).attr( 'data-tec-marker-kind' );
		if ( 'delete' === kind ) {
			state.undoDelete( ticketId );
		} else if ( 'move' === kind ) {
			state.undoMove( ticketId );
		}
		render();
	} );

	// On submit: the staged entries carry everything; an open edit panel must not post its fields as top-level ones.
	// The settings panel is left alone: the post save reads its fields. The inputs are handed back right after the
	// submit. The leave warning comes back only when the page stays: a preview submits to a new tab, and a later
	// handler may cancel the submit.
	$( '#post' ).on( 'submit.tecDeferredSave', ( event ) => {
		const isPreview = 'dopreview' === $( '#wp-preview' ).val();

		// PHP drops the fields past `max_input_vars` without a word; a truncated entry would save half a ticket.
		if ( ! isPreview && maxInputVars && submittedFieldCount() > maxInputVars ) {
			event.preventDefault();
			event.stopImmediatePropagation();
			showNotice( strings.inputLimit || '' );
			handBackSubmit();
			return;
		}

		$( window ).off( 'beforeunload.tecDeferredSave' );

		if ( isPreview ) {
			setTimeout( bindUnload, 0 );
			return;
		}

		const $inputs = $panelEdit().find( 'input,textarea,select' ).not( ':disabled' );
		$inputs.prop( 'disabled', true );
		setTimeout( () => {
			$inputs.prop( 'disabled', false );
			if ( event.isDefaultPrevented() ) {
				bindUnload();
			}
		}, 0 );
	} );

	obj.stageMove = ( ticketId, destinationId, destinationTitle ) => {
		const staged = state.stageMove(
			parseInt( ticketId, 10 ),
			parseInt( destinationId, 10 ),
			destinationTitle || ''
		);
		render();

		return staged;
	};

	obj.render = render;

	$( render );
} )( jQuery, ( tribe.tickets.deferredSave = tribe.tickets.deferredSave || {} ) );

export default tribe.tickets.deferredSave;
