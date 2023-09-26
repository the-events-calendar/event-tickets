<?php

namespace TEC\Tickets\QR;

/**
 * Class Facade
 *
 * @since   TBD
 *
 * @package TEC\Tickets\QR
 */
class Facade {
	/**
	 * The level of the QR code.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $level = QR_ECLEVEL_L;

	/**
	 * The size of the QR code.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $size = 3;

	/**
	 * The margin of the QR code.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $margin = 4;

	/**
	 * Change the level of the QR code.
	 *
	 * @since TBD
	 *
	 * @param int $value
	 *
	 * @return $this
	 */
	public function level( int $value ): self {
		$this->level = $value;
		return $this;
	}

	/**
	 * Change the size of the QR code image.
	 *
	 * @since TBD
	 *
	 * @param int $value
	 *
	 * @return $this
	 */
	public function size( int $value ): self {
		$this->size = $value;
		return $this;
	}

	/**
	 * Change the margin of the QR code image.
	 *
	 * @since TBD
	 *
	 * @param int $value
	 *
	 * @return $this
	 */
	public function margin( int $value ): self {
		$this->margin = $value;
		return $this;
	}

	/**
	 * Get the level of the QR code.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_level(): int {
		return $this->level;
	}

	/**
	 * Get the size of the QR code.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_size(): int {
		return $this->size;
	}

	/**
	 * Get the margin of the QR code.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_margin(): int {
		return $this->margin;
	}

	/**
	 * Get the QR code as a string.
	 *
	 * @since TBD
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public function get_png_as_string( string $data ): string {
		ob_start();
		\QRcode::png( $data, false, $this->get_level(), $this->get_size(), $this->get_margin() );
		$png_string = ob_get_clean();
		return $png_string;
	}

	/**
	 * Get the QR code as a PNG base64 image, helpful to use when uploading the file would create duplicates.
	 *
	 * @since TBD
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public function get_png_as_base64( string $data ): string {
		$src = base64_encode( $this->get_png_as_string( $data ) );
		return "data:image/png;base64," . $src;
	}

	/**
	 * Get the QR code as a file uploaded to WordPress.
	 *
	 * @since TBD
	 *
	 * @param string $data
	 * @param string $name
	 * @param string $folder
	 *
	 * @return array
	 */
	public function get_png_as_file( string $data, string $name, string $folder = 'tec-tickets-qr' ): array {
		$folder = '/' . ltrim( $folder, '/' );
		$png_as_string = $this->get_png_as_string( $data );

		// Filters the upload directory but still use `wp_upload_bits` to create the file.
		$upload_bits_filter = static function( $arr ) use ( $folder ) {
	        $arr['url'] = str_replace( $arr['subdir'], $folder, $arr['url'] );
	        $arr['path'] = str_replace( $arr['subdir'], $folder, $arr['path'] );
			$arr['subdir'] = $folder;
		    return $arr;
		};

		add_filter( 'upload_dir', $upload_bits_filter );

		$filename = sanitize_file_name( $name ) . '.png';
		$file_upload = wp_upload_bits( $filename, null, $png_as_string );

		remove_filter( 'upload_dir', $upload_bits_filter );

		return $file_upload;
	}
}