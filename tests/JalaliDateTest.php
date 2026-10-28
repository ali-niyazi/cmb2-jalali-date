<?php
use PHPUnit\Framework\TestCase;

class CMB2_Jalali_Date_Test extends TestCase {

	public function test_normalizes_persian_and_arabic_digits() {
		$this->assertSame( '1405/01/01', CMB2_Jalali_Date::normalize_digits( '۱۴۰۵/۰۱/۰۱' ) );
		$this->assertSame( '1405/01/01', CMB2_Jalali_Date::normalize_digits( '١٤٠٥/٠١/٠١' ) );
	}

	public function test_converts_ascii_digits_to_persian_for_display() {
		$this->assertSame( '۱۴۰۵/۰۱/۰۱', CMB2_Jalali_Date::to_persian_digits( '1405/01/01' ) );
	}

	public function test_nowruz_conversion() {
		$this->assertSame(
			array( 'year' => 2026, 'month' => 3, 'day' => 21 ),
			CMB2_Jalali_Date::to_gregorian( 1405, 1, 1 )
		);
		$this->assertSame(
			array( 'year' => 1405, 'month' => 1, 'day' => 1 ),
			CMB2_Jalali_Date::to_jalali( 2026, 3, 21 )
		);
	}

	public function test_timestamp_matches_cmb2_baseline_date() {
		$this->assertSame( 1774051200, CMB2_Jalali_Date::to_timestamp( '1405/01/01' ) );
		$this->assertSame( '1405/01/01', CMB2_Jalali_Date::from_timestamp( 1774051200 ) );
	}

	public function test_persian_digit_input_converts_to_timestamp() {
		$this->assertSame( 1774051200, CMB2_Jalali_Date::to_timestamp( '۱۴۰۵/۰۱/۰۱' ) );
	}

	public function test_field_formatter_can_present_persian_or_latin_digits() {
		$this->assertSame( '1405/01/01', CMB2_Jalali_Date_Field::format_stored_value( 1774051200, 'latin' ) );
		$this->assertSame( '۱۴۰۵/۰۱/۰۱', CMB2_Jalali_Date_Field::format_stored_value( 1774051200, 'persian' ) );
	}

	public function test_rejects_invalid_dates() {
		$this->assertFalse( CMB2_Jalali_Date::to_timestamp( '1404/12/30' ) );
		$this->assertFalse( CMB2_Jalali_Date::to_timestamp( '1405/13/01' ) );
		$this->assertFalse( CMB2_Jalali_Date::to_timestamp( '1405/01/32' ) );
		$this->assertFalse( CMB2_Jalali_Date::to_timestamp( 'not-a-date' ) );
	}

	public function test_accepts_esfand_30_in_a_leap_year() {
		$this->assertTrue( CMB2_Jalali_Date::is_valid_jalali_date( 1403, 12, 30 ) );
		$this->assertFalse( CMB2_Jalali_Date::is_valid_jalali_date( 1404, 12, 30 ) );
	}
}
