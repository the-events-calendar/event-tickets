/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const ActionDashboard = ( {
	actions,
	cancelLabel,
	className,
	confirmLabel,
	isCancelDisabled,
	isConfirmDisabled,
	onCancelClick = noop,
	onConfirmClick = noop,
	showCancel = true,
	showConfirm = true,
} ) => {
	const actionsList = actions && !! actions.length && (
		<div className="tribe-editor__action-dashboard__group-left">
			{ actions.map( ( action, index ) => (
				<span key={ `action-${ index }` } className="tribe-editor__action-dashboard__action-wrapper">
					{ action }
				</span>
			) ) }
		</div>
	);

	const cancelButton = showCancel && (
		<Button
			className="tribe-editor__action-dashboard__cancel-button"
			isDisabled={ isCancelDisabled }
			onClick={ onCancelClick }
		>
			{ cancelLabel }
		</Button>
	);

	/* eslint-disable max-len */
	const confirmButton = showConfirm && (
		<Button
			className="tribe-editor__action-dashboard__confirm-button tribe-editor__button--sm tribe-common-c-btn"
			isDisabled={ isConfirmDisabled }
			onMouseDown={ onConfirmClick }
		>
			{ confirmLabel }
		</Button>
	);
	/* eslint-enable max-len */

	const groupRight = ( showCancel || showConfirm ) && (
		<div className="tribe-editor__action-dashboard__group-right">
			{ cancelButton }
			{ confirmButton }
		</div>
	);

	return (
		<section
			className={ classNames(
				'tribe-editor__action-dashboard',
				{
					'tribe-editor__action-dashboard__no-top-bottom-paddings': ! actionsList && ! groupRight,
				},
				className
			) }
		>
			{ actionsList }
			{ groupRight }
		</section>
	);
};

ActionDashboard.propTypes = {
	actions: PropTypes.arrayOf( PropTypes.node ),
	cancelLabel: PropTypes.string,
	className: PropTypes.string,
	confirmLabel: PropTypes.string,
	isCancelDisabled: PropTypes.bool,
	isConfirmDisabled: PropTypes.bool,
	onCancelClick: PropTypes.func,
	onConfirmClick: PropTypes.func,
	showCancel: PropTypes.bool,
	showConfirm: PropTypes.bool,
};

export default ActionDashboard;
