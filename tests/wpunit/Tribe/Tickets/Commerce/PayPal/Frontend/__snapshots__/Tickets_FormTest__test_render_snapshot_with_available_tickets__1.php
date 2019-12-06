<?php return '<form
	id="tpp-buy-tickets"
	action=""
	class="tribe-tickets-tpp cart "
	method="post"
	enctype=\'multipart/form-data\'
>
	<input type="hidden" name="provider" value="Tribe__Tickets__Commerce__PayPal__Main">
	<input type="hidden" name="add" value="1">
	<h2 class="tribe-events-tickets-title tribe--tpp">
		Tickets	</h2>

	<div class="tribe-tpp-messages">

		<div
			class="tribe-tpp-message tribe-tpp-message-error tribe-tpp-message-confirmation-error" style="display:none;">
			Please fill in the ticket confirmation name and email fields.		</div>
	</div>

	<table class="tribe-events-tickets tribe-events-tickets-tpp">
					<tr>
				<td class="tribe-ticket quantity" data-product-id="142">
					<input type="hidden" name="product_id[]" value="142">
											<input
							type="number"
							class="tribe-tickets-quantity qty"
							min="0"
							max="100"							name="quantity_142"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="142">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 141				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 141				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="143">
					<input type="hidden" name="product_id[]" value="143">
											<input
							type="number"
							class="tribe-tickets-quantity qty"
							min="0"
							max="100"							name="quantity_143"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="143">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 141				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 141				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="144">
					<input type="hidden" name="product_id[]" value="144">
											<input
							type="number"
							class="tribe-tickets-quantity qty"
							min="0"
							max="100"							name="quantity_144"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="144">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 141				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 141				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>

					<tr>
				<td colspan="5" class="tpp-add">

	<a href="http://test.locahost/wp-login.php?redirect_to=http://test.locahost/?p=141">Log in before purchasing</a>

										</td>
			</tr>

		<noscript>
			<tr>
				<td class="tribe-link-tickets-message">
					<div class="no-javascript-msg">You must have JavaScript activated to purchase tickets. Please enable JavaScript in your browser.</div>
				</td>
			</tr>
		</noscript>
	</table>
</form>
';
