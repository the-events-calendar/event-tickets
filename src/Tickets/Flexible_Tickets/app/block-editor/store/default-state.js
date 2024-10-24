export default {
	isInSeries: window?.TECFtEditorData?.event.isInSeries || false,
	defaultTicketTypeDescriptionTemplate:
		window?.TECFtEditorData
			?.defaultTicketTypeEventInSeriesDescriptionTemplate || '',
	multipleProvidersNoticeTemplate:
		window?.tribe_editor_config?.tickets?.multipleProvidersNoticeTemplate ||
		'',
	series: {
		title: window?.TECFtEditorData?.series?.title || '',
		editLink: window?.TECFtEditorData?.series?.editLink || '',
		hasSeriesPasses: window?.TECFtEditorData?.series?.seriesPassesCount > 0,
		seriesPassTotalCapacity:
			window?.TECFtEditorData?.series?.seriesPassTotalCapacity || 0,
		seriesPassTotalAvailable:
			window?.TECFtEditorData?.series?.seriesPassAvailableCapacity || 0,
		seriesPassSharedCapacity:
			window?.TECFtEditorData?.series?.seriesPassSharedCapacity || 0,
		seriesPassSharedCapacityItems:
			window?.TECFtEditorData?.series?.seriesPassSharedCapacityItems ||
			'',
		seriesPassIndependentCapacity:
			window?.TECFtEditorData?.series?.seriesPassIndependentCapacity || 0,
		seriesPassIndependentCapacityItems:
			window?.TECFtEditorData?.series
				?.seriesPassIndependentCapacityItems || '',
		seriesPassUnlimitedCapacityItems:
			window?.TECFtEditorData?.series?.seriesPassUnlimitedCapacityItems ||
			'',
		hasUnlimitedSeriesPasses:
			window?.TECFtEditorData?.series?.hasUnlimitedSeriesPasses || false,
		headerLink: window?.TECFtEditorData?.series?.headerLink || '#',
		headerLinkText: window?.TECFtEditorData?.series?.headerLinkText || '',
		headerLinkTemplate:
			window?.TECFtEditorData?.series?.headerLinkTemplate || '',
	},
	labels: {
		seriesPassPluralUppercase:
			window?.TECFtEditorData?.labels?.seriesPassPluralUppercase || '',
	},
};
