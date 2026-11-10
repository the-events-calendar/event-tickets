<?php

namespace Tribe\Tickets\Test\REST\V1;

use Codeception\Example;
use Faker\Factory;
use Faker\Generator;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class AttendeeArchivePrivateFiltersCest extends BaseRestCest {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * @var Generator
	 */
	protected $faker;

	public function _before( Restv1Tester $I ) {
		parent::_before( $I );

		$this->faker = Factory::create();
	}

	/**
	 * It should not let a visitor narrow the attendee list by a field the visitor cannot read.
	 *
	 * @test
	 * @dataProvider private_filters
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::process_search
	 */
	public function should_ignore_private_filters_for_visitor( Restv1Tester $I, Example $example ) {
		$seed     = $this->seed_attendees( $I );
		$expected = [ $seed['checked_in'], $seed['not_checked_in'] ];

		$I->sendGET(
			$this->attendees_url,
			array_merge( [ 'include_post' => $seed['post'] ], $this->filter_params( $example['filter'], $seed ) )
		);

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'total' => count( $expected ) ] );
		$ids = $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' );
		sort( $ids );
		sort( $expected );
		$I->assertEquals( $expected, $ids );
	}

	/**
	 * It should keep the check-in filter for a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 */
	public function should_filter_by_check_in_with_api_key( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post' => $seed['post'],
				'checkedin'    => 1,
				'api_key'      => $this->set_api_key(),
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals( [ $seed['checked_in'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should keep the email search for a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::process_search
	 */
	public function should_search_by_email_with_api_key( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post' => $seed['post'],
				'email'        => $seed['email_token'],
				'api_key'      => $this->set_api_key(),
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals( [ $seed['checked_in'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should keep the attendee information filter for a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 */
	public function should_filter_by_attendee_information_with_api_key( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post'                   => $seed['post'],
				'attendee_information_available' => 'true',
				'api_key'                        => $this->set_api_key(),
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals( [ $seed['checked_in'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should keep the title search for a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 */
	public function should_search_by_title_with_api_key( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post' => $seed['post'],
				'search'       => $seed['order_token'],
				'api_key'      => $this->set_api_key(),
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals( [ $seed['checked_in'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * It should not let a visitor order the attendee list by the slug, which the visitor cannot read.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 */
	public function should_ignore_slug_order_for_visitor( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post' => $seed['post'],
				'orderby'      => 'slug',
				'order'        => 'ASC',
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals(
			[ $seed['checked_in'], $seed['not_checked_in'] ],
			$I->grabDataFromResponseByJsonPath( '$.attendees[*].id' )
		);
	}

	/**
	 * It should keep the slug order for a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::get
	 */
	public function should_order_by_slug_with_api_key( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET(
			$this->attendees_url,
			[
				'include_post' => $seed['post'],
				'orderby'      => 'slug',
				'order'        => 'ASC',
				'api_key'      => $this->set_api_key(),
			]
		);

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals(
			[ $seed['not_checked_in'], $seed['checked_in'] ],
			$I->grabDataFromResponseByJsonPath( '$.attendees[*].id' )
		);
	}

	/**
	 * It should keep the name search for a visitor, since names are shown to visitors.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive::process_search
	 */
	public function should_search_by_name_for_visitor( Restv1Tester $I ) {
		$seed = $this->seed_attendees( $I );

		$I->sendGET( $this->attendees_url, [ 'include_post' => $seed['post'], 'name' => $seed['name_token'] ] );

		$I->seeResponseCodeIs( 200 );
		$I->assertEquals( [ $seed['checked_in'] ], $I->grabDataFromResponseByJsonPath( '$.attendees[*].id' ) );
	}

	/**
	 * Each filter, applied, would leave out at least one of the two seeded attendees.
	 *
	 * @return array<string,array{filter:string}>
	 */
	protected function private_filters(): array {
		return [
			'checked in'         => [ 'filter' => 'checkedin' ],
			'not checked in'     => [ 'filter' => 'not_checkedin' ],
			'order ID'           => [ 'filter' => 'order_id' ],
			'order status'       => [ 'filter' => 'order_status' ],
			'minimum price'      => [ 'filter' => 'price_min' ],
			'maximum price'      => [ 'filter' => 'price_max' ],
			'exact email'        => [ 'filter' => 'email' ],
			'non-matching email' => [ 'filter' => 'wrong_email' ],
			'has answers'        => [ 'filter' => 'answered' ],
			'has no answers'     => [ 'filter' => 'unanswered' ],
			'title search'       => [ 'filter' => 'search' ],
		];
	}

	/**
	 * @param string                                                                                                                           $filter The filter key from the data provider.
	 * @param array{post:int,checked_in:int,not_checked_in:int,email:string,email_token:string,name_token:string,order_token:string,price:int} $seed   The seeded data.
	 *
	 * @return array<string,int|string> The request parameters for the filter.
	 */
	private function filter_params( string $filter, array $seed ): array {
		$params = [
			'checkedin'     => [ 'checkedin' => 1 ],
			'not_checkedin' => [ 'checkedin' => 0 ],
			/* An RSVP attendee is its own order. */
			'order_id'      => [ 'order_id' => $seed['checked_in'] ],
			/* RSVP attendees store the going status, so a Tribe Commerce status matches none of them. */
			'order_status'  => [ 'order_status' => 'completed' ],
			'price_min'     => [ 'price_min' => $seed['price'] ],
			'price_max'     => [ 'price_max' => $seed['price'] - 1 ],
			'email'         => [ 'email' => $seed['email'] ],
			'wrong_email'   => [ 'email' => $this->faker->uuid() . '@example.org' ],
			'answered'      => [ 'attendee_information_available' => 'true' ],
			'unanswered'    => [ 'attendee_information_available' => 'false' ],
			'search'        => [ 'search' => $seed['order_token'] ],
		];

		return $params[ $filter ];
	}

	/**
	 * Seeds a public post showing its attendee list, with two going RSVP attendees: one checked in,
	 * with a paid price, a known email, name and title and an answer to an attendee question, and one
	 * not checked in, who paid nothing and answered nothing. The attendee created last has the slug
	 * that sorts first.
	 *
	 * @param Restv1Tester $I The REST tester.
	 *
	 * @return array{post:int,checked_in:int,not_checked_in:int,email:string,email_token:string,name_token:string,order_token:string,price:int}
	 */
	private function seed_attendees( Restv1Tester $I ): array {
		/* Despite its name, a truthy `_tribe_hide_attendees_list` means the attendee list is shown. */
		$post_id   = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		/* Random tokens keep each search matching exactly one of the two attendees. */
		$email_token = $this->faker->uuid();
		$name_token  = $this->faker->lexify( '????????????' );
		$full_name   = $this->faker->firstName() . ' ' . $name_token;
		$other_name  = $this->faker->name();

		/* Some providers start the attendee title, and so its slug, with the order number. */
		$order_tokens = [
			$this->faker->unique()->numerify( '############' ),
			$this->faker->unique()->numerify( '############' ),
		];
		rsort( $order_tokens, SORT_STRING );
		[ $order_token, $other_order_token ] = $order_tokens;
		$price       = $this->faker->numberBetween( 5, 50 );

		$checked_in = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status'         => 'yes',
			'optout'              => false,
			'checkin'             => true,
			'email'               => $email_token . '@example.org',
			'full_name'           => $full_name,
			'post_title'          => $order_token . ' | ' . $full_name . ' | 1',
			'_paid_price'         => $price,
			'_tribe_tickets_meta' => [ $this->faker->slug( 2 ) => $this->faker->sentence() ],
		] );

		$not_checked_in = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
			'checkin'     => false,
			'email'       => $this->faker->uuid() . '@example.org',
			'full_name'   => $other_name,
			'post_title'  => $other_order_token . ' | ' . $other_name . ' | 1',
			'_paid_price' => 0,
		] );

		return [
			'post'           => $post_id,
			'checked_in'     => $checked_in,
			'not_checked_in' => $not_checked_in,
			'email'          => $email_token . '@example.org',
			'email_token'    => $email_token,
			'name_token'     => $name_token,
			'order_token'    => $order_token,
			'price'          => $price,
		];
	}

	/**
	 * @return string The QR API key now stored in the settings.
	 */
	private function set_api_key(): string {
		$api_key = $this->faker->sha1();
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		return $api_key;
	}
}
