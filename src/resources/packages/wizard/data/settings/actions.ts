/* Dispatch actions for the reducers to handle */
import { API_ENDPOINT } from "./constants";
import { apiFetch } from '@wordpress/data';

export const TYPES = {
	CREATE: 'CREATE',
	INITIALIZE: 'INITIALIZE',
	IS_SAVING: 'IS_SAVING',
	SAVE_SETTINGS_ERROR: 'SAVE_SETTINGS_ERROR',
	SAVE_SETTINGS_REQUEST: 'SAVE_SETTINGS_REQUEST',
	SAVE_SETTINGS_SUCCESS: 'SAVE_SETTINGS_SUCCESS',
	UPDATE: 'UPDATE',
	SKIP_TAB: 'SKIP_TAB',
	COMPLETE_TAB: 'COMPLETE_TAB',
} as const;

interface Settings {
	[key: string]: any;
}

interface Setting {
	[key: string]: any;
}

interface Action {
	type: string;
	settings?: Settings;
	setting?: Setting;
	payload?: any;
	error?: any;
}

export function initializeSettings(settings) {
	return {
		type: TYPES.INITIALIZE,
		settings
	};
}

export function createSetting(setting) {
	return {
		type: TYPES.CREATE,
		setting
	};
}

export const updateSettings = settings => {
    return{
      type: TYPES.UPDATE,
      settings,
    };
};

export const setSaving = (isSaving) => {
	return {
		type: TYPES.IS_SAVING,
		isSaving
	};
};

export const skipTab = (tabId) => {
	return {
		type: TYPES.SKIP_TAB,
		payload: tabId
	};
}

export const completeTab = (tabId) => {
	return {
		type: TYPES.COMPLETE_TAB,
		payload: tabId
	};
}
