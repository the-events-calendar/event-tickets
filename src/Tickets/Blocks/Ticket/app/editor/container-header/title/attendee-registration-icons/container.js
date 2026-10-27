/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '../../../../../../../../modules/data/blocks/ticket';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';

const { select } = wp.data;

/**
 * Given a Sale status that is present on the map return a translated label.
 *
 * @param { string } saleStatus The sale status
 * @return { string } The translated sale window label
 */
const getSaleWindowLabel = ( saleStatus ) => {
	if ( ! saleStatus ) {
		return '';
	}

	const labelMap = {
		future: __( 'Scheduled', 'event-tickets' ),
		past: __( 'Expired', 'event-tickets' ),
		onSale: __( 'On sale', 'event-tickets' ),
	};
	return labelMap[ saleStatus ];
};

/**
 * Determine the sale status of the ticket.
 *
 * @since 5.6.7
 * @param { Object } state    The redux state
 * @param { Object } ownProps The ownProps of the component
 * @return { string } Sale status slug.
 */
const getSalesStatus = ( state, ownProps ) => {
	switch ( true ) {
		case selectors.isTicketFuture( state, ownProps ):
			return 'future';
		case selectors.isTicketPast( state, ownProps ):
			return 'past';
		case selectors.isTicketOnSale( state, ownProps ):
			return 'onSale';
	}
	return '';
};

/**
 * Given an array of attendee info fields returs a label for the tooltip
 *
 * @param { Array } attendeeInfoFields The attendee info fields array
 * @return { string } Returns the first 4 attendee fields joined by comma
 */
const getAttendeeInfoFieldsLabel = ( attendeeInfoFields ) => {
	if ( ! Array.isArray( attendeeInfoFields ) ) {
		return '';
	}

	if ( attendeeInfoFields.length === 0 ) {
		return '';
	}

	return attendeeInfoFields
		.slice( 0, 4 )
		.map( ( attendeeInformationField ) => attendeeInformationField.label )
		.join( ', ' );
};

const mapStateToProps = ( state, ownProps ) => {
	const attendeeInfoFields = selectors.getTicketAttendeeInfoFields( state, ownProps );
	const attendeeInfoFieldsLabel = getAttendeeInfoFieldsLabel( attendeeInfoFields );
	const hasAttendeeInfoFields = selectors.getTicketHasAttendeeInfoFields( state, ownProps );

	const saleStatus = getSalesStatus( state, ownProps );
	const saleWindowLabel = getSaleWindowLabel( saleStatus );

	const dateFormat = momentUtil.toFormat( globals.dateSettings().formats.date );
	const startDateMoment = selectors.getTicketTempStartDateMoment( state, ownProps );
	const endDateMoment = selectors.getTicketTempEndDateMoment( state, ownProps );

	/**
	 * Filters the sale dates the ticket header shows in its sale window tooltip.
	 *
	 * @since TBD
	 *
	 * @param {Object} dates          The sale dates.
	 * @param {string} dates.fromDate The date sales start, in the site date format.
	 * @param {string} dates.toDate   The date sales end, in the site date format.
	 * @param {string} clientId       The client ID of the ticket block.
	 */
	const { fromDate, toDate } = applyFilters(
		'tec.tickets.blocks.Ticket.SaleWindow.dates',
		{
			fromDate: startDateMoment && startDateMoment.format( dateFormat ),
			toDate: endDateMoment && endDateMoment.format( dateFormat ),
		},
		ownProps.clientId
	);

	const selectedBlock = select( 'core/block-editor' ).getSelectedBlock();

	return {
		attendeeInfoFieldsLabel,
		hasAttendeeInfoFields,
		isBlockSelected: selectedBlock?.name === 'tribe/tickets',
		fromDate,
		saleWindowLabel,
		toDate,
	};
};

export default compose( withStore(), connect( mapStateToProps ) )( Template );
