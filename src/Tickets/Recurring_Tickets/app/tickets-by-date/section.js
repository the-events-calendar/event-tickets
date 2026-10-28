/**
 * The Tickets by Date section: every date of a recurring event, each with its recurring event tickets as they stand
 * on that date.
 *
 * @since TBD
 */
import React, { useState } from 'react';
import { __, _x, sprintf } from '@wordpress/i18n';
import './style.pcss';

/**
 * How many dates a page lists.
 *
 * @since TBD
 *
 * @type {number}
 */
const PER_PAGE = 10;

/**
 * How many page buttons show at once.
 *
 * @since TBD
 *
 * @type {number}
 */
const PAGE_BUTTONS = 4;

/**
 * Returns a capacity or an availability as shown, "Unlimited" for -1.
 *
 * @since TBD
 *
 * @param {number} value The value.
 *
 * @return {string} The value shown.
 */
const amount = ( value ) => ( -1 === value ? __( 'Unlimited', 'event-tickets' ) : String( value ) );

/**
 * Returns the dates of a view: the upcoming ones, or all of them.
 *
 * @since TBD
 *
 * @param {Object[]} dates The event's dates, oldest first.
 * @param {string}   range `upcoming` or `all`.
 *
 * @return {Object[]} The dates of the view.
 */
const datesOf = ( dates, range ) => ( 'all' === range ? dates : dates.filter( ( date ) => ! date.past ) );

/**
 * Returns the page numbers to offer around the current page.
 *
 * @since TBD
 *
 * @param {number} page  The current page.
 * @param {number} pages How many pages there are.
 *
 * @return {number[]} The page numbers.
 */
const pageNumbers = ( page, pages ) => {
	const first = Math.max( 1, Math.min( page - 1, pages - PAGE_BUTTONS + 1 ) );

	return Array.from( { length: Math.min( PAGE_BUTTONS, pages ) }, ( _, i ) => first + i );
};

/**
 * A ticket's row in a date's table.
 *
 * @since TBD
 *
 * @param {Object} props        The component props.
 * @param {Object} props.ticket The ticket on that date.
 *
 * @return {JSX.Element} The row.
 */
function TicketRow( { ticket } ) {
	return (
		<div className="tec-tickets-by-date__row">
			<div className="tec-tickets-by-date__ticket">
				<div className="tec-tickets-by-date__ticket-name">{ ticket.name }</div>
				{ ticket.fields.length > 0 && (
					<div className="tec-tickets-by-date__ticket-meta">
						<span className="dashicons dashicons-admin-users" aria-hidden="true" />
						<span className="tec-tickets-by-date__ticket-fields">{ ticket.fields.join( ', ' ) }</span>
					</div>
				) }
				{ ticket.sales_window && (
					<div className="tec-tickets-by-date__ticket-meta">
						<span className="dashicons dashicons-clock" aria-hidden="true" />
						<span>{ ticket.sales_window }</span>
					</div>
				) }
			</div>
			<div>
				{ ticket.price }
				{ ticket.regular_price && <del className="tec-tickets-by-date__regular-price">{ ticket.regular_price }</del> }
			</div>
			<div>{ amount( ticket.capacity ) }</div>
			<div>{ amount( ticket.available ) }</div>
		</div>
	);
}

/**
 * A date's panel: its header, and its tickets when open.
 *
 * @since TBD
 *
 * @param {Object}   props          The component props.
 * @param {Object}   props.date     The date.
 * @param {boolean}  props.open     Whether the panel is open.
 * @param {Function} props.onToggle Opens or closes the panel.
 *
 * @return {JSX.Element} The panel.
 */
function DatePanel( { date, open, onToggle } ) {
	const panelId = `tec-tickets-by-date-${ date.id }`;
	const onKeyDown = ( event ) => {
		if ( 'Enter' === event.key || ' ' === event.key ) {
			event.preventDefault();
			onToggle();
		}
	};

	return (
		<div className={ `tec-tickets-by-date__date${ open ? ' is-open' : '' }` }>
			<div
				role="button"
				tabIndex={ 0 }
				aria-expanded={ open }
				aria-controls={ panelId }
				className="tec-tickets-by-date__date-header"
				onClick={ onToggle }
				onKeyDown={ onKeyDown }
			>
				<div className="tec-tickets-by-date__when">
					<span className="tec-tickets-by-date__chevron dashicons dashicons-arrow-right-alt2" aria-hidden="true" />
					<strong>{ date.label }</strong>
					{ date.time && <span className="tec-tickets-by-date__time">{ date.time }</span> }
					{ date.past && (
						<span className="tec-tickets-by-date__past">
							{ _x( 'Past', 'A date of a recurring event that has ended.', 'event-tickets' ) }
						</span>
					) }
				</div>
				<div className="tec-tickets-by-date__availability">
					{ __( 'Available', 'event-tickets' ) } <strong>{ amount( date.available ) }</strong> / { amount( date.capacity ) }
				</div>
			</div>
			{ open && (
				<div id={ panelId } className="tec-tickets-by-date__body">
					<div className="tec-tickets-by-date__table">
						<div className="tec-tickets-by-date__row tec-tickets-by-date__row--head">
							<div>{ __( 'Ticket', 'event-tickets' ) }</div>
							<div>{ __( 'Price', 'event-tickets' ) }</div>
							<div>{ __( 'Capacity', 'event-tickets' ) }</div>
							<div>{ __( 'Available', 'event-tickets' ) }</div>
						</div>
						{ date.tickets.map( ( ticket ) => (
							<TicketRow key={ ticket.id } ticket={ ticket } />
						) ) }
					</div>
					<div className="tec-tickets-by-date__footer">
						<div>
							<a href={ date.attendees_url }>{ __( 'View Attendees', 'event-tickets' ) }</a>
							<span className="tec-tickets-by-date__separator"> | </span>
							<a href={ date.orders_url }>{ __( 'View Orders', 'event-tickets' ) }</a>
						</div>
						<span className="tec-tickets-by-date__total">
							{ sprintf(
								// translators: %s is the total capacity of the date's tickets, or "Unlimited".
								__( 'Total Ticket Capacity: %s', 'event-tickets' ),
								amount( date.capacity )
							) }
							<span
								className="dashicons dashicons-info-outline"
								title={ __( 'Total does not include Series Passes', 'event-tickets' ) }
								aria-label={ __( 'Total does not include Series Passes', 'event-tickets' ) }
							/>
						</span>
					</div>
				</div>
			) }
		</div>
	);
}

