<?php
/**
 * Block: RSVP attendee registration
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-kitchen-sink/ari.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 *
 * @version TBD
 */
?>
<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="51">




<div class="tribe-tickets__rsvp-ar tribe-common-g-row tribe-common-g-row--gutters">

<div class="tribe-tickets__rsvp-ar-sidebar-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-ar-sidebar">

<h3 class="tribe-common-h5">
Attendee Registration</h3>

<div class="tribe-tickets__rsvp-ar-quantity">
<span class="tribe-common-h7">
	Total Guests	</span>

<div class="tribe-tickets__rsvp-ar-quantity-input">
	<button type="button" class="tribe-tickets__rsvp-ar-quantity-input-number tribe-tickets__rsvp-ar-quantity-input-number--minus">
<span class="tribe-common-a11y-hidden">Minus</span>
</button>

	<input type="number" name="quantity_51" class="tribe-common-h4" step="1" min="1" value="1" required="" max="40">

	<button type="button" class="tribe-tickets__rsvp-ar-quantity-input-number tribe-tickets__rsvp-ar-quantity-input-number--plus">
<span class="tribe-common-a11y-hidden">Plus</span>
</button>
</div>

</div>

<ul class="tribe-tickets__rsvp-ar-guest-list tribe-common-h6">
<li class="tribe-tickets__rsvp-ar-guest-list-item">
	<button>
		<svg class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs></defs><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"></path></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Main Guest			</span>
	</button>
</li>
<li class="tribe-tickets__rsvp-ar-guest-list-item">
	<button>
		<svg class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon--disabled" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs></defs><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"></path></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Guest 2			</span>
	</button>
</li>
<li class="tribe-tickets__rsvp-ar-guest-list-item">
	<button>
		<svg class="tribe-tickets-svgicon tribe-tickets__rsvp-ar-guest-icon--disabled" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 11 14"><defs></defs><path fill="#141827" stroke="#141827" stroke-width="1.1" d="M8.24995 3.575c0 1.32005-1.18823 2.475-2.75 2.475s-2.75-1.15495-2.75-2.475v-.55c0-1.32005 1.18823-2.475 2.75-2.475s2.75 1.15495 2.75 2.475v.55zM.55 11.5868c0-2.12633 1.7237-3.85003 3.85-3.85003h2.2c2.1263 0 3.85 1.7237 3.85 3.85003v1.7435H.55v-1.7435z"></path></svg>			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
			Guest 3			</span>
	</button>
</li>
</ul>

</div>

</div>

<div class="tribe-tickets__rsvp-ar-form-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-ar-form">

<h3 class="tribe-tickets__rsvp-ar-form-title tribe-common-h5">
Main Guest</h3>

<div class="tribe-tickets__form">
				<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_textfield{{data.attendee_id}}">Textfield<span class="screen-reader-text">(required)</span><span class="tribe-required" aria-hidden="true" role="presentation">*</span></label>
		<input type="text" id="tribe-tickets-meta_51_textfield{{data.attendee_id}}" class="tribe-common-form-control-text__input tribe-tickets__form-field-input" name="tribe-tickets-meta[51][{{data.attendee_id}}][textfield]" value="" required="" aria-required="true">
</div>
				<div class="tribe-common-b1 tribe-tickets__form-field">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_textarea{{data.attendee_id}}">Textarea</label>
		<textarea id="tribe-tickets-meta_51_textarea{{data.attendee_id}}" class="tribe-common-form-control-text__input tribe-tickets__form-field-input" name="tribe-tickets-meta[51][{{data.attendee_id}}][textarea]"></textarea>
</div>
				<div class="tribe-tickets__form-field tribe-tickets__form-field--required">
<header class="tribe-tickets__form-field-label">
	<h3 class="tribe-common-b1 tribe-common-b2--min-medium">
		Radio<span class="screen-reader-text">(required)</span><span class="tribe-required" aria-hidden="true" role="presentation">*</span>		</h3>
</header>

