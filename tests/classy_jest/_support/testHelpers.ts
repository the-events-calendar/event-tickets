import { addQueryArgs } from '@wordpress/url';

/**
 * Helper function to create the expected API path with query parameters.
 * This makes the test more readable and maintainable.
 *
 * @param {string} basePath - The base API endpoint path.
 * @param {Record<string, any>} queryArgs - Query arguments to append.
 * @return {string} The complete path with query parameters.
 */
export const createExpectedPath = ( basePath: string, queryArgs: Record< string, any > = {} ): string => {
	return addQueryArgs( basePath, queryArgs );
};

/**
 * Test constants for API endpoints.
 */
export const TEST_CONSTANTS = {
	tecExperimentalHeader:
		'I understand that this endpoint is experimental and may change in a future release without maintaining backward compatibility. I also understand that I am using this endpoint at my own risk, while support is not provided for it.',
	restEndpoint: '/tec/v1/tickets',
	restUrl: 'https://example.com/wp-json/tec/v1/tickets',
} as const;