/**
 * The Tickets by Date section.
 *
 * @since TBD
 *
 * @param {Object}  props         The component props.
 * @param {Object}  props.data    The section's data, as of the event's last save.
 * @param {boolean} props.unsaved Whether tickets or dates have changes not saved yet.
 *
 * @return {JSX.Element} The section.
 */
export default function TicketsByDate( { data, unsaved } ) {
	const [ range, setRange ] = useState( 'upcoming' );
	const [ page, setPage ] = useState( 1 );
	const [ open, setOpen ] = useState( () => new Set( datesOf( data.dates, 'upcoming' ).slice( 0, 1 ).map( ( date ) => date.id ) ) );

	const dates = datesOf( data.dates, range );
	const pages = Math.ceil( dates.length / PER_PAGE );
	const shown = dates.slice( ( page - 1 ) * PER_PAGE, page * PER_PAGE );

	const goTo = ( next ) => {
		setPage( next );
		setOpen( new Set() );
	};
	const onRangeChange = ( event ) => {
		setRange( event.target.value );
		goTo( 1 );
	};
	const toggle = ( id ) =>
		setOpen( ( current ) => {
			const next = new Set( current );
			next.has( id ) ? next.delete( id ) : next.add( id );

			return next;
		} );

	return (
		<section className="tec-tickets-by-date">
			<div className="tec-tickets-by-date__header">
				<div>
					<h3 className="tec-tickets-by-date__title">{ __( 'Tickets by Date', 'event-tickets' ) }</h3>
					<p className="tec-tickets-by-date__help">{ __( 'Expand a date to see its ticket details.', 'event-tickets' ) }</p>
				</div>
				<select
					className="tec-tickets-by-date__range"
					value={ range }
					onChange={ onRangeChange }
					aria-label={ __( 'Dates to show', 'event-tickets' ) }
				>
					<option value="upcoming">
						{ sprintf(
							// translators: %d is how many dates of the event are still to come.
							__( 'Upcoming dates (%d)', 'event-tickets' ),
							data.counts.upcoming
						) }
					</option>
					<option value="all">
						{ sprintf(
							// translators: %d is how many dates the event has.
							__( 'All dates (%d)', 'event-tickets' ),
							data.counts.all
						) }
					</option>
				</select>
			</div>
			{ unsaved && (
				<div role="status" className="tec-tickets-by-date__unsaved">
					{ __( 'You have unsaved changes to tickets or dates. Save the event to update this list.', 'event-tickets' ) }
				</div>
			) }
			<div className="tec-tickets-by-date__dates">
				{ shown.map( ( date ) => (
					<DatePanel key={ date.id } date={ date } open={ open.has( date.id ) } onToggle={ () => toggle( date.id ) } />
				) ) }
			</div>
			{ pages > 1 && (
				<div className="tec-tickets-by-date__pages">
					<button
						type="button"
						className="tec-tickets-by-date__page-arrow"
						aria-label={ __( 'Previous page', 'event-tickets' ) }
						disabled={ 1 === page }
						onClick={ () => goTo( page - 1 ) }
					>
						<span className="dashicons dashicons-arrow-left-alt2" aria-hidden="true" />
					</button>
					{ pageNumbers( page, pages ).map( ( number ) => (
						<button
							key={ number }
							type="button"
							className={ `tec-tickets-by-date__page${ number === page ? ' is-current' : '' }` }
							aria-current={ number === page ? 'page' : undefined }
							onClick={ () => goTo( number ) }
						>
							{ number }
						</button>
					) ) }
					<button
						type="button"
						className="tec-tickets-by-date__page-arrow"
						aria-label={ __( 'Next page', 'event-tickets' ) }
						disabled={ pages === page }
						onClick={ () => goTo( page + 1 ) }
					>
						<span className="dashicons dashicons-arrow-right-alt2" aria-hidden="true" />
					</button>
				</div>
			) }
		</section>
	);
}
