<?php
/**
 * Sessions Model Class
 *
 * Handles sessions that are added/retrieved from the custom table.
 *
 * @package wsal
 * @subpackage user-sessions
 */

/**
 * Object for session data the plugin tracks.
 *
 * @package wsal
 */
class WSAL_Models_Session extends WSAL_Models_ActiveRecord {

	/**
	 * Use Default Adapter.
	 *
	 * @var boolean
	 */
	protected $useDefaultAdapter = true;

	/**
	 * Field to hold the user ID.
	 *
	 * @var integer
	 */
	public $user_id = 0;

	/**
	 * Field to hold the session token. Also the primary key.
	 *
	 * @var string
	 */
	public $session_token = '';

	/**
	 * Field to store the session creation time.
	 *
	 * @var int
	 */
	public $creation_time = 0;

	/**
	 * Field to store the session expiry time.
	 *
	 * @var int
	 */
	public $expiry_time = 0;

	/**
	 * Field to store the user IP.
	 *
	 * @var string
	 */
	public $ip = '';

	/**
	 * Filed to store the user roles.
	 *
	 * @var array
	 */
	public $roles = array();

	/**
	 * Field to store the sites.
	 *
	 * @var string
	 */
	public $sites = '';

	/**
	 * Model Name.
	 *
	 * @var string
	 */
	protected $adapterName = 'Session';

	/**
	 * Save this active record
	 *
	 * @see WSAL_Adapters_MySQL_ActiveRecord::Save()
	 * @return integer|boolean Either the number of modified/inserted rows or false on failure.
	 */
	public function Save() {
		$this->_state = self::STATE_UNKNOWN;

		// Use today's date if not set up.
		if ( is_null( $this->creation_time ) ) {
			$this->creation_time = time();
		}

		$update_id = $this->getId();
		$result    = $this->getAdapter()->Save( $this );

		if ( false !== $result ) {
			$this->_state = ( ! empty( $update_id ) ) ? self::STATE_UPDATED : self::STATE_CREATED;
		}
		return $result;
	}

	/**
	 * Load object data from variable.
	 *
	 * @param array|object $data Data array or object.
	 * @throws Exception - Unsupported type.
	 */
	public function LoadData( $data ) {
		$copy = get_class( $this );
		$copy = new $copy();
		foreach ( (array) $data as $key => $val ) {
			if ( isset( $copy->$key ) ) {
				switch ( true ) {
					//  user ID has to be done explicitly, otherwise it might be considered a datetime below
					case ( 'user_id' === $key ):
						$this->$key = (int) $val;
						break;
					case $this->is_ip_address( $val ):
						$this->$key = (string) $val;
						break;
					case $this->is_a_valid_date( $val ):
						$this->$key = (int) strtotime( $val );
						break;
					case is_array( $copy->$key ):
					case is_object( $copy->$key ):
						$json_decoded_val = WSAL_Helpers_DataHelper::JsonDecode( $val );
						$this->$key       = ( null === $json_decoded_val ) ? $val : $json_decoded_val;
						break;
					case is_int( $copy->$key ):
						$this->$key = (int) $val;
						break;
					case is_float( $copy->$key ):
						$this->$key = (float) $val;
						break;
					case is_bool( $copy->$key ):
						$this->$key = (bool) $val;
						break;
					case is_string( $copy->$key ):
						$this->$key = (string) $val;
						break;
					default:
						throw new Exception( 'Unsupported type "' . gettype( $copy->$key ) . '"' );
				}
			}
		}
		return $this;
	}

	/**
	 * Valid date strings will return true, fail will return false.
	 *
	 * @method is_a_valid_date
	 * @since  4.1.0
	 * @param  string $date A string containing a date that is either valid/not.
	 * @return boolean
	 */
	private function is_a_valid_date( $date ) {
		return preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date );
	}

	/**
	 * Check if the float is IPv4 instead.
	 *
	 * @see WSAL_Models_ActiveRecord::LoadData()
	 * @param float $ip_address - Number to check.
	 * @return bool result validation
	 */
	private function is_ip_address( $ip_address ) {
		return filter_var( $ip_address, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Override default ID column.
	 *
	 * @return string
	 */
	public function getId() {
		return $this->session_token;
	}

}
