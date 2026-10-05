import React from 'react';

/**
 * Interface for HandleNextTabParams.
 */
interface HandleNextTabParams {
	currencyCode: string;
	updateSettings: ( settings: Record< string, any > ) => void;
	skipPaymentsTab: boolean;
	moveToNextTab: () => void;
	paymentOption: string;
	paymentsTabExists: boolean;
	paymentsTabAdded: boolean;
	setPaymentsTabAdded: ( value: boolean ) => void;
	addTab: ( tab: {
		id: string;
		title: string;
		content: React.ComponentType< {
			moveToNextTab: () => void;
			skipToNextTab: () => void;
		} >;
		ref: React.RefObject< any >;
		priority: number;
		isVisible: boolean;
	} ) => void;
	reorderTabs: () => void;
	skipToNextTab: () => void;
}

/**
 * Handle moving to the next tab, potentially adding the Payments tab.
 *
 * @since 5.24.0
 *
 * @param {HandleNextTabParams} params Parameters.
 */
const handleNextTab = ( { currencyCode, updateSettings, moveToNextTab }: HandleNextTabParams ): void => {
	// Save currency setting
	updateSettings( {
		currency: currencyCode,
	} );

	moveToNextTab();
};

export default handleNextTab;
