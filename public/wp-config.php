<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', "eventkviz" );

/** MySQL database username */
define( 'DB_USER', "eventkviz" );

/** MySQL database password */
define( 'DB_PASSWORD', "Pn3;RC`tnc" );

/** MySQL hostname */
define( 'DB_HOST', "mariadb105.r4.websupport.sk:3315" );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '8|U^J1}>X>YM;]b#QrF)j9#?JNLjuNs{1)L)q^K9n/XFV@M@acK#UJ~:{:5_/H>Q' );
define( 'SECURE_AUTH_KEY',   'e[U~et RGm8JQK3oCoe/B]mZ Z&R4v64Ve8L5gZ?J<.3r1%r-B?rFdf~L|c? 1-d' );
define( 'LOGGED_IN_KEY',     'T.+)oAcT[gbKGciNqO^Vta~fhD4q|3wHM~E|H7.kqyf;t:1c({F<SGOqu<hWW+_H' );
define( 'NONCE_KEY',         '7=2wE.o!n&ATTaRD-bvf|ewxsu36Ot|!O?)qu3Uvj[U!$uocb&7m%=xkK7nJ!;Zs' );
define( 'AUTH_SALT',         '8!^u:Y`y.lRwm:WvWlXLPH0Pb)_%qNv,1G[@h4__EDaV{5Oi4>;PKp.%dQ.+h%2c' );
define( 'SECURE_AUTH_SALT',  '>E2`w-,D)*0@KnlZ7yRC|6I!eXuPbO4aEm.IB82|9SIm>>1v7vyX?B.J3[>vi?C,' );
define( 'LOGGED_IN_SALT',    'g`K(6KB4eGV}t84?;Igk^/DAz)=yUW&6#ie.oRy0S[Mr7oPx8##FKtV]JB29VNL;' );
define( 'NONCE_SALT',        '_q$;0HD}$Sr9Q~T2WE7c3(=.LQ8yafgqNKd0;F~}+5qWshaujnI.^6(9z-(5dv}F' );
define( 'WP_CACHE_KEY_SALT', 'c(`+F]y*$xO>MEF%P;d(d$O5E[ke29nvnR#IM8IL]D:Sz3KLY;I)d$u<|02A[uPl' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'pmgoni';


define( 'WP_SITEURL', 'http://eventkviz.sk/' );
define('WPLANG', 'sk_SK');


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname(__FILE__) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
