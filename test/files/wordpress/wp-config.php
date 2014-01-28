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
define('DB_NAME', 'wordpress_database_name');

/** MySQL database username */
define('DB_USER', 'wordpress_database_user');

/** MySQL database password */
define('DB_PASSWORD', 'wordpress_database_password');

/** MySQL hostname */
define('DB_HOST', 'wordpress_database_host');

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
define('AUTH_KEY',         '=hhA3&0Vs<Z#Uf>qU^YcF>cAtnAT-0lO8_r{(6zL;Ib&U)!u+2`?.C3%aT:pDibM');
define('SECURE_AUTH_KEY',  'fCG?q~(4~Q+G+>r63.sXaSO+g;=p1R@aG@wN|Z(_|)cTFm jT(>@X%[Q=T-sX.Te');
define('LOGGED_IN_KEY',    ' $y{EZqI::#vqK.uT?HzS_.HA$4>DN`X0gVtYjFUfeP*slM6Cn=aSLyXe|+fo9re');
define('NONCE_KEY',        'nRyd],.eX]j0^ex]jMz8q6L)m/VhTZgqf|_9ZY9L@2oY[lqe6w*+ye$[nv%ttMzZ');
define('AUTH_SALT',        'Il#B9VE.uxMgGcgQxx$Nfby|K/N I4YQf!j;[:{yxGD4?d%_r/%[Ux$z4MAB}c`p');
define('SECURE_AUTH_SALT', 'i&9,*1yg6N/OgM]t%W!{9Ai$5Yo!>GF*E+#e+{DqzawBTfRl9K8+*1P`YxAvlY[H');
define('LOGGED_IN_SALT',   '#Xq`Q[wc/;RV5@9BdDXEhh#Bb`#JBz5dl<qBdq<4F$=s)*d=V?3*BGu(gTdmtT6v');
define('NONCE_SALT',       'R8e-DZ*2]cH97`yWI=!r}Xxyjih8VI{s-#o9K23oW8&/BD_x^maaQnDwRv`Zw.z@');

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
