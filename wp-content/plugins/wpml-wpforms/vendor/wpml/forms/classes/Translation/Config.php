<?php

namespace WPML\Forms\Translation;

use WPML_XML2Array;

class Config {

	/**
	 * Holds XML to array helper object.
	 *
	 * @var WPML_XML2Array $xml2array
	 */
	private $xml2array;

	/**
	 * Holds WP_Filesystem_$method object.
	 *
	 * @var object $wpFilesystem
	 */
	private $wpFilesystem;

	/**
	 * Admin notice error message.
	 *
	 * @var string $errorMessage
	 */
	private $errorMessage;

	/**
	 * WPML_Forms_XML_Config constructor.
	 *
	 * @param WPML_XML2Array $xml2array XML to array helper.
	 * @param object         $wpFilesystem WP_Filesystem_$method object.
	 */
	public function __construct( WPML_XML2Array $xml2array, $wpFilesystem ) {
		$this->xml2array    = $xml2array;
		$this->wpFilesystem = $wpFilesystem;
	}

	/**
	 * Gets XML config file contents.
	 *
	 * @param string $file Path to file.
	 *
	 * @return array
	 */
	public function getXmlArray( $file ) {

		$xml_array = [];

		if ( ! $this->wpFilesystem->exists( $file ) ) {
			/* translators: %s: path to file */
			$this->errorMessage = sprintf( __( 'WPML Forms config file does not exist: %s', 'wpml-forms' ), $file );
		} else {

			$file_contents = $this->wpFilesystem->get_contents( $file );
			$xml_array     = $this->xml2array->get( $file_contents );

			if ( ! $xml_array ) {
				/* translators: %s: path to file */
				$this->errorMessage = sprintf( __( 'WPML Forms cannot parse config file: %s', 'wpml-forms' ), $file );
			}
		}

		if ( $this->errorMessage ) {
			add_action( 'admin_notices', [ $this, 'adminNotice' ] );
		}

		return ! $this->errorMessage ? $xml_array : [];
	}

	/**
	 * Renders admin notice.
	 *
	 * @codeCoverageIgnore
	 */
	public function adminNotice() {
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $this->errorMessage ) . '</p></div>';
	}
}
