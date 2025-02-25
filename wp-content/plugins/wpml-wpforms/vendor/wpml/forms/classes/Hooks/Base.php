<?php

namespace WPML\Forms\Hooks;

use WPML\Forms\Translation\Factory;
use WPML\Forms\Translation\Package;

class Base {

	/**
	 * Form type slug specific for each plugin.
	 *
	 * @var string $slug
	 */
	protected $slug;

	/**
	 * Translation package kind specific for each plugin.
	 *
	 * @var string $kind
	 */
	protected $kind;

	/**
	 * Holds currently processed form ID.
	 *
	 * @var int $formId
	 */
	protected $formId;

	/**
	 * Holds factory object that creates packages on demand.
	 *
	 * @var Factory $factory
	 */
	protected $factory;

	/**
	 * Holds translation package object created on demand.
	 *
	 * @var Package $package
	 */
	protected $package;

	/**
	 * Hooks\Base constructor.
	 *
	 * @param string  $slug Form type slug.
	 * @param string  $kind Translation package kind.
	 * @param Factory $factory Translation package factory.
	 */
	public function __construct( $slug, $kind, Factory $factory ) {
		$this->slug    = $slug;
		$this->kind    = $kind;
		$this->factory = $factory;
	}

	/**
	 * Sets currently processed form ID.
	 *
	 * @param int $formId Currently processed form ID.
	 */
	public function setFormId( $formId ) {
		$this->formId  = intval( $formId );
		$this->package = null;
	}

	/**
	 * Gets currently processed form ID.
	 *
	 * @return int
	 */
	protected function getFormId() {
		return $this->formId;
	}

	/**
	 * Gets form ID from provided data.
	 *
	 * @param array $data Form data.
	 *
	 * @return int|null
	 */
	protected function getId( array $data ) {
		return array_key_exists( 'id', $data ) ? intval( $data['id'] ) : null;
	}

	/**
	 * Creates and returns new translation package.
	 *
	 * @param int $formId Form ID.
	 *
	 * @return Package
	 */
	protected function newPackage( $formId ) {
		return $this->factory->getPackage( $formId, $this->kind );
	}

	/**
	 * Gets translation package.
	 *
	 * @return Package
	 */
	protected function getPackage() {

		if ( ! $this->package ) {
			$this->package = $this->newPackage( $this->formId );
		}

		return $this->package;
	}

	/**
	 * Checks if array value is not empty.
	 *
	 * @param mixed $key Key name.
	 * @param mixed $array Associated array.
	 *
	 * @return bool
	 */
	protected function notEmpty( $key, $array ) {
		return is_string( $key ) && is_array( $array ) && array_key_exists( $key, $array ) && ! empty( $array[ $key ] );
	}
}
