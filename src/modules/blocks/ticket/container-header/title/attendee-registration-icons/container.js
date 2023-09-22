/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import {
	globals,
	moment as momentUtil,
} from '@moderntribe/common/utils';

const { select } = wp.data;

const ticketStatusMap = {
	isTicketFuture: __( 'Scheduled', 'your-text-domain' ),
	isTicketPast: __( 'Expired', 'your-text-domain' ),
	isTicketOnSale: __( 'On sale', 'your-text-domain' ),
};

/**
 *
 * @param { boolean } isTicketFuture
 * @param { boolean } isTicketPast
 * @param { boolean } isTicketOnSale
 * @returns { string } The translated sale window label
 */
const getSaleWindowLabel = ( isTicketFuture, isTicketPast, isTicketOnSale ) => {
	switch(true) {
		case isTicketFuture:
			return ticketStatusMap[ 'isTicketFuture' ];
		case isTicketPast:
			return ticketStatusMap[ 'isTicketPast' ];
		case isTicketOnSale:
			return ticketStatusMap[ 'isTicketOnSale' ];
	}
	return '';
}

/**
 *
 * @param { Array } attendeeInfoFields
 * @returns { string } Returns the first 4 attendee fields joined by comma
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
}

const mapStateToProps = ( state, ownProps ) => {
	const attendeeInfoFields = selectors.getTicketAttendeeInfoFields( state, ownProps );
	const attendeeInfoFieldsLabel = getAttendeeInfoFieldsLabel( attendeeInfoFields );
	const hasAttendeeInfoFields = selectors.getTicketHasAttendeeInfoFields( state, ownProps );

	const isTicketFuture = selectors.isTicketFuture( state, ownProps );
	const isTicketPast = selectors.isTicketPast( state, ownProps );
	const isTicketOnSale = selectors.isTicketOnSale( state, ownProps );
	const saleWindowLabel = getSaleWindowLabel( isTicketFuture, isTicketPast, isTicketOnSale );

	const dateFormat = momentUtil.toFormat( globals.dateSettings()?.formats?.date );
	const startDateMoment = selectors.getTicketTempStartDateMoment( state, ownProps );
	const endDateMoment = selectors.getTicketTempEndDateMoment( state, ownProps );
	const fromDate = startDateMoment && startDateMoment.format( dateFormat );
	const toDate = endDateMoment && endDateMoment.format( dateFormat );

	return {
		attendeeInfoFieldsLabel,
		hasAttendeeInfoFields,
		isBlockSelected: select( 'core/editor' ).getSelectedBlock() !== null,
		fromDate,
		saleWindowLabel,
		toDate,
	}
};


export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