<div class="tribe-common-form-control-checkbox-radio-group">

	<div class="tribe-common-form-control-radio">
		<label class="tribe-common-form-control-radio__label" for="tribe-tickets-meta_51_radio{{data.attendee_id}}_f97c5d29941bfb1b2fdab0874906ab82">
			<input class="tribe-common-form-control-radio__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_radio{{data.attendee_id}}_f97c5d29941bfb1b2fdab0874906ab82" name="tribe-tickets-meta[51][{{data.attendee_id}}][radio]" type="radio" value="One" required="" aria-required="true">
			One			</label>
	</div>

	<div class="tribe-common-form-control-radio">
		<label class="tribe-common-form-control-radio__label" for="tribe-tickets-meta_51_radio{{data.attendee_id}}_b8a9f715dbb64fd5c56e7783c6820a61">
			<input class="tribe-common-form-control-radio__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_radio{{data.attendee_id}}_b8a9f715dbb64fd5c56e7783c6820a61" name="tribe-tickets-meta[51][{{data.attendee_id}}][radio]" type="radio" value="Two" required="" aria-required="true">
			Two			</label>
	</div>

	<div class="tribe-common-form-control-radio">
		<label class="tribe-common-form-control-radio__label" for="tribe-tickets-meta_51_radio{{data.attendee_id}}_35d6d33467aae9a2e3dccb4b6b027878">
			<input class="tribe-common-form-control-radio__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_radio{{data.attendee_id}}_35d6d33467aae9a2e3dccb4b6b027878" name="tribe-tickets-meta[51][{{data.attendee_id}}][radio]" type="radio" value="Three" required="" aria-required="true">
			Three			</label>
	</div>

	<div class="tribe-common-form-control-radio">
		<label class="tribe-common-form-control-radio__label" for="tribe-tickets-meta_51_radio{{data.attendee_id}}_8cbad96aced40b3838dd9f07f6ef5772">
			<input class="tribe-common-form-control-radio__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_radio{{data.attendee_id}}_8cbad96aced40b3838dd9f07f6ef5772" name="tribe-tickets-meta[51][{{data.attendee_id}}][radio]" type="radio" value="Four" required="" aria-required="true">
			Four			</label>
	</div>

	<div class="tribe-common-form-control-radio">
		<label class="tribe-common-form-control-radio__label" for="tribe-tickets-meta_51_radio{{data.attendee_id}}_30056e1cab7a61d256fc8edd970d14f5">
			<input class="tribe-common-form-control-radio__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_radio{{data.attendee_id}}_30056e1cab7a61d256fc8edd970d14f5" name="tribe-tickets-meta[51][{{data.attendee_id}}][radio]" type="radio" value="Five" required="" aria-required="true">
			Five			</label>
	</div>
		</div>
</div>
				<div class="tribe-tickets__form-field">
<header class="tribe-tickets__form-field-label">
	<h3 class="tribe-common-b1 tribe-common-b2--min-medium">
		Checkbox		</h3>
</header>

<div class="tribe-common-form-control-checkbox-radio-group">

	<div class="tribe-common-form-control-checkbox">
		<label class="tribe-common-form-control-checkbox__label" for="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_f97c5d29941bfb1b2fdab0874906ab82">
			<input class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_f97c5d29941bfb1b2fdab0874906ab82" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox]" type="checkbox" value="One">
			One			</label>
	</div>

	<div class="tribe-common-form-control-checkbox">
		<label class="tribe-common-form-control-checkbox__label" for="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_b8a9f715dbb64fd5c56e7783c6820a61">
			<input class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_b8a9f715dbb64fd5c56e7783c6820a61" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox]" type="checkbox" value="Two">
			Two			</label>
	</div>

	<div class="tribe-common-form-control-checkbox">
		<label class="tribe-common-form-control-checkbox__label" for="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_35d6d33467aae9a2e3dccb4b6b027878">
			<input class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_35d6d33467aae9a2e3dccb4b6b027878" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox]" type="checkbox" value="Three">
			Three			</label>
	</div>

	<div class="tribe-common-form-control-checkbox">
		<label class="tribe-common-form-control-checkbox__label" for="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_8cbad96aced40b3838dd9f07f6ef5772">
			<input class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_8cbad96aced40b3838dd9f07f6ef5772" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox]" type="checkbox" value="Four">
			Four			</label>
	</div>

	<div class="tribe-common-form-control-checkbox">
		<label class="tribe-common-form-control-checkbox__label" for="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_30056e1cab7a61d256fc8edd970d14f5">
			<input class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_checkbox{{data.attendee_id}}_30056e1cab7a61d256fc8edd970d14f5" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox]" type="checkbox" value="Five">
			Five			</label>
	</div>
		</div>
<input type="hidden" name="tribe-tickets-meta[51][{{data.attendee_id}}][checkbox][0]" value="">
</div>
				<div class="tribe-tickets__form-field">
