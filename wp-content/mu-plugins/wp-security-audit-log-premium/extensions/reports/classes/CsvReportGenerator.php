<?php
/**
 * Class WSAL_Rep_CsvReportGenerator
 * Provides utility methods to generate a csv report
 *
 * @package wsal/report
 */

if ( ! class_exists( 'WSAL_Rep_Plugin' ) ) {
	exit( 'You are not allowed to view this page.' );
}

/**
 * Class WSAL_Rep_CsvReportGenerator
 * Provides utility methods to generate a csv report
 *
 * @package wsal/report
 */
class WSAL_Rep_CsvReportGenerator extends WSAL_Rep_AbstractReportGenerator {

	/**
	 * Generate the CSV of the Report.
	 *
	 * @param array  $data - Data.
	 * @param array  $filters - Filters.
	 * @param string $uploads_dir_path - Uploads Directory Path.
	 * @param string $delim - (Optional) Delimiter.
	 * @return int|string
	 */
	public function Generate( array $data, array $filters, $uploads_dir_path, $delim = ',' ) {
		if ( empty( $data ) ) {
			return 0;
		}
		// Split data by blog so we can display an organized report.
		$temp_data = array();
		foreach ( $data as $k => $entry ) {
			$blogName                  = $entry['blog_name'];
			$user                      = get_user_by( 'login', $entry['user_name'] );
			$entry['user_displayname'] = empty( $user ) ? '' : WSAL_Utilities_UsersUtils::get_display_label( WpSecurityAuditLog::GetInstance(), $user );
			if ( ! isset( $temp_data[ $blogName ] ) ) {
				$temp_data[ $blogName ] = array();
			}
			array_push( $temp_data[ $blogName ], $entry );
		}

		if ( empty( $temp_data ) ) {
			return 0;
		}

		// Check directory once more.
		if ( ! is_dir( $uploads_dir_path ) || ! is_readable( $uploads_dir_path ) || ! is_writable( $uploads_dir_path ) ) {
			return 1;
		}

		$report_filename = 'wsal_report_' . WSAL_Rep_Util_S::GenerateRandomString() . '.csv';
		$report_filepath = $uploads_dir_path . $report_filename;

		$file = fopen( $report_filepath, 'w' );

		// Add columns.
		if ( ! empty( $filters['number_logins'] ) ) {
			$columns = array(
				array(
					esc_html__('Username', 'wp-security-audit-log'),
					esc_html__('User', 'wp-security-audit-log'),
					esc_html__('Role', 'wp-security-audit-log'),
					esc_html__('Logins', 'wp-security-audit-log'),
				),
			);
		} else {
			if ( isset( $filters['type_statistics'] ) ) {
				$columns = $this->_getColumns( $filters['type_statistics'] );
			} else {
				$columns = array(
					array(
						esc_html__('Blog Name', 'wp-security-audit-log'),
						esc_html__('Code', 'wp-security-audit-log'),
						esc_html__('Type', 'wp-security-audit-log'),
						esc_html__('Date', 'wp-security-audit-log'),
						esc_html__('Username', 'wp-security-audit-log'),
						esc_html__('User', 'wp-security-audit-log'),
						esc_html__('Role', 'wp-security-audit-log'),
						esc_html__('Source IP', 'wp-security-audit-log'),
						esc_html__('Object Type', 'wp-security-audit-log'),
						esc_html__('Event Type', 'wp-security-audit-log'),
						esc_html__('Message', 'wp-security-audit-log'),
						esc_html__('Metadata', 'wp-security-audit-log'),
						esc_html__('Links', 'wp-security-audit-log'),
					),
				);
			}
		}

		$out = '';
		foreach ( $columns as $row ) {
			$quoted_data = array_map( array( $this, 'quote' ), $row );
			$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
		}
		fwrite( $file, $out );

		if ( ! empty( $filters['number_logins'] ) ) {
			$temp_data = array();
			foreach ( $data as $entry ) {
				$user_name = $entry['user_name'];
				if ( ! isset( $temp_data[ $user_name ] ) ) {
					$temp_data[ $user_name ] = array(
						'counter'   => 1,
						'user_name' => $user_name, // Username of the user.
						'user'      => $entry['user_displayname'],
						'role'      => $entry['user_name'],
					);
				} else {
					$temp_data[ $user_name ]['counter'] ++;
				}
			}
			foreach ( $temp_data as $element ) {
				$values = array(
					array(
						$element['user_name'],
						$element['user'],
						$element['role'],
						$element['counter'],
					),
				);
				$out    = '';
				foreach ( $values as $row ) {
					$quoted_data = array_map( array( $this, 'quote' ), $row );
					$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
				}
				fwrite( $file, $out );
			}
		} else {
			if ( isset( $filters['type_statistics'] ) ) {
				$this->_writeRows( $file, $data, $filters['type_statistics'], $delim );
			} else {
				foreach ( $temp_data as $blogName => $entry ) {
					// Add rows.
					foreach ( $entry as $k => $alert ) {
						$values = array(
							array(
								$alert['blog_name'],
								$alert['alert_id'],
								$alert['code'],
								WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $alert['timestamp'], 'datetime', true, false, false ),
								$alert['user_name'],
								$alert['user_displayname'],
								$alert['role'],
								$alert['user_ip'],
								$alert['object'],
								$alert['event_type'],
								$alert['message'],
								$alert['metadata'],
								$alert['links']
							),
						);
						$out        = '';
						foreach ( $values as $row ) {
							$quoted_data = array_map( array( $this, 'quote' ), $row );
							$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
						}
						fwrite( $file, $out );
					}
				}
			}
		}
		fclose( $file );
		return $report_filename;
	}

	/**
	 * Generate the CSV file of the Unique IP Report.
	 *
	 * @param array $data - Data.
	 * @param string $uploads_dir_path - Uploads Directory Path.
	 * @param string $delim - (Optional) Delimiter.
	 *
	 * @return int|string
	 */
	public function GenerateUniqueIPS( array $data, $uploads_dir_path, $delim = ',' ) {
		if ( empty( $data ) ) {
			return 0;
		}

		// Check directory once more
		if ( ! is_dir( $uploads_dir_path ) || ! is_readable( $uploads_dir_path ) || ! is_writable( $uploads_dir_path ) ) {
			return 1;
		}

		$report_filename = 'wsal_report_' . WSAL_Rep_Util_S::GenerateRandomString() . '.csv';
		$report_filepath = $uploads_dir_path . $report_filename;

		$file = fopen( $report_filepath, 'w' );

		// Add columns
		$columns = array(
			array(
				esc_html__( 'Username', 'wp-security-audit-log' ),
				esc_html__( 'Display name', 'wp-security-audit-log' ),
				esc_html__( 'Unique IP', 'wp-security-audit-log' ),
				esc_html__( 'List of IP addresses', 'wp-security-audit-log' ),
			),
		);
		$out     = '';
		foreach ( $columns as $row ) {
			$quoted_data = array_map( array( $this, 'quote' ), $row );
			$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
		}
		fwrite( $file, $out );

		foreach ( $data as $k => $element ) {
			$values = array(
				array(
					$element['user_login'],
					$element['display_name'],
					count( $element['ips'] ),
					implode( ', ', $element['ips'] ),
				),
			);
			$out    = '';
			foreach ( $values as $row ) {
				$quoted_data = array_map( array( $this, 'quote' ), $row );
				$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
			}
			fwrite( $file, $out );
		}
		fclose( $file );
		return $report_filename;
	}

	/**
	 * Utility method to quote the given item
	 *
	 * @internal
	 * @param mixed $data - Data.
	 * @return string
	 */
	final public function quote( $data ) {
		$data = preg_replace( '/"(.+)"/', '""$1""', $data );
		return sprintf( '"%s"', $data );
	}

	/**
	 * Get the columns by type of report.
	 *
	 * @param int $typeStatistics
	 *
	 * @return array[]
	 */
	private function _getColumns( $typeStatistics ) {
		// Logins Report
		if ( $typeStatistics == WSAL_Rep_Common::LOGIN_BY_USER || $typeStatistics == WSAL_Rep_Common::LOGIN_BY_ROLE ) {
			$columns = array(
				array(
					esc_html__('Date', 'wp-security-audit-log'),
					esc_html__('Number of Logins', 'wp-security-audit-log'),
				),
			);
		}
		// Views Report
		if ( $typeStatistics == WSAL_Rep_Common::VIEWS_BY_USER || $typeStatistics == WSAL_Rep_Common::VIEWS_BY_ROLE ) {
			$columns = array(
				array(
					esc_html__('Date', 'wp-security-audit-log'),
					esc_html__('Views', 'wp-security-audit-log'),
				),
			);
		}
		// Published content Report
		if ( $typeStatistics == WSAL_Rep_Common::PUBLISHED_BY_USER || $typeStatistics == WSAL_Rep_Common::PUBLISHED_BY_ROLE ) {
			$columns = array(
				array(
					esc_html__('Date', 'wp-security-audit-log'),
					esc_html__('Published', 'wp-security-audit-log'),
				),
			);
		}
		return $columns;
	}

	/**
	 * Write the rows of the file.
	 *
	 * @param string $file
	 * @param array $data
	 * @param int $typeStatistics
	 * @param string $delim (Optional) Delimiter.
	 */
	private function _writeRows( $file, $data, $typeStatistics, $delim ) {
		$temp_data = array();
		// Logins Report
		if ( $typeStatistics == WSAL_Rep_Common::LOGIN_BY_USER || $typeStatistics == WSAL_Rep_Common::LOGIN_BY_ROLE ) {
			foreach ( $data as $entry ) {
				$entry_date = $this->getFormattedDate( $entry['timestamp'] );
				if ( ! isset( $temp_data[ $entry_date ] ) ) {
					$temp_data[ $entry_date ] = array(
						'count' => 1,
					);
				} else {
					$temp_data[ $entry_date ]['count']++;
				}
			}
			foreach ( $temp_data as $date => $element ) {
				$values = array(
					array( $date, $element['count'] ),
				);
				$out    = '';
				foreach ( $values as $row ) {
					$quoted_data = array_map( array( $this, 'quote' ), $row );
					$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
				}
				fwrite( $file, $out );
			}
		}
		// Views Report
		if ( $typeStatistics == WSAL_Rep_Common::VIEWS_BY_USER || $typeStatistics == WSAL_Rep_Common::VIEWS_BY_ROLE ) {
			foreach ( $data as $entry ) {
				$entry_date = $this->getFormattedDate( $entry['timestamp'] );
				switch ( $entry['alert_id'] ) {
					case '2101':
						if ( ! empty( $temp_data[ $entry_date ]['posts'] ) ) {
							$temp_data[ $entry_date ]['posts'] += 1;
						} else {
							$temp_data[ $entry_date ]['posts'] = 1;
						}
						break;
					case '2103':
						if ( ! empty( $temp_data[ $entry_date ]['pages'] ) ) {
							$temp_data[ $entry_date ]['pages'] += 1;
						} else {
							$temp_data[ $entry_date ]['pages'] = 1;
						}
						break;
					case '2105':
						if ( ! empty( $temp_data[ $entry_date ]['custom'] ) ) {
							$temp_data[ $entry_date ]['custom'] += 1;
						} else {
							$temp_data[ $entry_date ]['custom'] = 1;
						}
						break;
					default:
						//  fallback for any other alerts would go here
						break;
				}
			}
			foreach ( $temp_data as $date => $element ) {
				$values = array(
					array( $date, '' ),
					array( 'posts', ! empty( $element['posts'] ) ? $element['posts'] : 0 ),
					array( 'pages', ! empty( $element['pages'] ) ? $element['pages'] : 0 ),
					array( 'custom', ! empty( $element['custom'] ) ? $element['custom'] : 0 ),
				);
				$out    = '';
				foreach ( $values as $row ) {
					$quoted_data = array_map( array( $this, 'quote' ), $row );
					$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
				}
				fwrite( $file, $out );
			}
		}
		// Published content Report
		if ( $typeStatistics == WSAL_Rep_Common::PUBLISHED_BY_USER || $typeStatistics == WSAL_Rep_Common::PUBLISHED_BY_ROLE ) {
			foreach ( $data as $entry ) {
				$entry_date = $this->getFormattedDate( $entry['timestamp'] );
				switch ( $entry['alert_id'] ) {
					case '2001':
						if ( ! empty( $temp_data[ $entry_date ]['posts'] ) ) {
							$temp_data[ $entry_date ]['posts'] += 1;
						} else {
							$temp_data[ $entry_date ]['posts'] = 1;
						}
						break;
					case '2005':
						if ( ! empty( $temp_data[ $entry_date ]['pages'] ) ) {
							$temp_data[ $entry_date ]['pages'] += 1;
						} else {
							$temp_data[ $entry_date ]['pages'] = 1;
						}
						break;
					case '2030':
						if ( ! empty( $temp_data[ $entry_date ]['custom'] ) ) {
							$temp_data[ $entry_date ]['custom'] += 1;
						} else {
							$temp_data[ $entry_date ]['custom'] = 1;
						}
						break;
					case '9001':
						if ( ! empty( $temp_data[ $entry_date ]['woocommerce'] ) ) {
							$temp_data[ $entry_date ]['woocommerce'] += 1;
						} else {
							$temp_data[ $entry_date ]['woocommerce'] = 1;
						}
						break;
					default:
						//  fallback for any other alerts would go here
						break;
				}
			}
			foreach ( $temp_data as $date => $element ) {
				$values = array(
					array( $date, '' ),
					array( 'posts', ! empty( $element['posts'] ) ? $element['posts'] : 0 ),
					array( 'pages', ! empty( $element['pages'] ) ? $element['pages'] : 0 ),
					array( 'custom', ! empty( $element['custom'] ) ? $element['custom'] : 0 ),
					array( 'woocommerce', ! empty( $element['woocommerce'] ) ? $element['woocommerce'] : 0 ),
				);
				$out    = '';
				foreach ( $values as $row ) {
					$quoted_data = array_map( array( $this, 'quote' ), $row );
					$out        .= sprintf( "%s\n", implode( $delim, $quoted_data ) );
				}
				fwrite( $file, $out );
			}
		}
	}
}
