<?php
/**
 * Class: Utility Class
 *
 * Utility class to manage notification.
 *
 * @since 1.0.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WSAL_OPT_PREFIX' ) ) {
	exit( 'Invalid request' );
}

/**
 * Class WSAL_NP_NotificationBuilder
 *
 * Utility class to manage a Notification.
 *
 * @author wp.kytten
 * @package wp-security-audit-log
 */
class WSAL_NP_NotificationBuilder {

	/**
	 * Delete Button Text.
	 *
	 * @var string
	 */
	protected $delete_button_text = '';

	/**
	 * Save Button Text.
	 *
	 * @var string
	 */
	protected $save_button_text = '';

	/**
	 * Add Button Text.
	 *
	 * @var string
	 */
	protected $add_button_text = '';

	/**
	 * Email Input Label.
	 *
	 * @var string
	 */
	protected $email_label = '';

	/**
	 * Phone Input Label.
	 *
	 * @var string
	 */
	protected $phone_label = '';

	/**
	 * Notification Object.
	 *
	 * @var stdClass
	 */
	protected $notif_obj = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->delete_button_text = __( 'Delete', 'wp-security-audit-log' );
		$this->save_button_text   = __( 'Save Notification', 'wp-security-audit-log' );
		$this->add_button_text    = __( 'Add Notification', 'wp-security-audit-log' );
		$this->email_label        = __( 'Email Address or WordPress Username:', 'wp-security-audit-log' );
		$this->phone_label        = __( 'Phone number for SMS notifications:', 'wp-security-audit-log' );
		$this->notif_obj          = new stdClass();
	}

	/**
	 * Return Select 1 Field Values.
	 *
	 * @return array
	 */
	public function GetSelect1Data() {
		return array( 'AND', 'OR' );
	}

	/**
	 * Return Select 2 Field Values.
	 *
	 * @return array
	 */
	public function GetSelect2Data() {
		return array(
			'EVENT ID',
			'DATE',
			'TIME',
			'USERNAME',
			'USER ROLE',
			'SOURCE IP',
			'POST ID',
			'PAGE ID', // @deprecated since 3.1
			'CUSTOM POST ID', // @deprecated since 3.1
			'SITE DOMAIN',
			'POST TYPE',
			'POST STATUS',
			'OBJECT',
			'TYPE',
			'CUSTOM USER FIELD'
		);
	}

	/**
	 * Return Select 3 Field Values.
	 *
	 * @return array
	 */
	public function GetSelect3Data() {
		return array( 'IS EQUAL', 'CONTAINS', 'IS AFTER', 'IS BEFORE', 'IS NOT' );
	}

	/**
	 * Get Select4/Post Status data.
	 *
	 * @since  3.1
	 */
	public function GetSelect4Data() {
		return array( 'DRAFT', 'FUTURE', 'PENDING', 'PRIVATE', 'PUBLISHED' );
	}

	/**
	 * Get Select5/Post Type data.
	 *
	 * @since  3.1
	 */
	public function GetSelect5Data() {
		$post_types = get_post_types( [], 'names' );
		unset( $post_types['attachment'] );
		$post_types = implode( ',', $post_types );
		$post_types = strtoupper( $post_types );
		$post_types = explode( ',', $post_types );

		return $post_types;
	}

	/**
	 * Get Select6/User role data.
	 *
	 * @since  3.1
	 */
	public function GetSelect6Data() {
		$user_roles    = array();
		$wp_user_roles = array();

		// Check if function `wp_roles` exists.
		if ( function_exists( 'wp_roles' ) ) {
			// Get WP user roles.
			$wp_user_roles = wp_roles()->roles;
		} else { // WP Version is below 4.3.0
			// Get global wp roles variable.
			global $wp_roles;

			// If it is not set then initiate WP_Roles class object.
			if ( ! isset( $wp_roles ) ) {
				$new_wp_roles = new WP_Roles();
			}

			// Get WP user roles.
			$wp_user_roles = $new_wp_roles->roles;
		}

		foreach ( $wp_user_roles as $role => $details ) {
			$user_roles[] = translate_user_role( $details['name'] );
		}
		$user_roles = implode( ', ', $user_roles );
		$user_roles = strtoupper( $user_roles );
		$user_roles = explode( ',', $user_roles );
		return $user_roles;
	}

	/**
	 * Get Select7/Event object data.
	 *
	 * @author Ashar Irfan
	 */
	public function GetSelect7Data() {
		$objects = WpSecurityAuditLog::GetInstance()->alerts->get_event_objects_data();
		$objects = implode( ', ', $objects );
		$objects = strtoupper( $objects );
		$objects = explode( ',', $objects );
		return $objects;
	}

	/**
	 * Get Select8/Event type data.
	 *
	 * @author Ashar Irfan
	 */
	public function GetSelect8Data() {
		$type = WpSecurityAuditLog::GetInstance()->alerts->get_event_type_data();
		$type = implode( ', ', $type );
		$type = strtoupper( $type );
		$type = explode( ',', $type );
		return $type;
	}

	/**
	 * Create the default Notification object.
	 *
	 * @param object|null $errors     - (Optional) Errors.
	 * @param object|null $info       - (Optional) Information.
	 * @param object|null $buttons    - (Optional) Buttons.
	 * @param object|null $default    - (Optional) Default values.
	 * @param array|null  $triggers   - (Optional) Triggers.
	 * @param array|null  $view_state - (Optional) View state.
	 * @return null|stdClass
	 */
	public function create( $errors = null, $info = null, $buttons = null, $default = null, $triggers = null, $view_state = null ) {
		if ( $errors ) {
			$this->notif_obj->errors = $errors;
		} else {
			$this->notif_obj->errors = $this->createErrorsEntry();
		}

		if ( $info ) {
			$this->notif_obj->info = $info;
		} else {
			$this->notif_obj->info = $this->createInfoEntry();
		}

		if ( $buttons ) {
			$this->notif_obj->buttons = $buttons;
		} else {
			$this->notif_obj->buttons = $this->createButtonsEntry();
		}

		if ( $default ) {
			$this->notif_obj->default = $default;
		} else {
			$this->notif_obj->default = $this->createDefaultTrigger();
		}

		if ( $triggers ) {
			$this->notif_obj->triggers = $triggers;
		} else {
			$this->notif_obj->triggers = $this->createTriggersEntry();
		}

		if ( $view_state ) {
			$this->notif_obj->viewState = $view_state;
		} else {
			$this->notif_obj->viewState = $this->createViewStateEntry();
		}

		return $this->notif_obj;
	}

	/**
	 * Update Notification Object.
	 *
	 * Update the $this->notif_obj with the provided values. It will add
	 * the key if not found and if the section is object, will add
	 * the section if not found, as an object and add key and value
	 * to it.
	 *
	 * @param string $section - Section.
	 * @param string $key     - Key.
	 * @param mixed  $value   - Value.
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function update( $section, $key, $value ) {
		if ( isset( $this->notif_obj->$section ) ) {
			if ( isset( $this->notif_obj->$section->$key ) ) {
				$this->notif_obj->$section->$key = $value;
				return $this;
			} else {
				if ( is_object( $this->notif_obj->$section ) ) {
					$this->notif_obj->$section->$key = $value;
					return $this;
				}
			}
		}
		$this->notif_obj->$section       = new stdClass();
		$this->notif_obj->$section->$key = $value;
		return $this;
	}

	/**
	 * Update an entry in the errors.triggers object.
	 *
	 * @param string $key   - Key.
	 * @param mixed  $value - Value.
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function updateTriggerError( $key, $value ) {
		$this->notif_obj->errors->triggers->$key = $value;
		return $this;
	}

	/**
	 * Update the viewState entry in the notifObj
	 *
	 * @param array $data - Data array.
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function UpdateViewState( array $data = array() ) {
		$this->notif_obj->viewState = $data;
		return $this;
	}

	/**
	 * Add an entry into notif_obj->triggers[]
	 *
	 * @param object $trigger_entry - Trigger entry object.
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function addTrigger( $trigger_entry ) {
		if ( is_object( $trigger_entry ) ) {
			array_push( $this->notif_obj->triggers, $trigger_entry );
		}
		return $this;
	}

	/**
	 * Clear the internal notif_obj.
	 *
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function clear() {
		$this->notif_obj = new stdClass();
		return $this;
	}

	/**
	 * Reset trigger errors.
	 *
	 * @return WSAL_NP_NotificationBuilder
	 */
	public function clearTriggersErrors() {
		$this->notif_obj->errors = $this->createErrorsEntry();
		return $this;
	}

	/**
	 * Retrieve the value of the specified key from the $_notifObj object
	 *
	 * DO NOT USE TO GET TRIGGERS! Use $this->getTriggers() instead
	 *
	 * @param string $section - Section.
	 * @param string $key     - Key.
	 * @param bool   $default - (Optional) If true, then key is searched in the default trigger entry.
	 * @return mixed|null - If the key is not found.
	 */
	public function getFrom( $section, $key, $default = false ) {
		if ( $default ) {
			if ( isset( $this->notif_obj->default->$section->$key ) ) {
				return $this->notif_obj->default->$section->$key;
			}
			return null;
		}
		if ( isset( $this->notif_obj->$section->$key ) ) {
			return $this->notif_obj->$section->$key;
		}
		return null;
	}

	/**
	 * Retrieve all entries from notifObj->triggers
	 *
	 * @return array
	 */
	public function getTriggers() {
		return $this->notif_obj->triggers;
	}

	/**
	 * Retrieve the provided section from the $_notifObj object
	 *
	 * @param string $section - The section to retrieve from the $_notifObj object.
	 * @return array or null if the section is not found
	 */
	public function getSection( $section ) {
		if ( isset( $this->notif_obj->$section ) ) {
			return $this->notif_obj->$section;
		}
		return null;
	}

	/**
	 * Retrieve the internal notifObj
	 *
	 * @return null|stdClass
	 */
	public function get() {
		return $this->notif_obj;
	}

	/**
	 * JSON Encode the provided object, if provided, or the internal notifObj
	 *
	 * @param object|null $obj - Object.
	 * @return mixed|string|void
	 */
	public function encodeForJs( $obj = null ) {
		if ( $obj ) {
			return json_encode( $obj );
		}
		return json_encode( $this->notif_obj );
	}

	/**
	 * JSON Decode the provided string
	 *
	 * @param string $obj_string - Object string.
	 * @return array|mixed|null
	 */
	public function decodeFromString( $obj_string ) {
		if ( empty( $obj_string ) ) {
			return null;
		}
		$obj_string      = str_replace( '\\', '', $obj_string );
		$this->notif_obj = json_decode( trim( $obj_string ) );
		return $this->notif_obj;
	}

	/**
	 * Create the default errors entry in the notifObj
	 *
	 * @return stdClass
	 */
	public function createErrorsEntry() {
		$obj                  = new stdClass();
		$obj->titleMissing    = '';
		$obj->titleInvalid    = '';
		$obj->emailMissing    = '';
		$obj->emailInvalid    = '';
		$obj->phoneInvalid    = '';
		$obj->triggersMissing = '';
		$obj->triggers        = new stdClass();
		return $obj;
	}

	/**
	 * Create the default buttons entry in the notifObj
	 *
	 * @return stdClass
	 */
	public function createButtonsEntry() {
		$obj                  = new stdClass();
		$obj->deleteButton    = $this->delete_button_text;
		$obj->saveNotifButton = $this->save_button_text;
		$obj->addNotifButton  = $this->add_button_text;
		return $obj;
	}

	/**
	 * Create the default triggers entry in the notifObj
	 *
	 * @return array
	 */
	public function createTriggersEntry() {
		return array();
	}

	/**
	 * Create the default groups entry in the notifObj
	 *
	 * @return array
	 */
	public function createViewStateEntry() {
		return array();
	}

	/**
	 * Create the default info entry in the notifObj
	 *
	 * @return stdClass
	 */
	public function createInfoEntry() {
		$obj             = new stdClass();
		$obj->title      = '';
		$obj->email      = '';
		$obj->phone      = '';
		$obj->emailLabel = $this->email_label;
		$obj->phoneLabel = $this->phone_label;
		return $obj;
	}

	/**
	 * Create the default trigger entry in the notifObj
	 *
	 * @return stdClass
	 */
	public function createDefaultTrigger() {
		$obj                    = new stdClass();
		$obj->select1           = new stdClass();
		$obj->select1->data     = $this->GetSelect1Data();
		$obj->select1->selected = 0;

		$obj->select2           = new stdClass();
		$obj->select2->data     = $this->GetSelect2Data();
		$obj->select2->selected = 0;

		$obj->select3           = new stdClass();
		$obj->select3->data     = $this->GetSelect3Data();
		$obj->select3->selected = 0;

		$obj->select4           = new stdClass();
		$obj->select4->data     = $this->GetSelect4Data();
		$obj->select4->selected = 0;

		$obj->select5           = new stdClass();
		$obj->select5->data     = $this->GetSelect5Data();
		$obj->select5->selected = 0;

		$obj->select6           = new stdClass();
		$obj->select6->data     = $this->GetSelect6Data();
		$obj->select6->selected = 0;

		$obj->select7           = new stdClass();
		$obj->select7->data     = $this->GetSelect7Data();
		$obj->select7->selected = 0;

		$obj->select8           = new stdClass();
		$obj->select8->data     = $this->GetSelect8Data();
		$obj->select8->selected = 0;

		$obj->input1       = '';
		$obj->deleteButton = $this->delete_button_text;
		return $obj;
	}
}
