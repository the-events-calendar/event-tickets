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
		content: React.ComponentType<{
			moveToNextTab: () => void;
			skipToNextTab: () => void;
		}>;
		ref: React.RefObject<any>;
		priority: number;
		isVisible: boolean;
	}) => void;
	reorderTabs: () => void;
	skipToNextTab: () => void;
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
	skipToNextTab,
}: HandleNextTabParams): void => {
	// Save currency setting
	updateSettings({
		currency: currencyCode,
	});

	// If we should skip the payments tab or no payment gateway is selected
	if (skipPaymentsTab || !['stripe', 'square', 'paypal'].includes(paymentOption)) {
		moveToNextTab();
		return;
	}

	// If the payment tab already exists or was already added
	if (paymentsTabExists || paymentsTabAdded) {
		moveToNextTab();
		return;
	}

	// Dynamic import and add the payments tab
	import('../../payments/tab').then((module) => {
		const PaymentsContent = module.default;

		addTab({
			id: 'payments',
			title: __('Payments', 'event-tickets'),
			content: PaymentsContent,
			ref: React.createRef(),
			priority: 25, // Between Settings (20) and Communication (30)
			isVisible: true,
		});

		setPaymentsTabAdded(true);
		reorderTabs();
		moveToNextTab();
	});
};

export default handleNextTab;
