/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.pcss';
import Bar from './bar';

const QuantityBar = ( {
	sharedSold = 0,
	sold = 0,
	capacity = 0,
	total = 0,
	isDisabled = false
} ) => {
	return (
		<div className="tribe-editor__quantity-bar">
			{ ! isDisabled && (
				<Fragment>
					<Bar
						className="tribe-editor__quantity-bar__bar--shared-sold"
						value={ sharedSold }
						total={ total }
					/>
					<Bar
						className="tribe-editor__quantity-bar__bar--sold"
						value={ sold }
						total={ total }
					/>
					{ !! capacity && ! ( capacity === total ) && (
						<Bar
							className="tribe-editor__quantity-bar__bar--capacity"
							value={ capacity }
							total={ total }
						>
							<span className="tribe-editor__quantity-bar__bar-label">
								{ __( 'cap', 'event-tickets' ) }
							</span>
						</Bar>
					) }
				</Fragment>
			) }
		</div>
	);
};

QuantityBar.propTypes = {
	sharedSold: PropTypes.number,
	capacity: PropTypes.number,
	sold: PropTypes.number,
	total: PropTypes.number,
	isDisabled: PropTypes.bool,
};

export default QuantityBar;
