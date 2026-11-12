<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Generator;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use Tribe__Events__Main as TEC;

/**
 * The help text of an Event in a Series renders even when its translation breaks the placeholders.
 */
class LabelsTest extends WPTestCase {
	private const SOURCE = 'A %1$s is specific to this %2$s. You can add a %3$s from the %%s Series page.';

	public function malformed_translations_data_provider(): Generator {
		yield 'es_ES escape without the s' => [
			'Un %1$s es específico para este %2$s. Puedes añadir un %3$s desde la página de la serie %%.',
		];
		yield 'fr_FR escape without the s' => [
			'Un seul %1$s est spécifique à ce %2$s. Vous pouvez ajouter un %3$s à partir de la page Série %%.',
		];
		yield 'lo fourth placeholder' => [
			'%1$s ແມ່ນໃຊ້ສະເພາະສຳລັບ %2$s ນີ້. ທ່ານສາມາດເພີ່ມ %3$s ໄດ້ຈາກໜ້າຊີຣີ %4$s.',
		];
	}

	/**
	 * @test
	 * @dataProvider malformed_translations_data_provider
	 */
	public function it_should_fall_back_to_the_english_template_when_the_translation_is_malformed( string $translation ): void {
		$this->translate_to( $translation );

		$template = ( new Labels() )->get_default_ticket_type_event_in_series_template();

		$this->assertSame( $this->format_source( self::SOURCE ), $template );
	}

	/**
	 * @test
	 * @dataProvider malformed_translations_data_provider
	 */
	public function it_should_link_the_series_when_the_translation_is_malformed( string $translation ): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$series_title = Factory::create()->sentence();
		$series_id    = static::factory()->post->create(
			[
				'post_type'  => Series_Post_Type::POSTTYPE,
				'post_title' => $series_title,
			]
		);
		$event_id     = static::factory()->post->create( [ 'post_type' => TEC::POSTTYPE ] );
		$this->translate_to( $translation );

		$description = ( new Labels() )->get_default_ticket_type_event_in_series_description( $series_id, $event_id );

		$series_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			get_edit_post_link( $series_id, 'admin' ) . '#tribetickets',
			$series_title
		);
		// The description goes through wp_kses(), which encodes the ampersands of the edit link.
		$this->assertSame(
			sprintf( $this->format_source( self::SOURCE ), $series_link ),
			html_entity_decode( $description )
		);
	}

	/**
	 * @test
	 */
	public function it_should_keep_a_well_formed_translation(): void {
		$translation = 'Een %1$s is specifiek voor deze %2$s. Je kunt een %3$s toevoegen vanaf de %%s seriepagina.';
		$this->translate_to( $translation );

		$template = ( new Labels() )->get_default_ticket_type_event_in_series_template();

		$this->assertSame( $this->format_source( $translation ), $template );
	}

	private function translate_to( string $translation ): void {
		add_filter(
			'gettext_with_context',
			static fn( $text, $original ) => self::SOURCE === $original ? $translation : $text,
			10,
			2
		);
	}

	private function format_source( string $source ): string {
		return sprintf(
			$source,
			tec_tickets_get_default_ticket_type_label_lowercase( 'ticket_type_default_header_description' ),
			tribe_get_event_label_singular_lowercase(),
			tec_tickets_get_series_pass_singular_uppercase( 'ticket_type_default_header_description' )
		);
	}
}
