<?php
/**
 * Custom_GP_Route_Translation class allows us to set status (e.g 'rejected') of a translation
 * without having to do the Glotpress default POST request to the '-set-status' endpoint.
 */
class Custom_GP_Route_Translation extends GP_Route_Translation {

	/**
	 * ID of the translation
	 * @var int
	 */
	private $translation_id;

	/**
	 * Status of the translation e.g 'rejected'
	 * @var string
	 */
	private $status;

	/**
	 * WordPress nonce
	 * @var string
	 */
	private $_gp_route_nonce;

	public function __construct( $translation_id, $status, $_gp_route_nonce ) {
		$this->translation_id  = $translation_id;
		$this->status          = $status;
		$this->_gp_route_nonce = $_gp_route_nonce;
	}

	/**
	 * A modified copy of the set_status function from the parent GP_Route_Translation class
	 * In this function the variables `$status` and `$translation_id` are set
	 * when an instance of this class is created as opposed to being fetched
	 * from the POST request payload.
	 *
	 * @param string $project_path
	 * @param string $locale_slug
	 * @param string $translations_set_slug
	 */
	public function set_status( $project_path, $locale_slug, $translation_set_slug ) {
		$status         = $this->status;
		$translation_id = $this->translation_id;

		$action = 'update-translation-status-' . $status . '_' . $translation_id;
		if ( ! wp_verify_nonce( $this->_gp_route_nonce, $action ) ) {

			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, array( $this, 'set_status_edit_function' ) );
	}

	/**
	 * Copy of the private function 'edit_single_translation()' from parent class so it's callable from the child class.
	 *
	 * @param string $project_path
	 * @param string $locale_slug
	 * @param string $translations_set_slug
	 * @param callable $edit_function
	 *
	 * @return string html response
	 */
	private function edit_single_translation( $project_path, $locale_slug, $translation_set_slug, $edit_function ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$translation = GP::$translation->get( $this->translation_id );

		if ( ! $translation ) {
			return $this->die_with_error( 'Translation doesn&#8217;t exist!' );
		}

		$this->can_approve_translation_or_forbidden( $translation );

		call_user_func( $edit_function, $project, $locale, $translation_set, $translation );

		$translations = GP::$translation->for_translation(
			$project,
			$translation_set,
			'no-limit',
			array(
				'translation_id' => $translation->id,
				'status'         => 'either',
			),
			array()
		);
		if ( ! empty( $translations ) ) {
			$t = $translations[0];

			$can_edit                = $this->can( 'edit', 'translation-set', $translation_set->id );
			$can_write               = $this->can( 'write', 'project', $project->id );
			$can_approve             = $this->can( 'approve', 'translation-set', $translation_set->id );
			$can_approve_translation = $this->can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );

			$this->tmpl( 'translation-row', get_defined_vars() );
		} else {
			return $this->die_with_error( 'Error in retrieving translation!' );
		}
	}

	/**
	 * Copy of the private function 'set_status_edit_function' from parent class so it's callable from the child class.
	 */
	private function set_status_edit_function( $project, $locale, $translation_set, $translation ) {
		$res = $translation->set_status( $this->status );

		if ( ! $res ) {
			return $this->die_with_error( 'Error in saving the translation status!' );
		}
	}

	/**
	 * Copy of the private function 'can_approve_translation_or_forbidden' from parent class so it's callable from the child class.
	 */
	private function can_approve_translation_or_forbidden( $translation ) {
		$can_reject_self = ( get_current_user_id() == $translation->user_id && $translation->status == 'waiting' );
		if ( $can_reject_self ) {
			return;
		}
		$this->can_or_forbidden( 'approve', 'translation', $translation->id, null, array( 'translation' => $translation ) );
	}
}
