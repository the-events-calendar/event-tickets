import { LocalizedData, Settings, ETClassyGlobal } from './types/LocalizedData';

/**
 * Returns the default localized data.
 *
 * @since TBD
 *
 * @returns {LocalizedData} The default localized data.
 */
export function getDefault(): LocalizedData {
	return {
		settings: {},
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
