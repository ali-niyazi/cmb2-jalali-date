<?php

function cmb2_jalali_dependency_fail( $message ) {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

if ( function_exists( 'new_cmb2_box' ) || class_exists( 'CMB2' ) ) {
	cmb2_jalali_dependency_fail( 'CMB2 must not be active for this test.' );
}

if ( ! class_exists( 'CMB2_Jalali_Date' ) || ! class_exists( 'CMB2_Jalali_Date_Field' ) ) {
	cmb2_jalali_dependency_fail( 'CMB2 Jalali Date did not load without CMB2.' );
}

if ( false === has_action(
	'admin_notices',
	array( 'CMB2_Jalali_Date_Field', 'dependency_notice' )
) ) {
	cmb2_jalali_dependency_fail( 'Dependency notice callback is not registered.' );
}

ob_start();
CMB2_Jalali_Date_Field::dependency_notice();
$notice = ob_get_clean();

if ( false === strpos(
	$notice,
	'CMB2 Jalali Date requires CMB2 to be installed and active.'
) ) {
	cmb2_jalali_dependency_fail( 'Expected dependency notice was not rendered.' );
}

fwrite( STDOUT, "PASS: plugin loads safely and reports the missing CMB2 dependency.\n" );
