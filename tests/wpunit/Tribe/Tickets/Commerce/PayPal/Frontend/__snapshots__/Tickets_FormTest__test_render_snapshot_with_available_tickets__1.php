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
				<td class="tribe-ticket quantity" data-product-id="4">
					<input type="hidden" name="product_id[]" value="4">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_4"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="4">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 3				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 3				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="5">
					<input type="hidden" name="product_id[]" value="5">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_5"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="5">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 3				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 3				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="6">
					<input type="hidden" name="product_id[]" value="6">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_6"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="6">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 3				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 3				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
			
					<tr>
				<td colspan="5" class="tpp-add">
																
	<a href="http://commerce.dev/wp-login.php?redirect_to=http://commerce.dev/?p=3">Log in before purchasing</a>

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
