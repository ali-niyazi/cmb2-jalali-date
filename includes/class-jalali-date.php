<?php
/**
 * Jalali date parsing, validation, conversion and timestamp helpers.
 *
 * The conversion implementation follows the Borkowski break-year model used
 * by established Jalali implementations, while remaining dependency-free.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CMB2_Jalali_Date {

	/**
	 * Break years used by the conversion algorithm.
	 *
	 * @var int[]
	 */
	private const BREAKS = array(
		-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181,
		1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
	);

	/**
	 * Normalize Persian and Arabic-Indic digits to ASCII digits.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	/**
	 * Convert ASCII digits to Persian digits for display only.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function to_persian_digits( $value ) {
		return strtr(
			(string) $value,
			array(
				'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
				'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
			)
		);
	}

	public static function normalize_digits( $value ) {
		$value = (string) $value;

		return strtr(
			$value,
			array(
				'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
				'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
				'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
				'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
			)
		);
	}

	/**
	 * Parse a Jalali date string in Y/m/d form.
	 *
	 * Month and day may be one or two digits. The parsed date must be valid.
	 *
	 * @param mixed $value Input value.
	 * @return array|false Array with year/month/day or false when invalid.
	 */
	public static function parse( $value ) {
		if ( ! is_scalar( $value ) ) {
			return false;
		}

		$value = self::normalize_digits( $value );
		$value = preg_replace( '/[\x{061C}\x{200E}\x{200F}]/u', '', $value );
		$value = trim( $value );

		if ( '' === $value ) {
			return false;
		}

		if ( ! preg_match( '/^(\d{1,4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches ) ) {
			return false;
		}

		$year  = (int) $matches[1];
		$month = (int) $matches[2];
		$day   = (int) $matches[3];

		if ( ! self::is_valid_jalali_date( $year, $month, $day ) ) {
			return false;
		}

		return array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day,
		);
	}

	/**
	 * Convert a valid Jalali date string to a canonical UTC Unix timestamp.
	 *
	 * @param mixed $value Jalali date in Y/m/d form.
	 * @return int|false Timestamp or false for invalid input.
	 */
	public static function to_timestamp( $value ) {
		$parsed = self::parse( $value );
		if ( false === $parsed ) {
			return false;
		}

		$gregorian = self::to_gregorian( $parsed['year'], $parsed['month'], $parsed['day'] );
		$date       = DateTimeImmutable::createFromFormat(
			'!Y-n-j',
			sprintf( '%04d-%d-%d', $gregorian['year'], $gregorian['month'], $gregorian['day'] ),
			new DateTimeZone( 'UTC' )
		);

		return $date ? $date->getTimestamp() : false;
	}

	/**
	 * Format a stored Unix timestamp as Jalali Y/m/d.
	 *
	 * @param mixed $timestamp Unix timestamp.
	 * @return string
	 */
	public static function from_timestamp( $timestamp ) {
		if ( ! is_numeric( $timestamp ) ) {
			return '';
		}

		$timestamp = (int) $timestamp;
		$year      = (int) gmdate( 'Y', $timestamp );
		$month     = (int) gmdate( 'n', $timestamp );
		$day       = (int) gmdate( 'j', $timestamp );
		$jalali    = self::to_jalali( $year, $month, $day );

		return sprintf( '%04d/%02d/%02d', $jalali['year'], $jalali['month'], $jalali['day'] );
	}

	/**
	 * Convert Gregorian date to Jalali.
	 *
	 * @param int $year Gregorian year.
	 * @param int $month Gregorian month.
	 * @param int $day Gregorian day.
	 * @return array
	 * @throws InvalidArgumentException For invalid Gregorian dates.
	 */
	public static function to_jalali( $year, $month, $day ) {
		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;

		if ( ! checkdate( $month, $day, $year ) ) {
			throw new InvalidArgumentException( 'Invalid Gregorian date.' );
		}

		$result = self::d2j( self::g2d( $year, $month, $day ) );

		return array(
			'year'  => $result['jy'],
			'month' => $result['jm'],
			'day'   => $result['jd'],
		);
	}

	/**
	 * Convert Jalali date to Gregorian.
	 *
	 * @param int $year Jalali year.
	 * @param int $month Jalali month.
	 * @param int $day Jalali day.
	 * @return array
	 * @throws InvalidArgumentException For invalid Jalali dates.
	 */
	public static function to_gregorian( $year, $month, $day ) {
		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;

		if ( ! self::is_valid_jalali_date( $year, $month, $day ) ) {
			throw new InvalidArgumentException( 'Invalid Jalali date.' );
		}

		$result = self::d2g( self::j2d( $year, $month, $day ) );

		return array(
			'year'  => $result['gy'],
			'month' => $result['gm'],
			'day'   => $result['gd'],
		);
	}

	/**
	 * Validate a Jalali date.
	 *
	 * @param int $year Jalali year.
	 * @param int $month Jalali month.
	 * @param int $day Jalali day.
	 * @return bool
	 */
	public static function is_valid_jalali_date( $year, $month, $day ) {
		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;

		if ( $year < -61 || $year > 3177 || $month < 1 || $month > 12 || $day < 1 ) {
			return false;
		}

		return $day <= self::jalali_month_length( $year, $month );
	}

	/**
	 * Determine whether a Jalali year is leap.
	 *
	 * @param int $year Jalali year.
	 * @return bool
	 */
	public static function is_leap_jalali_year( $year ) {
		try {
			$result = self::jal_cal( (int) $year );
			return 0 === $result['leap'];
		} catch ( InvalidArgumentException $exception ) {
			return false;
		}
	}

	/**
	 * Return the number of days in a Jalali month.
	 *
	 * @param int $year Jalali year.
	 * @param int $month Jalali month.
	 * @return int
	 */
	public static function jalali_month_length( $year, $month ) {
		$year  = (int) $year;
		$month = (int) $month;

		if ( $month < 1 || $month > 12 ) {
			return 0;
		}
		if ( $month <= 6 ) {
			return 31;
		}
		if ( $month <= 11 ) {
			return 30;
		}

		return self::is_leap_jalali_year( $year ) ? 30 : 29;
	}

	/**
	 * Integer division with truncation toward zero.
	 *
	 * @param int $a Dividend.
	 * @param int $b Divisor.
	 * @return int
	 */
	private static function div_int( $a, $b ) {
		return intdiv( (int) $a, (int) $b );
	}

	/**
	 * Remainder matching truncation-toward-zero division.
	 *
	 * @param int $a Dividend.
	 * @param int $b Divisor.
	 * @return int
	 */
	private static function mod( $a, $b ) {
		return (int) $a - self::div_int( $a, $b ) * (int) $b;
	}

	/**
	 * Calculate Jalali leap and March anchor data.
	 *
	 * @param int  $jy Jalali year.
	 * @param bool $without_leap Skip leap calculation.
	 * @return array
	 * @throws InvalidArgumentException When year is outside supported range.
	 */
	private static function jal_cal( $jy, $without_leap = false ) {
		$breaks = self::BREAKS;
		$count  = count( $breaks );
		$gy     = $jy + 621;
		$leap_j = -14;
		$jp     = $breaks[0];
		$jump   = 0;

		if ( $jy < $jp || $jy >= $breaks[ $count - 1 ] ) {
			throw new InvalidArgumentException( 'Jalali year is outside the supported range.' );
		}

		for ( $i = 1; $i < $count; $i++ ) {
			$jm   = $breaks[ $i ];
			$jump = $jm - $jp;
			if ( $jy < $jm ) {
				break;
			}
			$leap_j += self::div_int( $jump, 33 ) * 8 + self::div_int( self::mod( $jump, 33 ), 4 );
			$jp      = $jm;
		}

		$n       = $jy - $jp;
		$leap_j += self::div_int( $n, 33 ) * 8 + self::div_int( self::mod( $n, 33 ) + 3, 4 );

		if ( 4 === self::mod( $jump, 33 ) && 4 === ( $jump - $n ) ) {
			$leap_j++;
		}

		$leap_g = self::div_int( $gy, 4 ) - self::div_int( ( self::div_int( $gy, 100 ) + 1 ) * 3, 4 ) - 150;
		$march  = 20 + $leap_j - $leap_g;

		if ( $without_leap ) {
			return array(
				'gy'    => $gy,
				'march' => $march,
			);
		}

		if ( ( $jump - $n ) < 6 ) {
			$n = $n - $jump + self::div_int( $jump + 4, 33 ) * 33;
		}

		$leap = self::mod( self::mod( $n + 1, 33 ) - 1, 4 );
		if ( -1 === $leap ) {
			$leap = 4;
		}

		return array(
			'leap'  => $leap,
			'gy'    => $gy,
			'march' => $march,
		);
	}

	/**
	 * Gregorian date to Julian day number.
	 *
	 * @param int $gy Gregorian year.
	 * @param int $gm Gregorian month.
	 * @param int $gd Gregorian day.
	 * @return int
	 */
	private static function g2d( $gy, $gm, $gd ) {
		$d = self::div_int( ( $gy + self::div_int( $gm - 8, 6 ) + 100100 ) * 1461, 4 )
			+ self::div_int( 153 * self::mod( $gm + 9, 12 ) + 2, 5 )
			+ $gd - 34840408;
		$d = $d - self::div_int( self::div_int( $gy + 100100 + self::div_int( $gm - 8, 6 ), 100 ) * 3, 4 ) + 752;

		return $d;
	}

	/**
	 * Julian day number to Gregorian date.
	 *
	 * @param int $jdn Julian day number.
	 * @return array
	 */
	private static function d2g( $jdn ) {
		$j  = 4 * $jdn + 139361631;
		$j  = $j + self::div_int( self::div_int( 4 * $jdn + 183187720, 146097 ) * 3, 4 ) * 4 - 3908;
		$i  = self::div_int( self::mod( $j, 1461 ), 4 ) * 5 + 308;
		$gd = self::div_int( self::mod( $i, 153 ), 5 ) + 1;
		$gm = self::mod( self::div_int( $i, 153 ), 12 ) + 1;
		$gy = self::div_int( $j, 1461 ) - 100100 + self::div_int( 8 - $gm, 6 );

		return array(
			'gy' => $gy,
			'gm' => $gm,
			'gd' => $gd,
		);
	}

	/**
	 * Jalali date to Julian day number.
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month.
	 * @param int $jd Jalali day.
	 * @return int
	 */
	private static function j2d( $jy, $jm, $jd ) {
		$result = self::jal_cal( $jy, true );

		return self::g2d( $result['gy'], 3, $result['march'] )
			+ ( $jm - 1 ) * 31
			- self::div_int( $jm, 7 ) * ( $jm - 7 )
			+ $jd - 1;
	}

	/**
	 * Julian day number to Jalali date.
	 *
	 * @param int $jdn Julian day number.
	 * @return array
	 */
	private static function d2j( $jdn ) {
		$gregorian = self::d2g( $jdn );
		$jy        = $gregorian['gy'] - 621;
		$result    = self::jal_cal( $jy );
		$jdn_1f    = self::g2d( $gregorian['gy'], 3, $result['march'] );
		$k         = $jdn - $jdn_1f;

		if ( $k >= 0 ) {
			if ( $k <= 185 ) {
				return array(
					'jy' => $jy,
					'jm' => 1 + self::div_int( $k, 31 ),
					'jd' => self::mod( $k, 31 ) + 1,
				);
			}
			$k -= 186;
		} else {
			$jy--;
			$k += 179;
			if ( 1 === $result['leap'] ) {
				$k++;
			}
		}

		return array(
			'jy' => $jy,
			'jm' => 7 + self::div_int( $k, 30 ),
			'jd' => self::mod( $k, 30 ) + 1,
		);
	}
}
