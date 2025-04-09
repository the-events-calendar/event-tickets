import React, { FunctionComponent, LegacyRef, useMemo } from "react";

interface TabProps {
	tab: {
		id: string;
		title: string;
		disabled: boolean;
		completed: boolean;
		panelId: string;
		ref: LegacyRef<HTMLButtonElement>;
	};
	activeTab: number;
	index: number;
	handleChange: (index: number) => void;
}

const Tab: FunctionComponent<TabProps> = ({
	index,
	tab,
	activeTab,
	handleChange,
}) => {
	const { id, title, disabled, completed, panelId, ref } = tab;
	const isActive = activeTab === index;

	const handleClick = () => handleChange(index);

	const tabClasses = useMemo(() => {
	return [
		"event-tickets-onboarding__tab",
		`event-tickets-onboarding__tab--${id}`,
		disabled && "event-tickets-onboarding__tab--disabled",
		isActive && "event-tickets-onboarding__tab--active",
		completed && "event-tickets-onboarding__tab--completed",
	]
		.filter(Boolean)
		.join(" ");
	}, [disabled, isActive, completed]);

	return (
		<li role="presentation" className={tabClasses}>
			<button
				aria-controls={panelId}
				aria-selected={isActive}
				className="event-tickets-onboarding__tab-button"
				disabled={disabled}
				id={id}
				onClick={handleClick}
				ref={ref}
				role="tab"
				tabIndex={isActive ? 0 : -1}
			>
				<span className="event-tickets-onboarding__tab-title">{title}</span>
			</button>
		</li>
	);
};

export default Tab;
