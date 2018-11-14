/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import uniqid from 'uniqid';

/**
 * WordPress dependencies
 */
import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Accordion } from '@moderntribe/common/elements';
import './style.pcss';
import Duration from './duration/container';
import SKU from './sku/container';

class AdvancedOptions extends Component {
	static propTypes = {
		accordionId: PropTypes.string.isRequired,
		contentId: PropTypes.string.isRequired,
		headerId: PropTypes.string.isRequired,
		isActive: PropTypes.bool.isRequired,
		onClick: PropTypes.func.isRequired,
		blockId: PropTypes.string.isRequired,
		accordionTitle: PropTypes.string,
	};

	static defaultProps = {
		accordionId: 'ticketsPlaceholder',
		contentId: 'ticketsPlaceholder',
		headerId: 'ticketsPlaceholder',
		isActive: false,
		accordionTitle: __( 'Advanced Options', 'events-gutenberg' ),
		onClick: () => {},
	};

	constructor( props ) {
		super( props );
		this.accordionId = uniqid();
	}

	getHeader = () => {
		const { accordionTitle } = this.props;
		return (
			<Fragment>
				<Dashicon
					className="tribe-editor__tickets__advanced-options-header-icon"
					icon="arrow-down"
				/>
				<span className="tribe-editor__tickets__advanced-options-header-text">
					{ accordionTitle }
				</span>
			</Fragment>
		);
	}

	getContent = () => (
		<Fragment>
			<Duration blockId={ this.props.blockId } />
			<SKU blockId={ this.props.blockId } />
		</Fragment>
	);

	getRows = () => ( [
		{
			accordionId: this.props.accordionId,
			content: this.getContent(),
			contentClassName: 'tribe-editor__tickets__advanced-options-content',
			header: this.getHeader(),
			headerClassName: 'tribe-editor__tickets__advanced-options-header',
		},
	] );

	render() {
		return (
			<Accordion
				className="tribe-editor__tickets__advanced-options"
				rows={ this.getRows() }
			/>
		)
	}
};

export default AdvancedOptions;
