<?php

namespace WPML\Forms\Translation;

class Package {

	const PACKAGE_NAME_FORMAT  = '%s';
	const PACKAGE_TITLE_FORMAT = 'ID %s';

	/**
	 * Holds currently processed form ID.
	 *
	 * @var int $formId
	 */
	private $formId;

	/**
	 * Translation package kind.
	 *
	 * @var string $kind
	 */
	private $kind;

	/**
	 * Translatable form settings helper.
	 *
	 * @var Properties $settings
	 */
	private $settings;

	/**
	 * Translatable field properties helper.
	 *
	 * @var Properties $fieldProperties
	 */
	private $fieldProperties;

	/**
	 * Translation package parameters.
	 *
	 * @var array $package
	 */
	private $package;

	/**
	 * Registered package strings.
	 *
	 * @var array $registeredPackageStrings Array of string names.
	 */
	private $registeredPackageStrings = [];

	/**
	 * Package constructor.
	 *
	 * @param int        $formId Form ID.
	 * @param string     $kind Translation package kind.
	 * @param Properties $settings Translatable form settings helper.
	 * @param Properties $fieldProperties Translatable field properties helper.
	 */
	public function __construct(
		$formId,
		$kind,
		Properties $settings,
		Properties $fieldProperties
	) {
		$this->formId          = $formId;
		$this->kind            = $kind;
		$this->settings        = $settings;
		$this->fieldProperties = $fieldProperties;
	}

	/**
	 * Sets|gets translation package parameters.
	 *
	 * @return array
	 */
	public function getPackage() {

		if ( ! $this->package ) {

			$kind_slug     = sanitize_title_with_dashes( $this->kind );
			$this->package = apply_filters( "wpml_forms_{$kind_slug}_package", [
				'kind'  => $this->kind,
				'name'  => sprintf( self::PACKAGE_NAME_FORMAT, strval( $this->formId ) ),
				'title' => sprintf( self::PACKAGE_TITLE_FORMAT, strval( $this->formId ) ),
			], $this->formId );
		}

		return $this->package;
	}

	/**
	 * Constructs string name.
	 *
	 * @param int|string $id Form ID.
	 * @param string     $name String name.
	 *
	 * @return string
	 */
	private function getStringName( $id, $name ) {
		return "{$name}-{$id}";
	}

	/**
	 * Constructs string title.
	 *
	 * @param int|string $id Form ID.
	 * @param string     $name String name.
	 *
	 * @return string
	 */
	private function getStringTitle( $id, $name ) {
		return "{$name}-{$id}";
	}

	/**
	 * Constructs option ID.
	 *
	 * @param string $propertyId Parent element name.
	 * @param string $key Option name.
	 *
	 * @return string
	 */
	private function getOptionId( $propertyId, $key ) {
		return "{$propertyId} option {$key}";
	}

	/**
	 * Constructs a name for strings listed in an array by using the parent property name.
	 *
	 * @param string $fieldId The ID of the field.
	 * @param string $parentPropertyName Parent property name.
	 *
	 * @return string
	 */
	private function getPropertyNameForArray( $fieldId, $parentPropertyName ) {
		return "$parentPropertyName-$fieldId";
	}

	/**
	 * Checks if translatable property is an array.
	 *
	 * @param array|string $value Property value.
	 * @param array        $translationSettings Pre-configured translation settings.
	 *
	 * @return bool
	 */
	private function isArray( $value, $translationSettings ) {
		return 'ARRAY' === $translationSettings['type'] && is_array( $value );
	}

	/**
	 * Registers string.
	 *
	 * @param string $value String value.
	 * @param string $propertyId Property ID.
	 * @param string $propertyName Property name.
	 * @param string $type Type of string.
	 */
	public function registerString( $value, $propertyId, $propertyName, $type = 'LINE' ) {
		$string_name = $this->getStringName( $propertyId, $propertyName );
		if ( ! in_array( $string_name, $this->registeredPackageStrings, true ) ) {
			$this->registeredPackageStrings[] = $this->sanitizeStringNameAsStringTranslation( $string_name );
		}
		do_action( 'wpml_register_string',
			$value,
			$string_name,
			$this->getPackage(),
			$this->getStringTitle( $propertyId, $propertyName ),
			$type
		);
	}

	/**
	 * Translates string.
	 *
	 * @param string $value String value.
	 * @param string $propertyId Property ID.
	 * @param string $propertyName Property name.
	 *
	 * @return string
	 */
	public function translateString( $value, $propertyId, $propertyName ) {
		return apply_filters( 'wpml_translate_string',
			$value,
			$this->getStringTitle( $propertyId, $propertyName ),
			$this->getPackage()
		);
	}

	/**
	 * Registers form settings for translation.
	 *
	 * @param array $data Form data.
	 */
	public function registerFormSettings( array $data ) {

		$forTranslation = $this->settings->forTranslation( $data );

		foreach ( $forTranslation as $propertyName => $translationSettings ) {
			$stringValue = $this->settings->getValue( $data, $propertyName );
			if ( $stringValue ) {
				if ( $this->isArray( $stringValue, $translationSettings ) ) {
					$this->registerOptions(
						$propertyName,
						$stringValue,
						$translationSettings,
						'setting'
					);
				} else {
					$this->registerString(
						$stringValue,
						$propertyName,
						'setting'
					);
				}
			}
		}
	}

