<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '4d617274');

/** MySQL hostname */
define('DB_HOST', 'clouddbserver01.cfiaiwxqrnzf.eu-west-1.rds.amazonaws.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'WSNzl1TXQ263Z>CN}BCZQD+SfiJ-uyl,-3p!D*-U%xZ)D-~/yi=/./_Sn_INs:_E');
define('SECURE_AUTH_KEY',  '<H^RY%|rndlgxK-kDk%)Bch-J{y^!2BLk{cV`jgUG^r`<f|a&PtV|RVwH/-dD~|<');
define('LOGGED_IN_KEY',    '&N!v$jKm+qE[Z@qEW?C>SdYeuY`41srX.wYGG#c6-k 5j7Z:a/^>mgl=%2XZf;M=');
define('NONCE_KEY',        'v^aXf-v=:+4KgoG:X4#1D$dSBdIuP5_>8mgz[GBn2< q|sSpl?fAEEfq-nrHYYEw');
define('AUTH_SALT',        'oH8}rhr|%]S&di 5K_4%vfEIG2Ye5L!$O?uyQls>l(7)#(S|P9F^w:i6x;  l&fu');
define('SECURE_AUTH_SALT', 'oI:CORh{&N>6)KZ3(]lq%cYvJ.GuGv0.i}.wdPWW2>FXUM||}~s0GE^XVi|.H|hB');
define('LOGGED_IN_SALT',   'Q-A%06=t3AmKSI<04L{{YO8 )m7-#Z|[K<nA=?vVfSUvZ9@|{01oPjL9G4N#p{_V');
define('NONCE_SALT',       'Z;=n#ib3OLUs|L c&Ah9a:X;dmg#BnHuFHxiJ*Z>&-PFE?diRGD:7IgJs:8-RXD?');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

