import React, { useRef } from "react";
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import { SETTINGS_STORE_KEY, MODAL_STORE_KEY } from "../data";
import EtIcon from "./img/et";
import MemoizedTabPanel from "./tabs/tabpanel";
import Tab from "./tabs/tab";
import WelcomeContent from "./tabs/welcome/tab";
import SettingsContent from "./tabs/settings/tab";
import CommunicationContent from "./tabs/communication/tab";
import EventsContent from "./tabs/events/tab";

const OnboardingTabs = () => {
	type TabConfig = {
		id: string;
		title: string;
		content: React.ComponentType<any>;
		ref: React.RefObject<HTMLDivElement | null>;
		priority?: number;
		isVisible?: boolean | (() => boolean);
		dependencies?: string[];
	};

	// Initial tab configuration
	const initialTabConfig: TabConfig[] = [
		{
			id: "welcome",
			title: __("Welcome", "event-tickets"),
			content: WelcomeContent,
			ref: useRef<HTMLDivElement>(null),
			priority: 10,
			isVisible: true,
		},
		{
			id: "settings",
			title: __("Selling Tickets", "event-tickets"),
			content: SettingsContent,
			ref: useRef<HTMLDivElement>(null),
			priority: 20,
			isVisible: true,
		},
		{
			id: "communication",
			title: __("Communication", "event-tickets"),
			content: CommunicationContent,
			ref: useRef<HTMLDivElement>(null),
			priority: 30,
			isVisible: true,
		},
		{
			id: "events",
			title: __("Events", "event-tickets"),
			content: EventsContent,
			ref: useRef<HTMLDivElement>(null),
			priority: 40,
			isVisible: true,
		}
	];

	const { closeModal } = useDispatch(MODAL_STORE_KEY);
	const lastActiveTab = useSelect((select) => select(SETTINGS_STORE_KEY).getSetting("currentTab")) || 0;
	const skippedTabs = useSelect((select) => select(SETTINGS_STORE_KEY).getSkippedTabs()) || [];
	const completedTabs = useSelect((select) => select(SETTINGS_STORE_KEY).getCompletedTabs()) || [];
	const completeTab = useDispatch(SETTINGS_STORE_KEY).completeTab;

	// Dynamic tab configuration state
	const [tabsConfig, setTabsConfig] = useState<TabConfig[]>(initialTabConfig);

	// Get visible tabs sorted by priority
	const getVisibleTabsSorted = () => {
		return tabsConfig
			.filter(tab => {
				if (typeof tab.isVisible === 'function') {
					return tab.isVisible();
				}
				return tab.isVisible !== false;
			})
			.sort((a, b) => (a.priority || 0) - (b.priority || 0));
	};

	// Map visible tabs to state
	const [tabsState, setTabsState] = useState(() =>
		getVisibleTabsSorted().map((tab, index) => ({
			...tab,
			disabled: index > lastActiveTab,
		}))
	);

	// Set the current active tab
	const [activeTab, setActiveTab] = useState(0);

	// Function to add a new tab
	const addTab = (newTab: TabConfig) => {
		setTabsConfig(prev => [...prev, newTab]);
	};

	// Function to update a tab's configuration
	const updateTab = (id: string, updates: Partial<TabConfig>) => {
		setTabsConfig(prev =>
			prev.map(tab => tab.id === id ? { ...tab, ...updates } : tab)
		);
	};

	// Function to reorder tabs based on updated priorities
	const reorderTabs = () => {
		setTabsConfig(prev => [...prev].sort((a, b) => (a.priority || 0) - (b.priority || 0)));
	};

	// Update tabs state when tabsConfig changes
	useEffect(() => {
		const visibleTabs = getVisibleTabsSorted();

		setTabsState(
			visibleTabs.map((tab, index) => ({
				...tab,
				disabled: index > lastActiveTab,
			}))
		);
	}, [tabsConfig, lastActiveTab]);

	const updateTabState = (index, changes) => {
		setTabsState((prevState) =>
			prevState.map((tab, i) => (i === index ? { ...tab, ...changes } : tab))
		);
	};

	const updatePreviousTabStates = (index, changes) => {
		setTabsState((prevState) =>
			prevState.map((tab, i) => (i < index ? { ...tab, ...changes } : tab))
		);
	};

	const moveToTab = (index) => {
		if (index >= 0 && index < tabsState.length) {
			const isCompleted = completedTabs.includes(index);
			const isSkipped = skippedTabs.includes(index);

			updatePreviousTabStates(index, {
				completed: isCompleted,
				skipped: isSkipped,
				disabled: false
			});

			updateTabState(index, { disabled: false });
			setActiveTab(index);
		}
	};

	// If we are on the welcome tab, and we have a last active tab, move directly to that tab.
	useEffect(() => {
		if (lastActiveTab > 0) {
			moveToTab(lastActiveTab);
		}
	}, [lastActiveTab]);

	const moveToNextTab = () => {
		if (activeTab < tabsState.length - 1) {
			updateTabState(activeTab, { completed: true });
			completeTab(activeTab);
			updateTabState(activeTab + 1, { disabled: false });
			setActiveTab(prevActiveTab => {
				const newTab = prevActiveTab + 1;
				if (tabsState[newTab].ref.current) {
					tabsState[newTab].ref.current.focus();
				}
				return newTab;
			});
		} else {
			closeModal();
		}
	};

	const skipToNextTab = () => {
		if (activeTab < tabsState.length - 1) {
			updateTabState(activeTab + 1, { disabled: false });
			setActiveTab(prevActiveTab => {
				const newTab = prevActiveTab + 1;
				if (tabsState[newTab].ref.current) {
					tabsState[newTab].ref.current.focus();
				}
				return newTab;
			});
		} else {
			closeModal();
		}
	};

	const handleClick = (index) => {
		if (!tabsState[index].disabled) {
			setActiveTab(index);
		}
	};

	const handleKeyPress = (event) => {
		if (event.key === "ArrowRight") changeTab(1);
		if (event.key === "ArrowLeft") changeTab(-1);
	};

	const changeTab = (direction) => {
		const newIndex = activeTab + direction;
		if (newIndex >= 0 && newIndex < tabsState.length && !tabsState[newIndex].disabled) {
			setActiveTab(newIndex);
			if (tabsState[newIndex].ref.current) {
				tabsState[newIndex].ref.current.focus();
			}
		}
	};

	// Get the current tab based on the active index
	const currentTab = tabsState[activeTab] || tabsState[0];

	return (
		<section className={`tec-tickets-onboarding__tabs tec-tickets-onboarding__tab-${currentTab.id}`}>
			<div className="tec-tickets-onboarding__tabs-header">
				<EtIcon />
				<ul
					role="tablist"
					className="tec-tickets-onboarding__tabs-list"
					aria-label={__("Onboarding Tabs", "event-tickets")}
					onKeyDown={handleKeyPress}
					style={{
						gridTemplateColumns: `repeat(${tabsState.length - 1}, 1fr)`
					}}
				>
					{tabsState.map((tab, index) => (
						<Tab
							key={tab.id}
							index={index}
							tab={tab}
							activeTab={activeTab}
							handleChange={handleClick}
						/>
					))}
				</ul>
			</div>
			{tabsState.map((tab, index) => {
				const TabContent = tab.content;
				return (
					<MemoizedTabPanel
						key={tab.id}
						tabIndex={index}
						id={`${tab.id}Panel`}
						tabId={tab.id}
						activeTab={activeTab}
					>
						<TabContent
							moveToNextTab={moveToNextTab}
							skipToNextTab={skipToNextTab}
							addTab={addTab}
							updateTab={updateTab}
							reorderTabs={reorderTabs}
						/>
					</MemoizedTabPanel>
				);
			})}
		</section>
	);
};

export default OnboardingTabs;
