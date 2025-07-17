import * as React from 'react';

/**
 * ClockIcon component for rendering a clock icon in the Classy editor.
 *
 * @since TBD
 */
export default function Clock(): JSX.Element {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="16"
			height="16"
			viewBox="0 0 16 16"
			fill="none"
		>
			<path
				d="M8 14.5C11.5899 14.5 14.5 11.5899 14.5 8C14.5 4.41015 11.5899 1.5 8 1.5C4.41015 1.5 1.5 4.41015 1.5 8C1.5 11.5899 4.41015 14.5 8 14.5Z"
				stroke="#727272"
				strokeWidth="1.5"
				strokeMiterlimit="10"
			/>
			<path
				d="M8 4V8.5L10.5 10.5"
				stroke="#727272"
				strokeWidth="1.5"
				strokeMiterlimit="10"
				strokeLinecap="round"
			/>
		</svg>
	);
}
