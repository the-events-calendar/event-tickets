import * as React from 'react';
import { IconProps } from '@tec/common/classy/types/ElementProps';

/**
 * Renders a "Tag" icon.
 *
 * @since TBD
 *
 * @param {IconProps} props The component props.
 * @return {JSX.Element} The rendered "Tag" icon.
 */
export default function ( { className = '' }: IconProps ): JSX.Element {
	const fullClassName = `classy-icon classy-icon--tag${ className ? ` ${ className }` : '' }`;

	return (
		<span className={ fullClassName }>
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path
					fillRule="evenodd"
					clipRule="evenodd"
					d="M4.75 4C4.33579 4 4 4.33579 4 4.75V12.5761C4 12.7752 4.07911 12.966 4.21991 13.1067L10.9408 19.8215C11.1556 20.0365 11.4107 20.2071 11.6915 20.3235C11.9725 20.44 12.2738 20.5 12.578 20.5C12.8822 20.5 13.1835 20.44 13.4645 20.3235C13.7454 20.207 14.0009 20.036 14.2158 19.8209L19.8254 14.2099L19.295 13.6796L19.8269 14.2083C20.2581 13.7745 20.5 13.1877 20.5 12.5761C20.5 11.9645 20.2581 11.3778 19.8269 10.944L13.1045 4.21974C12.9638 4.07904 12.773 4 12.5741 4H4.75ZM19 12.5761C19 12.7913 18.915 12.9976 18.7636 13.1503L13.1544 18.761C13.0787 18.8368 12.9889 18.8969 12.89 18.9379C12.7911 18.9789 12.685 19 12.578 19C12.4709 19 12.3649 18.9789 12.266 18.9379C12.1671 18.8969 12.0767 18.8362 12.001 18.7604L5.5 12.2653V5.5H12.2634L18.7637 12.002C18.915 12.1547 19 12.361 19 12.5761ZM8.75 9.75C9.30228 9.75 9.75 9.30228 9.75 8.75C9.75 8.19772 9.30228 7.75 8.75 7.75C8.19772 7.75 7.75 8.19772 7.75 8.75C7.75 9.30228 8.19772 9.75 8.75 9.75Z"
					fill="#1e1e1e"
				/>
			</svg>
		</span>
	);
}
