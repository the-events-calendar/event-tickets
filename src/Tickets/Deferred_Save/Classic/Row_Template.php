<?php
/**
 * The hidden templates the classic editor builds staged rows from.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

/**
 * Class Row_Template.
 *
 * Renders the `<template>` elements the staging module clones for a ticket that is not saved yet:
 * a list row, a table for a post that has no tickets yet, and the marker put on a saved ticket's
 * row when it has staged changes. The module fills the slots through DOM text nodes, so the
 * templates carry no placeholders that could be rendered as markup.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
class Row_Template {
	/**
	 * Renders the templates.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post the metabox is for.
	 *
	 * @return string The HTML.
	 */
	public function render( int $post_id ): string {
		return (string) tribe( 'tickets.admin.views' )->template(
			'deferred-save/row',
			[
				'post_id'       => $post_id,
				'not_saved_yet' => __( 'Not saved yet', 'event-tickets' ),
				'will_delete'   => __( 'Will be deleted on save', 'event-tickets' ),
				'will_move'     => __( 'Moves on save', 'event-tickets' ),
			],
			false
		);
	}
}
