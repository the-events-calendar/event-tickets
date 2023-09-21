/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const { select } = wp.data;

const getSaleWindowLabel = ( isTicketFuture, isTicketPast, isTicketOnSale ) => {
	switch(true) {
		case isTicketFuture:
			return 'Scheduled';
		case isTicketPast:
			return 'Expired';
		case isTicketOnSale:
			return 'On sale';
	}
	return '';
}

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
	const saleWindowLabel = getSaleWindowLabel(isTicketFuture, isTicketPast, isTicketOnSale);

	const startDateMoment = selectors.getTicketTempStartDateMoment( state, ownProps );
	const endDateMoment = selectors.getTicketTempEndDateMoment( state, ownProps );
	const fromDate = startDateMoment && startDateMoment.format('MMMM D, YYYY');
	const toDate = endDateMoment && endDateMoment.format('MMMM D, YYYY');

	return {
		attendeeInfoFieldsLabel,
		hasAttendeeInfoFields,
		isBlockSelected: select('core/editor').getSelectedBlock() !== null,
		fromDate,
		saleWindowLabel,
		toDate,
	}
};


export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
