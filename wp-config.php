<?php
# Database Configuration
define( 'DB_NAME', 'wp_hw30secure1' );
define( 'DB_USER', 'hw30secure1' );
define( 'DB_PASSWORD', 'rkC_VBBUbCChsXyR0VxJ' );
define( 'DB_HOST', '127.0.0.1:3306' );
define( 'DB_HOST_SLAVE', '127.0.0.1:3306' );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_unicode_ci');
$table_prefix = 'wp_';

# Security Salts, Keys, Etc
define('AUTH_KEY',         'nVs4+g#-=I|y#+m^OgYw`uv{Ksn7UBLyEBL6@cUt,9;NUcPqAvoO-pe,<<1&DbVr');
define('SECURE_AUTH_KEY',  't&g<JftK</7x5tTz&V_F*BF6 WhmhL4xBia_7qreg@S$HbaU4_oRP>.j%=aSQ9/-');
define('LOGGED_IN_KEY',    '8QM4^FjO C78?!B=dgS#4E.P6*9*^ZChSFywoBZBw`^`pm%t+3OyYvQ&P#t_-53&');
define('NONCE_KEY',        'U2mX7UhoJs?QRiTpWNa;nV--8QjJ6T~$EpCarzY+WEm2Hm[:t%gj1EoaFLE^M/il');
define('AUTH_SALT',        '~pUV*%yKVRO,sia9587V@Hy?rk:DJL8,yMHZ.Mt!aw=ln&sR@|&4qhb1%R5/psMj');
define('SECURE_AUTH_SALT', 'AW}R6Eglby(v3@(DV3 B|_u}j~niu>)TJX(k^FH(.xyo*btFB:-*+nGnZ#*)bwD~');
define('LOGGED_IN_SALT',   'jAn/+Np$x@N/ ATLa67IV[61nHU`ysh<fy;-rcq5YThhOZc<zDvv:6K2gu`gQ #{');
define('NONCE_SALT',       's-.35v|H?~+{+3]2Y$|{3?E#aYFc1%C<HdCbl#:~O3*9)LD-eRnFB#9GpGtS75Xs');


# Localized Language Stuff

define( 'WP_CACHE', TRUE );

define( 'WP_AUTO_UPDATE_CORE', false );

define( 'PWP_NAME', 'hw30secure1' );

define( 'FS_METHOD', 'direct' );

define( 'FS_CHMOD_DIR', 0775 );

define( 'FS_CHMOD_FILE', 0664 );

umask(0002);

define( 'WPE_APIKEY', '3c8dfda0d230e6a150a9b3e330674f38c1f87dd7' );

define( 'WPE_CLUSTER_ID', '140829' );

define( 'WPE_CLUSTER_TYPE', 'pod' );

define( 'WPE_ISP', true );

define( 'WPE_BPOD', false );

define( 'WPE_RO_FILESYSTEM', false );

define( 'WPE_LARGEFS_BUCKET', 'largefs.wpengine' );

define( 'WPE_SFTP_PORT', 2222 );

define( 'WPE_SFTP_ENDPOINT', '' );

define( 'WPE_LBMASTER_IP', '' );

define( 'WPE_CDN_DISABLE_ALLOWED', true );

define( 'DISALLOW_FILE_MODS', true );

define( 'DISALLOW_FILE_EDIT', true );

define( 'DISABLE_WP_CRON', false );

define( 'WPE_FORCE_SSL_LOGIN', false );

define( 'FORCE_SSL_LOGIN', false );

/*SSLSTART*/ if ( isset($_SERVER['HTTP_X_WPE_SSL']) && $_SERVER['HTTP_X_WPE_SSL'] ) $_SERVER['HTTPS'] = 'on'; /*SSLEND*/

define( 'WPE_EXTERNAL_URL', false );

define( 'WP_POST_REVISIONS', FALSE );

define( 'WPE_WHITELABEL', 'wpengine' );

define( 'WP_TURN_OFF_ADMIN_BAR', false );

define( 'WPE_BETA_TESTER', false );

$wpe_cdn_uris=array ( );

$wpe_no_cdn_uris=array ( );

$wpe_content_regexs=array ( );

$wpe_all_domains=array ( 0 => 'hw30secure1.wpengine.com', );

$wpe_varnish_servers=array ( 0 => 'pod-140829', );

$wpe_special_ips=array ( 0 => '34.123.21.228', );

$wpe_netdna_domains=array ( );

$wpe_netdna_domains_secure=array ( );

$wpe_netdna_push_domains=array ( );

$wpe_domain_mappings=array ( );

$memcached_servers=array ( 'default' =>  array ( 0 => 'unix:///tmp/memcached.sock', ), );
define('WPLANG','');

# WP Engine ID


# WP Engine Settings






# That's It. Pencils down
if ( !defined('ABSPATH') )
	define('ABSPATH', __DIR__ . '/');
require_once(ABSPATH . 'wp-settings.php');
