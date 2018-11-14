/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
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
import TicketEditContainer from './edit-container/container';
import TicketDisplayContainer from './display-container/container';

class Ticket extends PureComponent {

	static propTypes = {
		isEditing: PropTypes.bool,
		setIsSelected: PropTypes.func,
		isSelected: PropTypes.bool,
		clientId: PropTypes.string.isRequired,
		hasBeenCreated: PropTypes.bool,
		isLoading: PropTypes.bool,
	};

	static defaultProps = {
		isEditing: false,
		hasBeenCreated: false,
		isLoading: false,
	};

	updateIsSelected = () => {
		const { setIsSelected, isSelected } = this.props;
		setIsSelected( isSelected );
	};

	componentDidMount() {
		this.updateIsSelected();
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.isSelected !== this.props.isSelected ) {
			this.updateIsSelected();
		}
	}

	componentWillUnmount() {
		this.updateIsSelected();
	}

	renderComponents() {
		const { isEditing, clientId, isSelected } = this.props;

		return isEditing
			? <TicketEditContainer blockId={ clientId } />
			: <TicketDisplayContainer blockId={ clientId } isSelected={ isSelected } />;
	}

	renderSpinner() {
		return (
			<div className="tribe-editor__ticket--loading">
				<Spinner />
			</div>
		);
	}

	render() {
		const { isLoading, isEditing } = this.props;
		const containerClass = classNames( 'tribe-editor__ticket', {
			'tribe-editor__ticket--edit': isEditing,
			'tribe-editor__ticket--display': ! isEditing,
		} );

		return (
			<article className={ containerClass }>
				{ isLoading ? this.renderSpinner() : this.renderComponents() }
			</article>
		);
	}
}

export default Ticket;
