<?php
/**
 * Plugin Name: CMB2 Jalali Date
 * Description: Jalali/Persian date field extension for CMB2 with canonical Unix timestamp storage.
 * Version: 0.7.0-dev
 * Author: Ali Niyazi
 * License: GPL-2.0-or-later
 * Text Domain: cmb2-jalali-date
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CMB2_JALALI_DATE_VERSION', '0.7.0-dev' );
define( 'CMB2_JALALI_DATE_FILE', __FILE__ );
define( 'CMB2_JALALI_DATE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMB2_JALALI_DATE_URL', plugin_dir_url( __FILE__ ) );

require_once CMB2_JALALI_DATE_DIR . 'includes/class-jalali-date.php';
require_once CMB2_JALALI_DATE_DIR . 'includes/class-cmb2-jalali-date-field.php';

CMB2_Jalali_Date_Field::init();
