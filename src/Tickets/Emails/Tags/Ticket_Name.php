<?php

namespace TEC\Tickets\Emails\Tags;

class Ticket_Name extends \StellarWP\Pigeon\Tags\Tag {

	public $tag_name = 'tec_ticket_name';

	public function register( $tags ) {
		$tags[] = $this;
		return $tags;
	}

	public function render() :string {
		return 'string';
	}
}