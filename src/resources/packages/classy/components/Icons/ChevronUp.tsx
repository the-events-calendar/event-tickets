import * as React from 'react';

/**
 * ChevronUp component for rendering a chevron up icon in the Classy editor.
 *
 * @since TBD
 *
 * @return {JSX.Element} The rendered chevron up icon.
 */
export default function ChevronUp(): JSX.Element {
	return (
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
			<path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="currentColor"/>
		</svg>
	);
}
