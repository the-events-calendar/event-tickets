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
		blockId: PropTypes.string.isRequired,
		isDisabled: PropTypes.bool,
	};

	constructor( props ) {
		super( props );
		this.accordionId = uniqid();
	}

	getHeader = () => (
		<Fragment>
			<Dashicon
				className="tribe-editor__ticket__advanced-options-header-icon"
				icon="arrow-down"
			/>
			<span className="tribe-editor__ticket__advanced-options-header-text">
				{ __( 'Advanced Options', 'events-gutenberg' ) }
			</span>
		</Fragment>
	);

	getContent = () => (
		<Fragment>
			<Duration blockId={ this.props.blockId } />
			<SKU blockId={ this.props.blockId } />
		</Fragment>
	);

	getRows = () => ( [
		{
			accordionId: this.accordionId,
			content: this.getContent(),
			contentClassName: 'tribe-editor__ticket__advanced-options-content',
			header: this.getHeader(),
			headerAttrs: { disabled: this.props.isDisabled },
			headerClassName: 'tribe-editor__ticket__advanced-options-header',
		},
	] );

	render() {
		return (
			<Accordion
				className="tribe-editor__ticket__advanced-options"
				rows={ this.getRows() }
			/>
		)
	}
};

export default AdvancedOptions;
