import apiFetch from '@wordpress/api-fetch';

/**
 * Interface for HandleConnectParams.
 */
interface HandleConnectParams {
	gateway: string;
	currencyCode: string;
	actionNonce: string;
	wpNonce: string;
	getSettings: () => any;
	updateSettings: (settings: Record<string, any>) => void;
	setConnectionStatus: (status: string) => void;
	apiEndpoint: string;
}

/**
 * Handle gateway connection process.
 *
 * @since TBD
 *
 * @param {HandleConnectParams} params Parameters.
 */
const handleConnect = async ({
	gateway,
	currencyCode,
	actionNonce,
	wpNonce,
	getSettings,
	updateSettings,
	setConnectionStatus,
	apiEndpoint,
}: HandleConnectParams): Promise<void> => {
	setConnectionStatus('connecting');

	const connectSettings = {
		gateway: gateway,
		currency: currencyCode,
		action_nonce: actionNonce,
	};

	updateSettings(connectSettings);

	apiFetch.use(apiFetch.createNonceMiddleware(wpNonce));

	try {
		const result = await apiFetch({
			method: 'POST',
			data: {
				...getSettings(),
				gateway: gateway,
				action: 'connect',
			},
			path: apiEndpoint,
		});

		if (result && result.signup_url) {
			// Before redirecting, save that we've initiated connection
			updateSettings({
				connecting: true,
				currentTab: 1
			});
			window.location.href = result.signup_url;
		} else {
			setConnectionStatus('failed');
		}
	} catch (error) {
		console.error('Connection error:', error);
		setConnectionStatus('failed');
	}
};

export default handleConnect;
