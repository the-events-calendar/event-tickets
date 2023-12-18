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
		passTotalCapacity:
			window?.TECFtEditorData?.series?.seriesPassTotalCapacity || 0,
		passTotalAvailable:
			window?.TECFtEditorData?.series?.seriesPassAvailableCapacity || 0,
		headerLink: window?.TECFtEditorData?.series?.headerLink || '#',
		headerLinkText: window?.TECFtEditorData?.series?.headerLinkText || '',
		headerLinkTemplate:
			window?.TECFtEditorData?.series?.headerLinkTemplate || '',
	},
};
