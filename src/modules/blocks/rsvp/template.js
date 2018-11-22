/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import RSVPContainer from './container/container';
import RSVPDashboard from './dashboard/container';
import RSVPInactiveBlock from './inactive-block/container';
import './style.pcss';

class RSVP extends PureComponent {
	static propTypes = {
		created: PropTypes.bool.isRequired,
		deleteRSVP: PropTypes.func.isRequired,
		initializeRSVP: PropTypes.func.isRequired,
		isInactive: PropTypes.bool.isRequired,
		isLoading: PropTypes.bool.isRequired,
		isSelected: PropTypes.bool.isRequired,
		rsvpId: PropTypes.number.isRequired,
	};

	componentDidMount() {
		! this.props.rsvpId && this.props.initializeRSVP();
	}

	componentWillUnmount() {
		this.props.deleteRSVP();
	}

	render() {
		const {
			created,
			isInactive,
			isLoading,
			isSelected
		} = this.props;

		return (
			! isSelected && ( ( created && isInactive ) || ! created )
				? <RSVPInactiveBlock />
				: (
					<div className={ classNames(
						'tribe-editor__rsvp',
						{ 'tribe-editor__rsvp--selected': isSelected },
						{ 'tribe-editor__rsvp--loading': isLoading },
					) }>
						<RSVPContainer isSelected={ isSelected } />
						<RSVPDashboard isSelected={ isSelected } />
						{ isLoading && <Spinner /> }
					</div>
				)
		);
	}
}

export default RSVP;
