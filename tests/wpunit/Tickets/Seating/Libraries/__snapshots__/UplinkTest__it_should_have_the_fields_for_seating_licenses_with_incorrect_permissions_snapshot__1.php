<?php return array (
  'stellarwp-uplink_tec-seating-heading' => 
  array (
    'type' => 'heading',
    'label' => 'Seat Layouts & Reservations',
  ),
  'stellarwp-uplink_tec-seating' => 
  array (
    'type' => 'html',
    'label' => '',
    'html' => '
	<div class="stellarwp-uplink__license-field">
		<div
			class="stellarwp-uplink-license-key-field"
			id="event-tickets//event-tickets.php"
			data-slug="event-tickets//event-tickets.php"
			data-plugin="event-tickets//event-tickets.php"
			data-plugin-slug="tec-seating"
			data-action="tec"
		>
			<fieldset class="stellarwp-uplink__settings-group">
				<input type=\'hidden\' name=\'option_page\' value=\'stellarwp_uplink_group_tec-seating\' /><input type="hidden" name="action" value="update" /><input type="hidden" id="_wpnonce" name="_wpnonce" value="12345678" /><input type="hidden" name="_wp_http_referer" value="" />
				
				<input
					type="text"
					name="pue_install_key_tec_seating"
					value=""
					placeholder="License key"
					class="regular-text stellarwp-uplink__settings-field"
				/>
									
<div class="uplink-authorize-container">
	<a href="http://wordpress.test/wp-admin/network/"
	   target="_self"
	   class="button uplink-authorize not-authorized"	   data-plugin-slug="tec-seating"
	>
		Contact your network administrator to connect	</a>
</div>

								<p class="tooltip description">
	A valid license key is required for support and updates</p>
<div class="license-test-results">
	<img src="http://wordpress.test/wp-admin/images/wpspin_light.gif" class="ajax-loading-license" alt="Loading" style="display: none"/>
	<div class="key-validity"></div>
</div>
			</fieldset>
			<input type="hidden" class="wp-nonce-fluent" name="stellarwp-uplink-license-key-nonce__tec-seating" value="12345678" />		</div>
	</div>
',
  ),
);
