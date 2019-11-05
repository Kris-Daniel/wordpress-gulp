<?php
/**
 * Load Freemius module.
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @copyright (c) 2018, Webcraftic Ltd
 *
 * @package core
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'FACTORY_FREEMIUS_103_LOADED' ) ) {
	return;
}

define( 'FACTORY_FREEMIUS_103_LOADED', true );
define( 'FACTORY_FREEMIUS_103_DIR', dirname( __FILE__ ) );
define( 'FACTORY_FREEMIUS_103_URL', plugins_url( null, __FILE__ ) );

#comp merge
// Freemius
require_once( FACTORY_FREEMIUS_103_DIR . '/includes/entities/class-freemius-entity.php' );
require_once( FACTORY_FREEMIUS_103_DIR . '/includes/entities/class-freemius-scope.php' );
require_once( FACTORY_FREEMIUS_103_DIR . '/includes/entities/class-freemius-user.php' );
require_once( FACTORY_FREEMIUS_103_DIR . '/includes/entities/class-freemius-site.php' );
require_once( FACTORY_FREEMIUS_103_DIR . '/includes/entities/class-freemius-license.php' );

require_once( FACTORY_FREEMIUS_103_DIR . '/includes/licensing/class-freemius-provider.php' );

require_once( FACTORY_FREEMIUS_103_DIR . '/includes/updates/class-freemius-repository.php' );

if ( ! class_exists( 'Freemius_Api_WordPress' ) ) {
	require_once FACTORY_FREEMIUS_103_DIR . '/includes/sdk/FreemiusWordPress.php';
}

require_once( FACTORY_FREEMIUS_103_DIR . '/includes/class-freemius-api.php' );
#endcomp
