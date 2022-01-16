<?php
/**
 * Session Interface.
 *
 * Interface used by the Session data.
 *
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface used by the Session data.
 *
 * @package wsal
 */
interface WSAL_Adapters_SessionInterface {

	/**
	 * Delete sessions by passed session tokens.
	 *
	 * @param array $session_tokens - array of session tokens.
	 * @return array
	 */
	public function delete_by_session_tokens( $session_tokens = array() );

	/**
	 * Delete sessions by passed user IDs.
	 *
	 * @param array $user_ids - array of user ids to clear sessions for.
	 * @return array
	 */
	public function delete_by_user_ids( $user_ids = array() );

	/**
	 * Delete sessions by passed user IDs.
	 *
	 * @param string $session_token - a single session token to keep - removing others.
	 * @return bool
	 */
	public function delete_all_user_sessions_except( $session_token = '' );

	/**
	 * Load by name and occurrence id.
	 *
	 * @param string $session_token - a session token.
	 */
	public function load_by_session_token( $session_token = '' );

	/**
	 * Load by user ID.
	 *
	 * @param int $user_id - a single user ID token.
	 */
	public function load_all_sessions_by_user_id( $user_id = 0 );
}
