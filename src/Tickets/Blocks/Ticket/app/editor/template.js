/**
 * External dependencies
 */
import React, { PureComponent, Fragment } from 'react';
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
import MoveModal from '../../../../../modules/elements/move-modal/container';
import { applyFilters } from '@wordpress/hooks';

class Ticket extends PureComponent {
	static propTypes = {
		clientId: PropTypes.string.isRequired,
		hasTicketsPlus: PropTypes.bool,
		isDisabled: PropTypes.bool,
		isLoading: PropTypes.bool,
		isModalShowing: PropTypes.bool,
		isSelected: PropTypes.bool,
		onBlockUpdate: PropTypes.func,
		removeTicketBlock: PropTypes.func,
		showTicket: PropTypes.bool,
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
		const { clientId, hasTicketsPlus, isDisabled, isLoading, isSelected, isModalShowing, showTicket } = this.props;

		/**
		 * Filters the ticket `isSelected` property. The property comes fron the Block Editor,
		 * and it's a proxy to many of the interactivity properties of the ticket.
		 *
		 * @since 5.16.0
		 *
		 * @param {boolean} isSelected The ticket `isSelected` property.
		 * @param {Object}  props      The Ticket component props.
		 */
		const filteredIsSelected = applyFilters( 'tec.tickets.blocks.Ticket.isSelected', isSelected, this.props );

		/**
		 * Renders the default ticket form.
		 *
		 * @since 5.24.1
		 *
		 * @return {JSX.Element}
		 */
		const defaultForm = () => {
			return(
				<Fragment>
					<article
						className={classNames(
							'tribe-editor__ticket',
							{ 'tribe-editor__ticket--disabled': isDisabled },
							{
								'tribe-editor__ticket--selected':
								filteredIsSelected,
							},
							{
								'tribe-editor__ticket--has-tickets-plus':
								hasTicketsPlus,
							},
							{
								'tribe-editor__ticket--is-asc': applyFilters(
									'tribe.editor.ticket.isAsc',
									false,
									clientId
								),
							}
						)}
					>
						<TicketContainer
							clientId={clientId}
							isSelected={filteredIsSelected}
						/>
						<TicketDashboard
							clientId={clientId}
							isSelected={filteredIsSelected}
						/>
						{isLoading && <Spinner />}
					</article>
					{isModalShowing && <MoveModal />}
				</Fragment>
			);
		};

		/**
		 * Renders the default ticket form.
		 *
		 * @since 5.24.1
		 *
		 * @param {Function} defaultForm The function used to render the default form.
		 *
		 * @return {JSX.Element} The default ticket form JSX element.
		 */
		const ticketForm = applyFilters(
			'tec.tickets.blocks.Ticket.form',
			defaultForm,
			this.props
		);

		return showTicket ?
			<Fragment>
				{ ticketForm && ticketForm() }
			</Fragment>
			: null;
	}
}

export default Ticket;
