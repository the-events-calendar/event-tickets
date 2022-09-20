/**
 * External dependencies
 */
import React, { PureComponent, Fragment } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPContainer from './container/container';
import RSVPDashboard from './dashboard/container';
import RSVPInactiveBlock from './inactive-block/container';
import MoveModal from '@moderntribe/tickets/elements/move-modal';
import './style.pcss';

class RSVP extends PureComponent {
	static propTypes = {
		clientId: PropTypes.string.isRequired,
		created: PropTypes.bool.isRequired,
		initializeRSVP: PropTypes.func.isRequired,
		isInactive: PropTypes.bool.isRequired,
		isLoading: PropTypes.bool.isRequired,
		isModalShowing: PropTypes.bool.isRequired,
		isSelected: PropTypes.bool.isRequired,
		rsvpId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		! this.props.rsvpId && this.props.initializeRSVP();
	}

	renderBlock() {
		const {
			created,
			isInactive,
			isLoading,
			isSelected,
			clientId,
			isModalShowing,
		} = this.props;

		return (
			<Fragment>
				{
					! isSelected && ( ( created && isInactive ) || ! created )
						? <RSVPInactiveBlock />
						: (
							<div className={
								classNames(
									'tribe-editor__rsvp',
									{ 'tribe-editor__rsvp--selected': isSelected },
									{ 'tribe-editor__rsvp--loading': isLoading },
								) }
							>
								<RSVPContainer isSelected={ isSelected } clientId={ clientId } />
								<RSVPDashboard isSelected={ isSelected } />
								{ isLoading && <Spinner /> }
							</div>
						)
				}
				{ isModalShowing && <MoveModal /> }
			</Fragment>
		);
	}

	renderBlockNotSupported() {
		const { clientId } = this.props;
		return (
			<div className="tribe-editor__not-supported-message">
				<p className="tribe-editor__not-supported-message-text">
					{ __( 'RSVPs are not yet supported on recurring events.', 'event-tickets' ) }
					<br />
					<a
						className="tribe-editor__not-supported-message-link"
						href="https://evnt.is/1b7a"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Read about our plans for future features.', 'event-tickets' ) }
					</a>
					<br />
					<Button variant="secondary" onClick={ () =>
						wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId ) }
					>
						{ __( 'Remove block', 'event-tickets' ) }
					</Button>
				</p>
			</div>
		);
	}

	render() {
		if ( this.props.hasRecurrenceRules && this.props.noTicketsOnRecurring ) {
			return this.renderBlockNotSupported();
		}

		return this.renderBlock();
	}
}

export default RSVP;
