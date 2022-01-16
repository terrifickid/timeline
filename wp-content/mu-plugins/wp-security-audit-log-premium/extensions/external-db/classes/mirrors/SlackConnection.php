<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slack connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_SlackConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'slack';
	}

	public static function get_name() {
		return __( 'Slack', 'wp-security-audit-log' );
	}


	public static function get_config_definition() {
		return [
			'desc'   => __( 'General mirror connection description.', 'wp-security-audit-log' ),
			'fields' => [
				'webhook' => [
					'label'      => __( 'Webhook URL', 'wp-security-audit-log' ),
					'type'       => 'text',
					'desc'       => sprintf(
					/* translators: hyperlink to the Slack webhook documentation page */
						__( 'If you are not familiar with incoming WebHooks on Slack, please refer to %s.', 'wp-security-audit-log' ),
						sprintf(
							'<a href="%1$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
							esc_url( 'https://api.slack.com/messaging/webhooks' ),
							__( 'Slack webhooks documentation', 'wp-security-audit-log' )
						)
					),
					'validation' => 'slackWebhook',
					'required'   => true,
					'error'      => __( 'Invalid Webhook URL', 'wp-security-audit-log' )
				]
			]
		];
	}

	protected static function add_extra_requirements( $checker ) {
		$checker->requirePhpExtensions( [ 'curl' ] );

		return $checker;
	}

	public function get_monolog_handler() {
		$webhook = array_key_exists( 'webhook_url', $this->connection ) ? $this->connection['webhook_url'] : $this->connection['webhook'];

		return new \WSAL_Vendor\Monolog\Handler\SlackWebhookHandler(
			$webhook,
			null,
			null,
			true, // useAttachment
			null, // iconEmoji
			false, // useShortAttachment
			true, // includeContextAndExtra
			\WSAL_Vendor\Monolog\Logger::DEBUG
		);
	}

	public function pre_process_metadata( $metadata, $mirror ) {
		unset( $metadata['Severity'] );

		if ( is_array( $mirror ) && array_key_exists( 'source', $mirror ) ) {
			//  prepend the mirror identifier (the label is not translated on purpose)
			$metadata = [
				            'Identifier' => $mirror['source']
			            ] + $metadata;
		}

		return $metadata;
	}
}