	/**
	 * Applies translation to form settings.
	 *
	 * @param array $data Form settings.
	 *
	 * @return array
	 */
	public function translateFormSettings( array $data ) {

		$forTranslation = $this->settings->forTranslation( $data );

		foreach ( $forTranslation as $propertyName => $translationSettings ) {
			$stringValue = $this->settings->getValue( $data, $propertyName );
			if ( $stringValue ) {
				if ( $this->isArray( $stringValue, $translationSettings ) ) {
					$data[ $propertyName ] = $this->translateOptions(
						$stringValue,
						$propertyName,
						$translationSettings,
						'setting'
					);
				} else {
					$data[ $propertyName ] = $this->translateString(
						$stringValue,
						$propertyName,
						'setting'
					);
				}
			}
		}

		return $data;
	}

	/**
	 * Registers field for translation.
	 *
	 * @param string $fieldId Field ID.
	 * @param array  $data Field data.
	 */
	public function registerField( $fieldId, array $data ) {

		$forTranslation = $this->fieldProperties->forTranslation( $data );

		foreach ( $forTranslation as $propertyName => $translationSettings ) {
			$stringValue = $this->fieldProperties->getValue( $data, $propertyName );
			if ( $stringValue ) {
				if ( $this->isArray( $stringValue, $translationSettings ) ) {
					$this->registerOptions(
						$fieldId,
						$stringValue,
						$translationSettings,
						$propertyName
					);
				} else {
					$this->registerString(
						$stringValue,
						$fieldId,
						$propertyName
					);
				}
			}
		}
	}

	/**
	 * Applies translation to field.
	 *
	 * @param array  $data Field data.
	 * @param string $fieldId Field ID.
	 *
	 * @return array
	 */
	public function translateField( array $data, $fieldId ) {

		$forTranslation = $this->fieldProperties->forTranslation( $data );

		foreach ( $forTranslation as $propertyName => $translationSettings ) {
			$stringValue = $this->fieldProperties->getValue( $data, $propertyName );
			if ( $stringValue ) {
				if ( $this->isArray( $stringValue, $translationSettings ) ) {
					$data[ $propertyName ] = $this->translateOptions(
						$stringValue,
						$fieldId,
						$translationSettings,
						$propertyName
					);
				} else {
					$data[ $propertyName ] = $this->translateString(
						$stringValue,
						$fieldId,
						$propertyName
					);
				}
			}
		}

		return $data;
	}

	/**
	 * Registers field of option type for translation.
	 *
	 * @param string $fieldId Field ID.
	 * @param array  $options Field options.
	 * @param array  $translationSettings Pre-defined translation settings.
	 * @param string $parentPropertyName The parent property name.
	 */
	private function registerOptions( $fieldId, array $options, array $translationSettings, $parentPropertyName ) {

		foreach ( $options as $key => $option ) {

			if ( array_key_exists( 'properties', $translationSettings ) ) {
				$properties = explode( ',', $translationSettings['properties'] );

				foreach ( $properties as $propertyName ) {
					if ( isset( $option[ $propertyName ] ) ) {
						$this->registerString(
							$option[ $propertyName ],
							$this->getOptionId( $fieldId, $key ),
							$propertyName
						);
					}
				}
			} elseif ( is_int( $key ) && is_string( $option ) ) {
				$this->registerString(
					$option,
					$key,
					$this->getPropertyNameForArray( $fieldId, $parentPropertyName )
				);
			}
		}
	}

	/**
	 * Applies translation to field of option type.
	 *
	 * @param array  $options Field options.
	 * @param string $fieldId Field ID.
	 * @param array  $translationSettings Pre-defined translation settings.
	 * @param string $parentPropertyName The parent property name.
	 *
	 * @return array
	 */
	private function translateOptions( array $options, $fieldId, array $translationSettings, $parentPropertyName ) {

		foreach ( $options as $key => &$option ) {
			if ( array_key_exists( 'properties', $translationSettings ) ) {
				$properties = explode( ',', $translationSettings['properties'] );

				foreach ( $properties as $propertyName ) {
					if ( isset( $option[ $propertyName ] ) ) {
						$option[ $propertyName ] = $this->translateString(
							$option[ $propertyName ],
							$this->getOptionId( $fieldId, $key ),
							$propertyName
						);
					}
				}
			} elseif ( is_int( $key ) && is_string( $option ) ) {
				$options[ $key ] = $this->translateString(
					$option,
					$key,
					$this->getPropertyNameForArray( $fieldId, $parentPropertyName )
				);
			}
		}

		return $options;
	}

	/** Cleans translation packages. */
	public function cleanup() {
		$package       = $this->getPackage();
		$stringPackage = apply_filters( 'wpml_st_get_string_package', null, $package );

		if ( ! $stringPackage ) {
			return;
		}

		$context    = $stringPackage->get_string_context_from_package();
		$allStrings = $stringPackage->get_package_strings();

		foreach ( $allStrings as $string ) {
			if ( ! in_array( $string->name, $this->registeredPackageStrings, true ) ) {
				icl_unregister_string( $context, $string->name );
			}
		}
	}

	/**
	 * Sanitizes string name.
	 *
	 * @param string $string_name String name.
	 *
	 * @return string
	 * @see \WPML_Package::sanitize_string_name
	 */
	private function sanitizeStringNameAsStringTranslation( $string_name ) {
		return preg_replace( '/[ \[\]]+/', '-', $string_name );
	}
}
