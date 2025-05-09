/* Get data from the state/store */

export const getSettings = ( state ) => state.settings || {};

export const getSetting = ( state, key ) => state.settings[key] || false;

export const getIsSaving = ( state ) => state.isSaving || false;

export const getCompletedTabs = ( state ) => state.completedTabs || [];

export const getSkippedTabs = ( state ) => state.skippedTabs || [];

export const getCountryCurrency = ( state ) => {
    const country = state.settings['country'];
    const countries = state.settings['countries'];
    const countryData = countries && countries[ country ];

    return countryData && countryData.currency || false;
};
