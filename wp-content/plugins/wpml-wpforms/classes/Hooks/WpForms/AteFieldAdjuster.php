<?php

namespace WPML\Forms\Hooks\WpForms;

use WPML\FP\Obj;
use function WPML\FP\pipe;

class AteFieldAdjuster {

	const NAME_PART_SETTING      = 'setting';
	const NAME_PART_CONFIRMATION = 'confirmations';
	const NAME_PART_NOTIFICATION = 'notifications';
	const REGEX_NOTIFICATION     = '/(.*)-' . self::NAME_PART_NOTIFICATION . '-(.*)-(\d*)/';
	const REGEX_GENERAL_SETTING  = '/' . self::NAME_PART_SETTING . '-(.*)/';
	const REGEX_CONFIRMATION     = '/(.*)-' . self::NAME_PART_CONFIRMATION . '-(.*)-(\d*)/';
	const REGEX_FIELD_STRING     = '/(.*)-(\d*)/';
	const REGEX_FIELD_ITEM       = '/(.*)-(\d*)-(\d*)/';
	const REGEX_FIELD_OPTION     = '/(.*)-(\d*)-option-(\d*)/';

	const FIELDS_CONFIGS = [
		'types'  => [
			'textarea'           => 'Paragraph Text',
			'text'               => 'Single Line Text',
			'select'             => 'Dropdown',
			'radio'              => 'Multiple Choice',
			'checkbox'           => 'Checkboxes',
			'number-slider'      => 'Number Slider',
			'date-time'          => 'Date / Time',
			'url'                => 'Website / URL',
			'file-upload'        => 'File Upload',
			'richtext'           => 'Rich Text',
			'divider'            => 'Section Divider',
			'html'               => 'HTML Field',
			'net_promoter_score' => 'Net Promoter Score',
			'hidden'             => 'Hidden Field',
			'likert_scale'       => 'Likert Scale',
			'pagebreak'          => 'Page Title',
			'payment-single'     => 'Payment Single Item',
			'payment-total'      => 'Payment Total',
			'entry-preview'      => 'Entry Preview',
		],
		'groups' => [
			'label'   => 'Choices',
			'columns' => 'Columns',
			'rows'    => 'Rows',
		],
		'labels' => [
			'default_value'          => 'Default value',
			'next'                   => 'Next Button',
			'prev'                   => 'Previous Button',
			'preview-notice'         => 'Preview Notice',
			'option'                 => 'Choices',
			'form_desc'              => 'Form Description',
			'form_title'             => 'Form Title',
			'submit_text'            => 'Submit Button Text',
			'submit_text_processing' => 'Submit Button Processing Text',
		],
		'nested' => [
			'columns' => 'Column',
			'rows'    => 'Row',
		],
	];

	/**
	 * @var array
	 */
	private $formData;

	public function __construct( array $formData ) {
		$this->formData = $formData;
	}

	private function getFieldType( int $id ) {
		return Obj::path( [ 'fields', $id, 'type' ], $this->formData );
	}

	public function handle( array $field ) : array {
		$type = Obj::prop( 'field_type', $field );

		if ( is_null( $type ) ) {
			return $field;
		}

		$appendRootLevelGroup = self::getAppendGroup( 'wpforms', 'WP Form' );

		if ( $this->isSettingField( $type ) ) {
			return $this->adjustSettingField( $appendRootLevelGroup( $field ), $type );
		}

		return $this->adjustFormField( $appendRootLevelGroup( $field ), $type );
	}

	public function isSettingField( string $fieldType ) : bool {
		$parts    = explode( '-', $fieldType );
		$parts[0] = $parts[0] ?? null;
		$parts[1] = $parts[1] ?? null;

		return self::NAME_PART_SETTING === $parts[0]
			|| self::NAME_PART_CONFIRMATION === $parts[1]
			|| self::NAME_PART_NOTIFICATION === $parts[1];
	}

	private function adjustSettingField( array $field, string $fieldType ) : array {
		$index = null;
		if ( preg_match( self::REGEX_GENERAL_SETTING, $fieldType, $matches ) ) {
			$stringName  = $matches[1] ?? $fieldType;
			$appendGroup = self::getAppendGroup( self::NAME_PART_SETTING, 'Settings' );
		} elseif ( preg_match( self::REGEX_CONFIRMATION, $fieldType, $matches ) ) {
			$stringName = $matches[1] ?? $fieldType;
			$index      = (int) ( $matches[3] ?? 0 );

			$appendGroup = pipe(
				self::getAppendGroup( self::NAME_PART_CONFIRMATION, 'Confirmations' ),
				self::getAppendGroup( self::NAME_PART_CONFIRMATION . '-' . $index, self::getLabelFor( 'types', 'confirmation', "%s #$index" ) )
			);
		} elseif ( preg_match( self::REGEX_NOTIFICATION, $fieldType, $matches ) ) {
			$stringName = $matches[1] ?? $fieldType;
			$index      = (int) ( $matches[3] ?? 0 );

			$appendGroup = pipe(
				self::getAppendGroup( self::NAME_PART_NOTIFICATION, 'Notifications' ),
				self::getAppendGroup( self::NAME_PART_NOTIFICATION . '-' . $index, self::getLabelFor( 'types', 'notification', "%s #$index" ) )
			);
		} else {
			return $field;
		}

		$titleFormat = $index ? "%s #$index" : '%s';
		return pipe(
			$appendGroup,
			self::getAppendTitle( self::getLabelFor( 'labels', $stringName, $titleFormat ) )
		)( $field );
	}

	private function adjustFormField( array $field, string $fieldType ) : array {
		if ( preg_match( self::REGEX_FIELD_OPTION, $fieldType, $matches )
			|| preg_match( self::REGEX_FIELD_ITEM, $fieldType, $matches )
		) {
			return $this->adjustField( $field, $matches[1], (int) $matches[2], (int) $matches[3] );
		} elseif ( preg_match( self::REGEX_FIELD_STRING, $fieldType, $matches ) ) {
			return $this->adjustField( $field, $matches[1], (int) $matches[2] );
		}

		return $field;
	}

	private function adjustField( array $field, string $stringName, int $fieldId, int $index = null ): array {
		if ( is_null( $index ) ) {
			$appendTitle = self::getAppendTitle( self::getLabelFor( 'labels', $stringName ) );
		} else {
			$label       = self::getLabelFor( 'groups', $stringName ) . ' / ' . self::getLabelFor( 'nested', $stringName, "%s #$index" );
			$appendTitle = Obj::assoc( 'title', $label );
		}

		$addTopLevelGroup = self::getAppendGroup( 'fields', 'Fields' );
		$addFieldGroup    = self::getAppendGroup( 'field-' . $fieldId, self::getLabelFor( 'types', $this->getFieldType( $fieldId ), "%s #$fieldId" ) );

		return pipe(
			$appendTitle,
			$addTopLevelGroup,
			$addFieldGroup
		)( $field );
	}

	private static function getLabelFor( string $namespace, string $stringName, string $format = '%s' ) : string {
		$label = Obj::pathOr( $stringName, [ $namespace, $stringName ], self::FIELDS_CONFIGS );

		return ucfirst( sprintf( $format, $label ) );
	}

	private static function getAppendGroup( string $groupKey, string $groupLabel ) : callable {
		return function( $field ) use ( $groupKey, $groupLabel ) {
			$field['group'] = $field['group'] ?? [];
			return Obj::assocPath( [ 'group', $groupKey ], $groupLabel, $field );
		};
	}

	private static function getAppendTitle( string $title ) : callable {
		return Obj::assoc( 'title', $title );
	}
}
