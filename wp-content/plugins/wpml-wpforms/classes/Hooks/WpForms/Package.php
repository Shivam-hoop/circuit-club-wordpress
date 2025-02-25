<?php

namespace WPML\Forms\Hooks\WpForms;

class Package {

	/** @var string $kind_slug Translation package kind slug. */
	private $kind_slug;

	/**
	 * Package constructor.
	 *
	 * @param string $kind_slug Translation package kind slug.
	 */
	public function __construct( $kind_slug ) {
		$this->kind_slug = $kind_slug;
	}

	/** Adds hooks. */
	public function addHooks() {
		add_filter( "wpml_forms_{$this->kind_slug}_package", array( $this, 'applyFilter' ), 10, 2 );
		add_action( 'wpforms_delete_form', [ $this, 'deletePackage' ] );
	}

	/**
	 * Applies filter to translation package properties.
	 *
	 * @param array $package Translation package configuration.
	 * @param int   $formId Form ID.
	 *
	 * @return array
	 */
	public function applyFilter( $package, $formId ) {
		$package['title']     = get_the_title( $formId );
		$package['edit_link'] = esc_url(
			add_query_arg(
				array(
					'page'    => 'wpforms-builder',
					'view'    => 'fields',
					'form_id' => $formId,
				),
				admin_url( 'admin.php' )
			)
		);
		$package['view_link'] = esc_url(
			add_query_arg(
				array( 'wpforms_form_preview' => $formId ),
				home_url()
			)
		);

		return $package;
	}

	/**
	 * Delete the package and strings bound to the form.
	 *
	 * @param array $ids Post IDs.
	 *
	 * @return void
	 */
	public function deletePackage( $ids ) {

		foreach ( $ids as $id ) {
			do_action( 'wpml_delete_package', $id, $this->kind_slug );
		}
	}
}
