<?php
/**
 * Event Tickets Emails: Main template > Footer.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/footer/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

// If viewing preview, bail.
if ( ! empty( $preview ) ) {
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
