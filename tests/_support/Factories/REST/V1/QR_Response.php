<?php

namespace Tribe\Tickets\Test\Factories\REST\V1;

use Tribe\Tickets\Test\Factories\QR;

class QR_Response extends QR {

	public function create( $args = [], $generation_definitions = null ) {
		return $this->create_and_get( $args, $generation_definitions );
	}

	public function create_and_get( $args = [], $generation_definitions = null ) {
		$repository = new \Tribe__Tickets__REST__V1__Post_Repository( new \Tribe__Tickets__REST__V1__Messages() );

		$data = $repository->get_qr_data( parent::create( $args, $generation_definitions ) );

		return $data;
	}
}
