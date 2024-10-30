<?php
/**
 * Event Tickets Emails: Main template > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/footer/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe__Template $this  Current template object.
 * @var WP_Post|null    $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

// If viewing preview, bail.
if ( ! empty( $preview ) && tribe_is_truthy( $preview ) ) {
	return;
}

?>
					</table>
				</td>
			</tr>
			<?php $this->template( 'template-parts/body/footer' ); ?>
		</table>
	</body>
</html>
