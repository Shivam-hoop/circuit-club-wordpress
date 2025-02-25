<?php

namespace WPML\Forms\Helpers\WpForms;

use WPML\FP\Obj;

class Entry {

	/**
	 * @param int $id
	 *
	 * @return string|null
	 */
	public static function getLanguageById( int $id ) {
		$entryMetas = wpforms()
			->get( 'entry_meta' )
			->get_meta(
				[
					'entry_id' => $id,
					'type'     => 'page_id',
				]
			);

		$pageId  = Obj::path( [ 0, 'data' ], $entryMetas );
		$details = apply_filters( 'wpml_post_language_details', null, $pageId );

		return Obj::prop( 'language_code', $details );
	}
}
