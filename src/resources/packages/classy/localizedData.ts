import { LocalizedData, Settings, ETClassyGlobal, CurrencySettings } from './types/LocalizedData';
import { TecGlobal } from '@tec/common/classy/types/LocalizedData';

declare global {
	interface Window {
		tec: TecGlobal;
	}
}

/**
 * Returns the default localized data.
 *
 * @since TBD
 *
 * @returns {LocalizedData} The default localized data.
 */
export function getDefault(): LocalizedData {
	return {
		settings: {
			currency: {
				code: 'USD',
				symbol: '$',
				decimalSeparator: '.',
				thousandSeparator: ',',
				position: 'prefix',
				precision: 2,
			},
			startOfWeek: 0,
			ticketPostTypes: [ 'tribe_events' ],
		},
		nonces: {
			deleteTicket: '',
			createTicket: '',
			updateTicket: '',
		},
	};
}

export const localizedData: LocalizedData = ( window.tec as ETClassyGlobal ).tickets?.classy?.data ?? getDefault();

/**
 * Gets the localized data.
 *
 * Extending plugins should use this function rather than accessing the localized
 * data directly.
 *
 * @since TBD
 *
 * @returns {LocalizedData} The localized data.
 */
export function getLocalizedData(): LocalizedData {
	return localizedData;
}

/**
 * Gets the settings from the localized data.
 *
 * @since TBD
 *
 * @returns {Settings} The settings.
 */
export function getSettings(): Settings {
	return localizedData.settings;
}

/**
 * Gets the currency settings from the localized data.
 *
 * @since TBD
 *
 * @returns {CurrencySettings} The currency settings.
 */
export function getCurrencySettings(): CurrencySettings {
	return getSettings().currency;
}
