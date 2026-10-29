<?php
/**
 * CMB2 Jalali Date integration smoke test.
 */

function cmb2_jalali_integration_fail( $message ) {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function cmb2_jalali_integration_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		cmb2_jalali_integration_fail(
			$message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . '.'
		);
	}
}

if ( ! function_exists( 'new_cmb2_box' ) ) {
	cmb2_jalali_integration_fail( 'CMB2 is not active.' );
}

if ( ! class_exists( 'CMB2_Jalali_Date' ) || ! class_exists( 'CMB2_Jalali_Date_Field' ) ) {
	cmb2_jalali_integration_fail( 'CMB2 Jalali Date is not active.' );
}

$post_id = wp_insert_post(
	array(
		'post_title'  => 'CMB2 Jalali Date Integration Test',
		'post_status' => 'draft',
		'post_type'   => 'post',
	),
	true
);

if ( is_wp_error( $post_id ) ) {
	cmb2_jalali_integration_fail( 'Could not create integration-test post: ' . $post_id->get_error_message() );
}

try {
	$cmb = new_cmb2_box(
		array(
			'id'           => 'cmb2_jalali_date_integration',
			'title'        => 'CMB2 Jalali Date Integration',
			'object_types' => array( 'post' ),
		)
	);

	$date_key       = '_cmb2_jalali_integration_date';
	$repeatable_key = '_cmb2_jalali_integration_repeatable';
	$group_key      = '_cmb2_jalali_integration_group';

	$cmb->add_field(
		array(
			'name' => 'Jalali Date',
			'id'   => $date_key,
			'type' => 'jalali_date_timestamp',
		)
	);

	$cmb->add_field(
		array(
			'name'       => 'Repeatable Jalali Dates',
			'id'         => $repeatable_key,
			'type'       => 'jalali_date_timestamp',
			'repeatable' => true,
		)
	);

	$group_id = $cmb->add_field(
		array(
			'id'   => $group_key,
			'type' => 'group',
		)
	);

	$cmb->add_group_field(
		$group_id,
		array(
			'name' => 'Jalali Group Date',
			'id'   => 'jalali_date',
			'type' => 'jalali_date_timestamp',
		)
	);

	$valid_input = '1404/07/15';
	$expected_ts = CMB2_Jalali_Date::to_timestamp( $valid_input );

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$date_key => $valid_input,
		)
	);

	$raw = get_post_meta( $post_id, $date_key, true );

	cmb2_jalali_integration_assert_same(
		(string) $expected_ts,
		(string) $raw,
		'Jalali input was not stored as the expected Unix timestamp.'
	);

	cmb2_jalali_integration_assert_same(
		$valid_input,
		CMB2_Jalali_Date_Field::format_stored_value( $raw, 'latin' ),
		'Stored timestamp did not round-trip to Jalali display.'
	);

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$date_key => '1404/12/30',
		)
	);

	cmb2_jalali_integration_assert_same(
		(string) $expected_ts,
		(string) get_post_meta( $post_id, $date_key, true ),
		'Invalid Jalali input replaced the previous valid value.'
	);

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$date_key => '۱۴۰۴/۰۷/۱۵',
		)
	);

	cmb2_jalali_integration_assert_same(
		(string) $expected_ts,
		(string) get_post_meta( $post_id, $date_key, true ),
		'Persian-digit input did not normalize to the same timestamp.'
	);

	$repeatable_inputs = array(
		'1404/01/01',
		'1404/06/15',
		'1404/12/29',
	);

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$repeatable_key => $repeatable_inputs,
		)
	);

	$repeatable_expected = array_map(
		array( 'CMB2_Jalali_Date', 'to_timestamp' ),
		$repeatable_inputs
	);

	$repeatable_raw = get_post_meta( $post_id, $repeatable_key, true );

	cmb2_jalali_integration_assert_same(
		array_map( 'strval', $repeatable_expected ),
		array_map( 'strval', (array) $repeatable_raw ),
		'Repeatable Jalali values were not stored as timestamps in order.'
	);

	$group_inputs = array(
		array( 'jalali_date' => '1403/12/30' ),
		array( 'jalali_date' => '1404/01/01' ),
	);

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$group_key => $group_inputs,
		)
	);

	$group_raw = get_post_meta( $post_id, $group_key, true );

	if ( ! is_array( $group_raw ) || 2 !== count( $group_raw ) ) {
		cmb2_jalali_integration_fail( 'Group Jalali values were not stored as two rows.' );
	}

	cmb2_jalali_integration_assert_same(
		(string) CMB2_Jalali_Date::to_timestamp( '1403/12/30' ),
		(string) $group_raw[0]['jalali_date'],
		'First group Jalali value was not stored as a timestamp.'
	);

	cmb2_jalali_integration_assert_same(
		(string) CMB2_Jalali_Date::to_timestamp( '1404/01/01' ),
		(string) $group_raw[1]['jalali_date'],
		'Second group Jalali value was not stored as a timestamp.'
	);

	$cmb->save_fields(
		$post_id,
		'post',
		array(
			$date_key => '',
		)
	);

	if ( metadata_exists( 'post', $post_id, $date_key ) ) {
		cmb2_jalali_integration_fail( 'Clearing the field did not remove stored post meta.' );
	}

	echo "PASS: CMB2 Jalali Date integration smoke test completed successfully.\n";
} finally {
	wp_delete_post( $post_id, true );
}
