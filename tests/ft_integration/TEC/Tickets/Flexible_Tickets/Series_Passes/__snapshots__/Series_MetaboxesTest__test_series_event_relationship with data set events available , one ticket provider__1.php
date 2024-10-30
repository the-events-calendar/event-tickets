<?php return '	<div class="tec-events-pro-series">
		<label class="tec-events-pro-series__label screen-reader-text" for="tec-events-pro-series__dropdown">
			Events: 		</label>
		<select
			tabindex="-1"
			name="_tec_relationship_series_to_events[]"
			class="tribe-dropdown tec-events-pro-series__dropdown"
			id="_tec_relationship_series_to_events"
			aria-hidden="true"
			data-dropdown-css-width="0"
			data-maximum-selection-size="3"
			data-prevent-clear
			data-attach-container
			data-placeholder="Search events"
			style="width: 100%;"
			multiple
		>
							<option
					class="tec-events-pro-series__dropdown-option"
					value="{{ID}}"
					data-status="publish"
					data-status-label="Published"
					data-recurring="0"
											data-start-date="January 10, 2222"
															>
					Event 1				</option>
							<option
					class="tec-events-pro-series__dropdown-option"
					value="{{ID}}"
					data-status="publish"
					data-status-label="Published"
					data-recurring="0"
											data-start-date="February 10, 2222"
															>
					Event 2				</option>
							<option
					class="tec-events-pro-series__dropdown-option"
					value="{{ID}}"
					data-status="publish"
					data-status-label="Published"
					data-recurring="0"
											data-start-date="March 10, 2222"
															>
					Event 3				</option>
					</select>
		<div class="tec-events-pro-series__selections">
			<span class="tec-events-pro-series__selections-label hidden">
				Update this Series to add the selected events:			</span>
			<ul class="tec-events-pro-series__selections-list"></ul>
		</div>
		<script class="tec-events-pro-series__result-template" id="tec-events-pro-series__result-template" type="text/template">
			<div class="tec-events-pro-series__result">
				<div class="tec-events-pro-series__result-label">
					<span class="tec-events-pro-series__result-label-title"></span>
					<svg viewBox="0 0 12 12" width="12" height="12"><title>Recurring</title><use xlink:href="#recurring" /></svg>
					<span class="tec-events-pro-series__result-label-count-events"></span>
					<span class="tec-events-pro-series__result-label-status"></span>
				</div>
				<div class="tec-events-pro-series__result-date"></div>
			</div>
		</script>
		<script class="tec-events-pro-series__selection-template" id="tec-events-pro-series__selection-template" type="text/template">
			<div class="tec-events-pro-series__selection">
				<span class="tec-events-pro-series__selection-title"></span>
				<svg viewBox="0 0 12 12" width="12" height="12"><title>Recurring</title><use xlink:href="#recurring" /></svg>
				<span class="tec-events-pro-series__selection-count-events"></span>
			</div>
		</script>
	</div>
The ecommerce provider for events must match the provider for the Series. Events with a mismatched provider will not be listed. Change the provider using the <em>Sell tickets using</em> option in the tickets settings.';
