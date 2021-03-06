<?php 
/*
 * Plugin Name: biciteca wp plugin
 * Version: 0.8.0
 * Plugin URI: http://spatialcollective.com/
 * Description: Biciteca Management Dashboard.
 * Author: Spatial Collective in colaboration with KDI
 * Author URI: http://spatialcollective.com/
 * Requires at least: 3.8
 * Tested up to: 4.0
 *
 * Text Domain: biciteca
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Spatial Collective in colaboration with KDI
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
define("SCRIPT_DEBUG", true);

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once(ABSPATH .'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/template.php' );

require_once( 'includes/lib/twilio/Services/Twilio.php' );
require_once( 'includes/class-biciteca.php' );
require_once( 'includes/class-biciteca-settings.php' );
require_once( 'includes/lib/class-biciteca-post-type.php' );
require_once( 'includes/lib/class-biciteca-admin-api.php' );
require_once( 'includes/lib/class-biciteca-list-table.php' );
require_once( 'includes/lib/class-biciteca-sms.php' );
require_once( 'includes/lib/class-biciteca-data-logger.php' );

function biciteca () {
	$instance = biciteca::instance(__FILE__, '0.1.0');
	
	if ( is_null( $instance->settings ) ) {
		$instance->settings = biciteca_Settings::instance( $instance );
	}
	return $instance;
}

biciteca();

 ?>
