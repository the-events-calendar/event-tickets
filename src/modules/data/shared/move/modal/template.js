/**
 * External Dependencies
 */
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

export default class MoveModal extends PureComponent {
	static propTypes = {
		hideModal: PropTypes.func.isRequired,
		initialize: PropTypes.func.isRequired,
		isModalShowing: PropTypes.bool.isRequired,
		title: PropTypes.string.isRequired,
	}

	static defaultProps = {
		title: __( 'Move Ticket Types', 'events-gutenberg' ),
	}

	componentDidMount() {
		this.props.initialize();
	}

	render() {
		return this.props.isModalShowing && (
			<Modal
				title={ this.props.title }
				onRequestClose={ this.props.hideModal }
			>
				{ __( 'You can optionally focus on a specific post type:', 'events-gutenberg' ) }
				{ __( 'You can also enter keywords to help find the target event by title or description', 'events-gutenberg' ) }
				{ __( 'Select the post you wish to move the ticket type to:', 'events-gutenberg' ) }
			</Modal>
		);
	}
}
