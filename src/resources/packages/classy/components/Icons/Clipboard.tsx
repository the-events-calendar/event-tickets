import * as React from 'react';

/**
 * Clipboard component for rendering a clipboard icon in the Classy editor.
 *
 * @since TBD
 *
 * @return {JSX.Element} The rendered clipboard icon.
 */
export default function Clipboard(): JSX.Element {
	return (
		<svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16" fill="none">
			<path
				d="M9.69961 1.6V1.06667C9.69961 0.476444 9.16542 0 8.49961 0C7.8338 0 7.29961 0.476444 7.29961 1.06667V1.6C6.6338 1.6 6.09961 2.07644 6.09961 2.66667V3.2H10.8996V2.65956C10.8996 2.07644 10.3654 1.6 9.69961 1.6Z"
				fill="#727272"
			/>
			<path
				d="M5.19399 1.59998H3.2998V14.4H13.6998V1.59998H11.7768"
				stroke="#727272"
				strokeWidth="1.5"
				strokeMiterlimit="10"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<path
				d="M6.09961 6.80005H10.8996"
				stroke="#727272"
				strokeWidth="1.5"
				strokeMiterlimit="10"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<path
				d="M6.09961 10.832H10.8996"
				stroke="#727272"
				strokeWidth="1.5"
				strokeMiterlimit="10"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	);
}
