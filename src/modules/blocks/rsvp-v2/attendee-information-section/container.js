/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import RSVPAttendeeInformationSection from './template';
import { actions, selectors } from '../../../data/blocks/rsvp-v2';
import { plugins } from '@moderntribe/common/data';
import { withStore } from '@moderntribe/common/hoc';
import { showEditAffordances as getShowEditAffordances } from '../utils/block-state';
import RSVPAttendeeRegistration from '../../rsvp/attendee-registration/container';
import './style.pcss';

const AttendeeInformationWithModal = ( {
	clientId,
	fieldNames,
	hasAttendeeInfoFields,
	isModalOpen,
	onEdit,
	rsvpId,
	showEditAffordances,
} ) => (
	<>
		<RSVPAttendeeInformationSection
			fieldNames={ fieldNames }
			hasAttendeeInfoFields={ hasAttendeeInfoFields }
			onEdit={ onEdit }
			rsvpId={ rsvpId }
			showEditAffordances={ showEditAffordances }
		/>
		{ isModalOpen && <RSVPAttendeeRegistration /> }
	</>
);

AttendeeInformationWithModal.propTypes = {
	clientId: PropTypes.string.isRequired,
	fieldNames: PropTypes.arrayOf( PropTypes.string ),
	hasAttendeeInfoFields: PropTypes.bool,
	isModalOpen: PropTypes.bool,
	onEdit: PropTypes.func.isRequired,
	rsvpId: PropTypes.number,
	showEditAffordances: PropTypes.bool,
};

const mapStateToProps = ( state, ownProps ) => {
	const rsvpId = selectors.getRSVPId( state );

	return {
		created: selectors.getRSVPCreated( state ),
		fieldNames: applyFilters( 'tec.tickets.blocks.rsvp.attendeeInformationFields', [], { rsvpId } ),
		hasAttendeeInfoFields: selectors.getRSVPHasAttendeeInfoFields( state ),
		hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
		isModalOpen: selectors.getRSVPIsModalOpen( state ),
		rsvpId,
		showEditAffordances: getShowEditAffordances( {
			created: selectors.getRSVPCreated( state ),
			isSelected: ownProps.isSelected,
		} ),
	};
};

const mapDispatchToProps = ( dispatch ) => ( {
	onEdit: () => dispatch( actions.setRSVPIsModalOpen( true ) ),
} );

const ConnectedAttendeeInformation = compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps )
)( AttendeeInformationWithModal );

const RSVPAttendeeInformationSectionContainer = ( { clientId, isSelected } ) => {
	const Gate = compose(
		withStore(),
		connect( ( state ) => ( {
			created: selectors.getRSVPCreated( state ),
			hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
		} ) )
	)( ( { created, hasTicketsPlus } ) => {
		if ( ! hasTicketsPlus || ! created || ! isSelected ) {
			return null;
		}

		return <ConnectedAttendeeInformation clientId={ clientId } isSelected={ isSelected } />;
	} );

	return <Gate />;
};

RSVPAttendeeInformationSectionContainer.propTypes = {
	clientId: PropTypes.string.isRequired,
	isSelected: PropTypes.bool.isRequired,
};

export default RSVPAttendeeInformationSectionContainer;