<label class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_dropdown{{data.attendee_id}}">Dropdown	</label>
<select id="tribe-tickets-meta_51_dropdown{{data.attendee_id}}" class="tribe-common-form-control-select__input tribe-tickets__form-field-input tribe-common-b2" name="tribe-tickets-meta[51][{{data.attendee_id}}][dropdown]">
	<option value="">Select an option</option>
				<option value="One">One</option>
				<option value="Two">Two</option>
				<option value="Three">Three</option>
				<option value="Four">Four</option>
				<option value="Five">Five</option>
		</select>
</div>
				<div class="tribe-common-b1 tribe-tickets__form-field">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_email{{data.attendee_id}}">Email</label>
<input type="email" id="tribe-tickets-meta_51_email{{data.attendee_id}}" class="tribe-common-form-control-email__input tribe-tickets__form-field-input" name="tribe-tickets-meta[51][{{data.attendee_id}}][email]" value="">
</div>
				<div class="tribe-common-b1 tribe-tickets__form-field">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_telephone{{data.attendee_id}}">Telephone</label>
<input type="tel" id="tribe-tickets-meta_51_telephone{{data.attendee_id}}" class="tribe-common-form-control-text__input tribe-tickets__form-field-input" name="tribe-tickets-meta[51][{{data.attendee_id}}][telephone]" value="">
</div>
				<div class="tribe-common-b1 tribe-tickets__form-field">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_website{{data.attendee_id}}">Website</label>
<input type="url" class="tribe-common-form-control-url__input tribe-tickets__form-field-input" id="tribe-tickets-meta_51_website{{data.attendee_id}}" name="tribe-tickets-meta[51][{{data.attendee_id}}][website]" value="">
</div>
				<div class="tribe_horizontal_datepicker__container">
