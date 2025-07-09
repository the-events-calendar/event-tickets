/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { Button, Link } from '@moderntribe/common/elements';
import './style.pcss';

export const positions = {
	right: 'right',
	left: 'left',
};

const ActionButton = ( {
	asLink = false,
	children,
	className,
	disabled,
	href = '#',
	icon,
	onClick,
	position = positions.left,
	target,
	...props
} ) => {
	const containerClass = classNames(
		'tribe-editor__action-button',
		`tribe-editor__action-button--icon-${ position }`,
		className
	);

	const getProps = () => {
		const elemProps = { ...props };

		if ( asLink && ! disabled ) {
			elemProps.onMouseDown = () => {
				window.open( href, target );
			};
		} else {
			elemProps.disabled = disabled;
			elemProps.onMouseDown = onClick;
		}

		return elemProps;
	};

	if ( asLink && ! disabled ) {
		return (
			<Link className={ containerClass } { ...{ href: '#', ...getProps() } }>
				{ icon }
				<span className="tribe-editor__action-button__label">{ children }</span>
			</Link>
		);
	}

	return (
		<Button className={ containerClass } { ...getProps() }>
			{ icon }
			<span className="tribe-editor__action-button__label">{ children }</span>
		</Button>
	);
};

ActionButton.propTypes = {
	asLink: PropTypes.bool,
	children: PropTypes.node,
	className: PropTypes.string,
	disabled: PropTypes.bool,
	href: PropTypes.string,
	icon: PropTypes.node.isRequired,
	onClick: PropTypes.func,
	position: PropTypes.oneOf( Object.keys( positions ) ),
	target: PropTypes.string,
};

export default ActionButton;
