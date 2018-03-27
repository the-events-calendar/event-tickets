<?php


class Tribe__Tickets__REST__V1__Auth {
	public function can_post_event() {
		$post_type = get_post_type_object( Tribe__Tickets__Main::POSTTYPE );

		return current_user_can( $post_type->cap->create_posts );
	}
}