<div class="tribe-common-b1 tribe-tickets__form-field">
	<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_birth-date{{data.attendee_id}}">Birth date</label>

	<!-- Month -->
	<div class="tribe_horizontal_datepicker">
		<select class="tribe_horizontal_datepicker__month">
			<option value="" disabled="" selected="">Month</option>
								<option value="01">Jan</option>
								<option value="02">Feb</option>
								<option value="03">Mar</option>
								<option value="04">Apr</option>
								<option value="05">May</option>
								<option value="06">Jun</option>
								<option value="07">Jul</option>
								<option value="08">Aug</option>
								<option value="09">Sep</option>
								<option value="10">Oct</option>
								<option value="11">Nov</option>
								<option value="12">Dec</option>
						</select>
	</div>
	<!-- Day -->
	<div class="tribe_horizontal_datepicker">
		<select class="tribe_horizontal_datepicker__day">
			<option value="" disabled="" selected="">Day</option>
								<option value="01">01</option>
								<option value="02">02</option>
								<option value="03">03</option>
								<option value="04">04</option>
								<option value="05">05</option>
								<option value="06">06</option>
								<option value="07">07</option>
								<option value="08">08</option>
								<option value="09">09</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
								<option value="13">13</option>
								<option value="14">14</option>
								<option value="15">15</option>
								<option value="16">16</option>
								<option value="17">17</option>
								<option value="18">18</option>
								<option value="19">19</option>
								<option value="20">20</option>
								<option value="21">21</option>
								<option value="22">22</option>
								<option value="23">23</option>
								<option value="24">24</option>
								<option value="25">25</option>
								<option value="26">26</option>
								<option value="27">27</option>
								<option value="28">28</option>
								<option value="29">29</option>
								<option value="30">30</option>
								<option value="31">31</option>
						</select>
	</div>
	<!-- Year -->
	<div class="tribe_horizontal_datepicker">
		<select class="tribe_horizontal_datepicker__year">
			<option value="" disabled="" selected="">Year</option>
								<option value="2020">2020</option>
								<option value="2019">2019</option>
								<option value="2018">2018</option>
								<option value="2017">2017</option>
								<option value="2016">2016</option>
								<option value="2015">2015</option>
								<option value="2014">2014</option>
								<option value="2013">2013</option>
								<option value="2012">2012</option>
								<option value="2011">2011</option>
								<option value="2010">2010</option>
								<option value="2009">2009</option>
								<option value="2008">2008</option>
								<option value="2007">2007</option>
								<option value="2006">2006</option>
								<option value="2005">2005</option>
								<option value="2004">2004</option>
								<option value="2003">2003</option>
								<option value="2002">2002</option>
								<option value="2001">2001</option>
								<option value="2000">2000</option>
								<option value="1999">1999</option>
								<option value="1998">1998</option>
								<option value="1997">1997</option>
								<option value="1996">1996</option>
								<option value="1995">1995</option>
								<option value="1994">1994</option>
								<option value="1993">1993</option>
								<option value="1992">1992</option>
								<option value="1991">1991</option>
								<option value="1990">1990</option>
								<option value="1989">1989</option>
								<option value="1988">1988</option>
								<option value="1987">1987</option>
								<option value="1986">1986</option>
								<option value="1985">1985</option>
								<option value="1984">1984</option>
								<option value="1983">1983</option>
								<option value="1982">1982</option>
								<option value="1981">1981</option>
								<option value="1980">1980</option>
								<option value="1979">1979</option>
								<option value="1978">1978</option>
								<option value="1977">1977</option>
								<option value="1976">1976</option>
								<option value="1975">1975</option>
								<option value="1974">1974</option>
								<option value="1973">1973</option>
								<option value="1972">1972</option>
								<option value="1971">1971</option>
								<option value="1970">1970</option>
								<option value="1969">1969</option>
								<option value="1968">1968</option>
								<option value="1967">1967</option>
								<option value="1966">1966</option>
								<option value="1965">1965</option>
								<option value="1964">1964</option>
								<option value="1963">1963</option>
								<option value="1962">1962</option>
								<option value="1961">1961</option>
								<option value="1960">1960</option>
								<option value="1959">1959</option>
								<option value="1958">1958</option>
								<option value="1957">1957</option>
								<option value="1956">1956</option>
								<option value="1955">1955</option>
								<option value="1954">1954</option>
								<option value="1953">1953</option>
								<option value="1952">1952</option>
								<option value="1951">1951</option>
								<option value="1950">1950</option>
								<option value="1949">1949</option>
								<option value="1948">1948</option>
								<option value="1947">1947</option>
								<option value="1946">1946</option>
								<option value="1945">1945</option>
								<option value="1944">1944</option>
								<option value="1943">1943</option>
								<option value="1942">1942</option>
								<option value="1941">1941</option>
								<option value="1940">1940</option>
								<option value="1939">1939</option>
								<option value="1938">1938</option>
								<option value="1937">1937</option>
								<option value="1936">1936</option>
								<option value="1935">1935</option>
								<option value="1934">1934</option>
								<option value="1933">1933</option>
								<option value="1932">1932</option>
								<option value="1931">1931</option>
								<option value="1930">1930</option>
								<option value="1929">1929</option>
								<option value="1928">1928</option>
								<option value="1927">1927</option>
								<option value="1926">1926</option>
								<option value="1925">1925</option>
								<option value="1924">1924</option>
								<option value="1923">1923</option>
								<option value="1922">1922</option>
								<option value="1921">1921</option>
								<option value="1920">1920</option>
								<option value="1919">1919</option>
								<option value="1918">1918</option>
								<option value="1917">1917</option>
								<option value="1916">1916</option>
								<option value="1915">1915</option>
								<option value="1914">1914</option>
								<option value="1913">1913</option>
								<option value="1912">1912</option>
								<option value="1911">1911</option>
								<option value="1910">1910</option>
								<option value="1909">1909</option>
								<option value="1908">1908</option>
								<option value="1907">1907</option>
								<option value="1906">1906</option>
								<option value="1905">1905</option>
								<option value="1904">1904</option>
								<option value="1903">1903</option>
								<option value="1902">1902</option>
								<option value="1901">1901</option>
								<option value="1900">1900</option>
						</select>
	</div>
</div>
<div>
	<input type="hidden" class="tribe-tickets__form-field-input tribe_horizontal_datepicker__value" name="tribe-tickets-meta[51][{{data.attendee_id}}][birth-date]" value="">
</div>
</div>
				<div class="tribe-common-b1 tribe-tickets__form-field">
<label class="tribe-common-b2--min-medium tribe-tickets__form-field-label" for="tribe-tickets-meta_51_date{{data.attendee_id}}">Date</label>
<input type="date" id="tribe-tickets-meta_51_date{{data.attendee_id}}" class="tribe-common-form-control-datetime__input tribe-tickets__form-field-input" name="tribe-tickets-meta[51][{{data.attendee_id}}][date]" value="" min="1900-01-01" max="2120-12-31">
</div>
		</div>

<div class="tribe-tickets__rsvp-form-buttons">
<button class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel" type="reset">
	Cancel	</button>

<button class="tribe-common-c-btn tribe-tickets__rsvp-form-button" type="submit">
	Finish	</button>
</div>

</div>
</div>

</div>


		</div>