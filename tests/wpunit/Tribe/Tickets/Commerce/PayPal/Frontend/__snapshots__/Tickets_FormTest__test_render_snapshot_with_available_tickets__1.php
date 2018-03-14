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
				<td class="tribe-ticket quantity" data-product-id="9">
					<input type="hidden" name="product_id[]" value="9">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_9"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="9">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 8				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 8				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="10">
					<input type="hidden" name="product_id[]" value="10">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_10"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="10">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 8				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 8				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
						<tr>
				<td class="tribe-ticket quantity" data-product-id="11">
					<input type="hidden" name="product_id[]" value="11">
											<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							max="100"							name="quantity_11"
							value="0"
													>
													<span class="tribe-tickets-remaining">
							<span class="available-stock" data-product-id="11">100</span> available							</span>
															</td>
				<td class="tickets_name">
					Test Ticket for 8				</td>
				<td class="tickets_price">
					<span class="tribe-tickets-price-amount amount">&#x24;1.00</span>				</td>
				<td class="tickets_description" colspan="2">
					Ticket for 8				</td>
				<td class="tickets_submit">
											<button type="submit" class="tpp-submit tribe-button">Buy now</button>
									</td>
			</tr>
			
					<tr>
				<td colspan="5" class="tpp-add">
																
	<a href="http://commerce.dev/wp-login.php?redirect_to=http://test.tri.be">Log in before purchasing</a>

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
