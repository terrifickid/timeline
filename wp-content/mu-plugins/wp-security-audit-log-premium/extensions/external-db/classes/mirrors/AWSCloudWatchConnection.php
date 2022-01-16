<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AWS CloudWatch connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_AWSCloudWatchConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'aws_cloudwatch';
	}

	public static function get_name() {
		return __( 'AWS CloudWatch', 'wp-security-audit-log' );
	}

	public static function get_config_definition() {
		$wsal = WpSecurityAuditLog::GetInstance();

		$aws_definition = [
			'desc'   => __( 'General mirror connection description.', 'wp-security-audit-log' ),
			'fields' => [
				'region'    => [
					'label'   => __( 'Region', 'wp-security-audit-log' ),
					'type'    => 'select',
					'options' => [
						'us-east-1'      => 'US East (N. Virginia)',
						'us-east-2'      => 'US East (Ohio)',
						'us-west-1'      => 'US West (N. California)',
						'us-west-2'      => 'US West (Oregon)',
						'af-south-1'     => 'Africa (Cape Town)',
						'ap-east-1'      => 'Asia Pacific (Hong Kong)',
						'ap-south-1'     => 'Asia Pacific (Mumbai)',
						'ap-northeast-3' => 'Asia Pacific (Osaka)',
						'ap-northeast-2' => 'Asia Pacific (Seoul)',
						'ap-southeast-1' => 'Asia Pacific (Singapore)',
						'ap-southeast-2' => 'Asia Pacific (Sydney)',
						'ap-northeast-1' => 'Asia Pacific (Tokyo)',
						'ca-central-1'   => 'Canada (Central)',
						'eu-central-1'   => 'Europe (Frankfurt)',
						'eu-west-1'      => 'Europe (Ireland)',
						'eu-west-2'      => 'Europe (London)',
						'eu-south-1'     => 'Europe (Milan)',
						'eu-west-3'      => 'Europe (Paris)',
						'eu-north-1'     => 'Europe (Stockholm)',
						'me-south-1'     => 'Middle East (Bahrain)',
						'sa-east-1'      => 'South America (São Paulo)'
					]
				],
				'key'       => [
					'label'    => __( 'AWS Key', 'wp-security-audit-log' ),
					'type'     => 'text',
					'required' => true
				],
				'secret'    => [
					'label'    => __( 'AWS Secret', 'wp-security-audit-log' ),
					'type'     => 'text',
					'required' => true
				],
				'token'     => [
					'label' => __( 'AWS Session Token', 'wp-security-audit-log' ),
					'type'  => 'text',
					'desc'  => esc_html__( 'This is optional.', 'wp-security-audit-log' )
				],
				'group'     => [
					'label'      => __( 'Log group name', 'wp-security-audit-log' ),
					'type'       => 'text',
					'validation' => 'cloudWatchGroupName',
					'error'      => sprintf(
						esc_html__( 'Invalid AWS group name. It must satisfy regular expression pattern: %s', 'wp-security-audit-log' ),
						'[\.\-_/#A-Za-z0-9]+'
					),
					'desc'       => sprintf(
						esc_html__( 'If you do not specify a group name, one will be created using the default group name "%s".', 'wp-security-audit-log' ),
						'WP_Activity_Log'
					)
				],
				'stream'    => [
					'label' => __( 'Log stream name', 'wp-security-audit-log' ),
					'type'  => 'text',
					'desc'  => esc_html__( 'If you do not specify a stream name, one will be created using the site name as stream name.', 'wp-security-audit-log' )
				],
				'retention' => [
					'label'   => __( 'Retention', 'wp-security-audit-log' ),
					'type'    => 'select',
					'options' => [
						'0'    => 'indefinite',
						'1'    => '1',
						'3'    => '3',
						'5'    => '5',
						'7'    => '7',
						'14'   => '14',
						'30'   => '30',
						'60'   => '60',
						'90'   => '90',
						'120'  => '120',
						'150'  => '150',
						'180'  => '180',
						'365'  => '365',
						'400'  => '400',
						'545'  => '545',
						'731'  => '731',
						'1827' => '1827',
						'3653' => '3653'
					],
					'desc'    => esc_html__( 'Days to keep logs.', 'wp-security-audit-log' ),
				],
			]
		];

		if ( $wsal->isMultisite() ) {
			$aws_definition['fields']['stream'] = [
				'label'    => __( 'Stream', 'wp-security-audit-log' ),
				'type'     => 'radio',
				'required' => true,
				'options'  => [
					'single-stream'  => [
						'label' => __( 'Mirror the activity logs of all sub sites on the network to one Stream', 'wp-security-audit-log' ),
						'subfields' => [								
							'stream'    => [
								'label' => __( 'Log stream name', 'wp-security-audit-log' ),
								'type'  => 'text',
								'desc'  => esc_html__( 'If you do not specify a stream name, one will be created using the site name as stream name.', 'wp-security-audit-log' )
							],
						]
					],
					'multiple-streams' => [
						'label'     => __( 'Create a Stream for every individual sub site on the network. The Stream name should be the:', 'wp-security-audit-log' ),
						'subfields' => [
							'stream-setting' => [
								'label'      => false,
								'type'       => 'radio',
								'required'   => false,
								'options'  => [
									'sitename'  => [
										'label' => __( 'Sitename', 'wp-security-audit-log' ),
									],
									'fqdn'  => [
										'label' => __( 'FQDN', 'wp-security-audit-log' ),
									],
								],
							],
						]
					]
				]
			];
		}
		return $aws_definition;
	}

	/**
	 * Displays a notice about missing AWS SDk library if needed.
	 *
	 * @param WpSecurityAuditLog $plugin
	 *
	 * @since 4.3.2
	 */
	public static function display_no_aws_sdk_notice( WpSecurityAuditLog $plugin ) {
		$should_notice_be_displayed = $plugin->GetGlobalBooleanSetting( 'show-aws-sdk-config-nudge-4_3_2', false );
		if ( ! $should_notice_be_displayed ) {
			return;
		}

		echo '<div class="notice notice-error is-dismissible" style="padding-bottom: .5em;" data-dismiss-action="wsal_dismiss_missing_aws_sdk_nudge" data-nonce="' . wp_create_nonce( 'dismiss_missing_aws_sdk_nudge' ) . '">';
		echo '<p>' . esc_html__( 'You have setup a mirroring connection to AWS CloudWatch in the WP Activity Log plugin. In this version we\'ve done some changes and you need to add the following lines to the wp-config.php file to enable the AWS library.', 'wp-security-audit-log' ) . '</p>';
		echo '<code>define( \'WSAL_LOAD_AWS_SDK\', \'true\' );</code>';
		echo '</div>';
	}

	public function get_monolog_handler() {

		$wsal = WpSecurityAuditLog::GetInstance();

		$region    = array_key_exists( 'region', $this->connection ) ? $this->connection['region'] : 'eu-west-1';
		$awsKey    = array_key_exists( 'key', $this->connection ) ? $this->connection['key'] : '';
		$awsSecret = array_key_exists( 'secret', $this->connection ) ? $this->connection['secret'] : '';

		if ( empty( $awsKey ) || empty( $awsSecret ) ) {
			throw new Exception( 'AWS key and secret missing.' );
		}
		$sdkParams = [
			'region'      => $region,
			'version'     => 'latest',
			'credentials' => [
				'key'    => $awsKey,
				'secret' => $awsSecret,
			]
		];

		//  token is optional
		if ( array_key_exists( 'token', $this->connection ) && ! empty( $this->connection['token'] ) ) {
			$sdkParams['credentials']['token'] = $this->connection['token'];
		}

		//  instantiate AWS SDK CloudWatch Logs Client
		$client = new \Aws\CloudWatchLogs\CloudWatchLogsClient( $sdkParams );

		//  log group name, will be created if none
		$groupName = array_key_exists( 'group', $this->connection ) && ! empty( $this->connection['group'] ) ? $this->connection['group'] : 'WP_Activity_Log';

		if ( $wsal->isMultisite() ) {
			if ( 'single-stream' == $this->connection['stream'] ) {
				// log stream name, will be created if none
				$streamName = array_key_exists( 'single-stream-stream', $this->connection ) && ! empty( $this->connection['single-stream-stream'] ) ? $this->connection['single-stream-stream'] : get_blog_option( 0, 'blogname' );
			} else if ( 'multiple-streams' == $this->connection['stream'] ) {
				if ( 'sitename' == $this->connection['stream-subfield'] ) {
					$streamName = get_bloginfo( 'name' );
				} else {
					$streamName = preg_replace( "#^[^:/.]*[:/]+#i", "", preg_replace( "{/$}", "", urldecode(  get_bloginfo( 'url' ) ) ) );
				}
			}
		} else {
			// log stream name, will be created if none
			$streamName = array_key_exists( 'stream', $this->connection ) && ! empty( $this->connection['stream'] ) ? $this->connection['stream'] : get_bloginfo( 'name' );
		}

		//  days to keep logs, 14 by default. Set to `null` to allow indefinite retention.
		$retentionDays = 14;
		if ( array_key_exists( 'retention', $this->connection ) && strlen( $this->connection['retention'] ) > 0 ) {
			$retentionDays = intval( $this->connection['retention'] );
			if ( $retentionDays <= 0 ) {
				$retentionDays = null;
			}
		}

		//  instantiate handler (tags are optional)
		$handler = new \WSAL_Vendor\Maxbanton\Cwh\Handler\CloudWatch( $client, $groupName, $streamName, $retentionDays, 1 );

		//  set the JsonFormatter to be able to access your log messages in a structured way
		$handler->setFormatter( new \WSAL_Vendor\Monolog\Formatter\JsonFormatter() );

		return $handler;
	}

	/**
	 * @inerhitDoc
	 * @since 4.3.2
	 */
	protected static function add_extra_requirements( $checker ) {
		$checker->requireClasses( [
			'\Aws\CloudWatchLogs\CloudWatchLogsClient'
		] );

		return $checker;
	}

	public static function get_alternative_error_message( $original_error_message ) {
		if ( preg_match( '/CloudWatchLogsClient/', $original_error_message ) ) {
			$result = '<p>' . esc_html__( 'The AWS library is disabled. Please enable this library by adding the following to the wp-config.php file. Press continue when you are ready or cancel to stop the process.', 'wp-security-audit-log' ) . '</p>';
			$result .= '<code>define( \'WSAL_LOAD_AWS_SDK\', \'true\' );</code>';

			return $result;
		}

		return $original_error_message;
	}
}
