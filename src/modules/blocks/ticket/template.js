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
import './style.pcss';
import TicketContainer from './container/container';
import TicketDashboard from './dashboard/container';
import MoveModal from '@moderntribe/tickets/data/shared/move/modal';

class Ticket extends PureComponent {
	static propTypes = {
		blockId: PropTypes.string.isRequired,
		hasTicketsPlus: PropTypes.bool,
		isDisabled: PropTypes.bool,
		isLoading: PropTypes.bool,
		isModalShowing: PropTypes.bool,
		isSelected: PropTypes.bool,
		onBlockUpdate: PropTypes.func,
		removeTicketBlock: PropTypes.func,
	};

	componentDidMount() {
		this.props.onBlockUpdate( this.props.isSelected );
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.isSelected !== this.props.isSelected ) {
			this.props.onBlockUpdate( this.props.isSelected );
		}
	}

	render() {
		const {
			blockId,
			hasTicketsPlus,
			isDisabled,
			isLoading,
			isSelected,
			isModalShowing,
		} = this.props;

		return [
			(
				<article className={ classNames(
					'tribe-editor__ticket',
					{ 'tribe-editor__ticket--disabled': isDisabled },
					{ 'tribe-editor__ticket--selected': isSelected },
					{ 'tribe-editor__ticket--has-tickets-plus': hasTicketsPlus },
				) }
				>
					<TicketContainer blockId={ blockId } isSelected={ isSelected } />
					<TicketDashboard blockId={ blockId } isSelected={ isSelected } />
					{ isLoading && <Spinner /> }
				</article>
			),
			isModalShowing && <MoveModal />,
		];
	}
}

export default Ticket;
