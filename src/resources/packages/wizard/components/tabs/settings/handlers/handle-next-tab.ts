import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Interface for HandleNextTabParams.
 */
interface HandleNextTabParams {
	currencyCode: string;
	updateSettings: (settings: Record<string, any>) => void;
	skipPaymentsTab: boolean;
	moveToNextTab: () => void;
	paymentOption: string;
	paymentsTabExists: boolean;
	paymentsTabAdded: boolean;
	setPaymentsTabAdded: (value: boolean) => void;
	addTab: (tab: {
		id: string;
		title: string;
		content: React.ComponentType;
		ref: React.RefObject<any>;
		priority: number;
		isVisible: boolean;
	}) => void;
	reorderTabs: () => void;
}

/**
 * Handle moving to the next tab, potentially adding the Payments tab.
 *
 * @since TBD
 *
 * @param {HandleNextTabParams} params Parameters.
 */
const handleNextTab = ({
	currencyCode,
	updateSettings,
	skipPaymentsTab,
	moveToNextTab,
	paymentOption,
	paymentsTabExists,
	paymentsTabAdded,
	setPaymentsTabAdded,
	addTab,
	reorderTabs,
}: HandleNextTabParams): void => {
	// Save currency setting
	updateSettings({ currency: currencyCode });

	// If we should skip the payments tab due to having only one gateway option
	if (skipPaymentsTab) {
		moveToNextTab();
		return;
	}

	// If Stripe or Square is selected and we need a payment processor
	if (['stripe', 'square', 'paypal'].includes(paymentOption)) {
		// Only add the tab if it doesn't already exist
		if (!paymentsTabExists && !paymentsTabAdded) {
			// Import the payments tab dynamically
			import('../../payments/tab').then((module) => {
				const PaymentsContent = module.default;

				// Add the Payments tab after the current tab (Settings)
				addTab({
					id: 'payments',
					title: __('Payments', 'event-tickets'),
					content: PaymentsContent,
					ref: React.createRef(),
					priority: 25, // Between Settings (20) and Communication (30)
					isVisible: true,
				});

				// Mark that we've added the tab
				setPaymentsTabAdded(true);

				// Reorder tabs based on priority
				reorderTabs();

				// Now move to next tab, which should be the newly added Payments tab
				moveToNextTab();
			});
		} else {
			// Tab already exists, just move to next tab
			moveToNextTab();
		}
	} else {
		// No payment gateway selected, just move to next tab
		moveToNextTab();
	}
};

export default handleNextTab;
