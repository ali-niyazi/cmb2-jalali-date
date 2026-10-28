<?php
/**
 * CMB2 custom field integration for Jalali timestamp dates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CMB2_Jalali_Date_Field {

	const CORE_SCRIPT_HANDLE   = 'cmb2-jalali-date-core';
	const PICKER_SCRIPT_HANDLE = 'cmb2-jalali-date-picker';
	const PICKER_STYLE_HANDLE  = 'cmb2-jalali-date-picker';

	/**
	 * Register CMB2 custom-field hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );
		add_action( 'init', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'cmb2_render_jalali_date_timestamp', array( __CLASS__, 'render' ), 10, 5 );
		add_filter( 'cmb2_sanitize_jalali_date_timestamp', array( __CLASS__, 'sanitize' ), 10, 5 );
		add_filter( 'cmb2_types_esc_jalali_date_timestamp', array( __CLASS__, 'escape' ), 10, 4 );
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'validation_notice' ) );
	}

	/**
	 * Register the bundled translation directory with WordPress.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			'cmb2-jalali-date',
			false,
			dirname( plugin_basename( CMB2_JALALI_DATE_FILE ) ) . '/languages'
		);
	}

	/**
	 * Register picker assets without enqueueing them globally.
	 *
	 * The picker handle is added to CMB2's JS dependencies only when this field
	 * type is actually rendered.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style(
			self::PICKER_STYLE_HANDLE,
			CMB2_JALALI_DATE_URL . 'assets/css/jalali-datepicker.css',
			array(),
			CMB2_JALALI_DATE_VERSION
		);

		wp_register_script(
			self::CORE_SCRIPT_HANDLE,
			CMB2_JALALI_DATE_URL . 'assets/js/jalali-core.js',
			array(),
			CMB2_JALALI_DATE_VERSION,
			true
		);

		wp_register_script(
			self::PICKER_SCRIPT_HANDLE,
			CMB2_JALALI_DATE_URL . 'assets/js/jalali-datepicker.js',
			array( self::CORE_SCRIPT_HANDLE ),
			CMB2_JALALI_DATE_VERSION,
			true
		);

		wp_localize_script(
			self::PICKER_SCRIPT_HANDLE,
			'cmb2JalaliDatePickerL10n',
			self::picker_l10n()
		);
	}

	/**
	 * Build date-picker localization data.
	 *
	 * Source strings remain English and are translated through the normal
	 * WordPress text-domain pipeline. A bundled fa_IR catalog is included for
	 * local development; WordPress.org translations can replace it later.
	 *
	 * @return array
	 */
	private static function picker_l10n() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$l10n   = array(
			'monthNames'       => array(
				__( 'Farvardin', 'cmb2-jalali-date' ),
				__( 'Ordibehesht', 'cmb2-jalali-date' ),
				__( 'Khordad', 'cmb2-jalali-date' ),
				__( 'Tir', 'cmb2-jalali-date' ),
				__( 'Mordad', 'cmb2-jalali-date' ),
				__( 'Shahrivar', 'cmb2-jalali-date' ),
				__( 'Mehr', 'cmb2-jalali-date' ),
				__( 'Aban', 'cmb2-jalali-date' ),
				__( 'Azar', 'cmb2-jalali-date' ),
				__( 'Dey', 'cmb2-jalali-date' ),
				__( 'Bahman', 'cmb2-jalali-date' ),
				__( 'Esfand', 'cmb2-jalali-date' ),
			),
			'dayNames'         => array(
				__( 'Saturday', 'cmb2-jalali-date' ),
				__( 'Sunday', 'cmb2-jalali-date' ),
				__( 'Monday', 'cmb2-jalali-date' ),
				__( 'Tuesday', 'cmb2-jalali-date' ),
				__( 'Wednesday', 'cmb2-jalali-date' ),
				__( 'Thursday', 'cmb2-jalali-date' ),
				__( 'Friday', 'cmb2-jalali-date' ),
			),
			'dayNamesMin'      => array(
				__( 'Sat', 'cmb2-jalali-date' ),
				__( 'Sun', 'cmb2-jalali-date' ),
				__( 'Mon', 'cmb2-jalali-date' ),
				__( 'Tue', 'cmb2-jalali-date' ),
				__( 'Wed', 'cmb2-jalali-date' ),
				__( 'Thu', 'cmb2-jalali-date' ),
				__( 'Fri', 'cmb2-jalali-date' ),
			),
			'prevText'         => __( 'Previous month', 'cmb2-jalali-date' ),
			'nextText'         => __( 'Next month', 'cmb2-jalali-date' ),
			'monthSelectLabel' => __( 'Select month', 'cmb2-jalali-date' ),
			'yearSelectLabel'  => __( 'Select year', 'cmb2-jalali-date' ),
			'dialogLabel'      => __( 'Choose Jalali date', 'cmb2-jalali-date' ),
			/* Translators: Keep {day}, {month}, and {year} placeholders intact. */
			'fullDateLabel'    => __( '{day} {month} {year}', 'cmb2-jalali-date' ),
			'todayText'        => __( 'Today', 'cmb2-jalali-date' ),
			'clearText'        => __( 'Clear', 'cmb2-jalali-date' ),
			'invalidDateText'  => __( 'Enter a valid Jalali date in Y/m/d format, for example 1405/01/01.', 'cmb2-jalali-date' ),
			'formatHint'       => 'Y/m/d',
			'yearRangeRadius'  => 100,
		);

		/**
		 * Filter Jalali date-picker labels and global fallback behavior.
		 *
		 * Per-field behavior such as digit style and year range is emitted as data
		 * attributes by render() and takes precedence over these global fallbacks.
		 *
		 * @param array  $l10n   Picker configuration.
		 * @param string $locale Current WordPress locale.
		 */
		return apply_filters( 'cmb2_jalali_date_picker_l10n', $l10n, $locale );
	}

	/**
	 * Render the custom field.
	 *
	 * Supported field arguments for v1:
	 * - jalali_digits: locale|persian|latin (default: locale)
	 * - jalali_picker: bool (default: true)
	 * - jalali_year_range: integer radius around the active year (default: 100)
	 * - jalali_show_today: bool (default: true)
	 * - jalali_show_clear: bool (default: true)
	 *
	 * @param CMB2_Field $field Field object.
	 * @param mixed      $escaped_value Escaped/display value.
	 * @param int        $object_id Object ID.
	 * @param string     $object_type Object type.
	 * @param CMB2_Types $field_type_object Type helper object.
	 * @return void
	 */
	public static function render( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		unset( $object_id, $object_type );

		wp_enqueue_style( self::PICKER_STYLE_HANDLE );

		$digits_mode  = self::resolve_digits_mode( $field->args( 'jalali_digits' ) );
		$picker       = self::field_bool_arg( $field, 'jalali_picker', true );
		$year_range   = self::field_int_arg( $field, 'jalali_year_range', 100, 1, 500 );
		$show_today   = self::field_bool_arg( $field, 'jalali_show_today', true );
		$show_clear   = self::field_bool_arg( $field, 'jalali_show_clear', true );
		$placeholder  = 'persian' === $digits_mode ? '۱۴۰۵/۰۱/۰۱' : '1405/01/01';

		echo $field_type_object->input( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CMB2 input renderer escapes attributes.
			array(
				'class'                  => 'cmb2-text-small cmb2-jalali-date',
				'value'                  => $escaped_value,
				'desc'                   => $field_type_object->_desc(),
				'autocomplete'           => 'off',
				'inputmode'              => 'numeric',
				'dir'                    => 'ltr',
				'placeholder'            => $placeholder,
				'data-calendar'          => 'jalali',
				'data-jalali-digits'     => $digits_mode,
				'data-jalali-picker'     => $picker ? '1' : '0',
				'data-jalali-year-range' => (string) $year_range,
				'data-jalali-show-today' => $show_today ? '1' : '0',
				'data-jalali-show-clear' => $show_clear ? '1' : '0',
				'js_dependencies'        => array( self::PICKER_SCRIPT_HANDLE ),
			)
		);
	}

	/**
	 * Convert Jalali input to canonical Unix timestamp storage.
	 *
	 * @param mixed         $override Existing override from an earlier filter.
	 * @param mixed         $value Submitted value.
	 * @param int           $object_id Object ID.
	 * @param array         $field_args Field arguments.
	 * @param CMB2_Sanitize $sanitizer Sanitizer object.
	 * @return mixed
	 */
	public static function sanitize( $override, $value, $object_id, $field_args, $sanitizer ) {
		unset( $object_id );

		if ( null !== $override ) {
			return $override;
		}

		if ( is_array( $value ) ) {
			$converted = array();
			foreach ( $value as $key => $item ) {
				if ( '' === trim( (string) $item ) ) {
					continue;
				}

				$timestamp = self::sanitize_single( $item );
				if ( false === $timestamp ) {
					self::mark_invalid_submission( $field_args );
					return $sanitizer->field->value();
				}
				$converted[ $key ] = $timestamp;
			}

			return $converted;
		}

		if ( '' === trim( (string) $value ) ) {
			return '';
		}

		$timestamp = self::sanitize_single( $value );
		if ( false === $timestamp ) {
			self::mark_invalid_submission( $field_args );
			return $sanitizer->field->value();
		}

		return $timestamp;
	}

	/**
	 * Convert stored timestamps to Jalali display strings.
	 *
	 * @param mixed      $override Existing override from an earlier filter.
	 * @param mixed      $meta_value Stored value.
	 * @param array      $field_args Field arguments.
	 * @param CMB2_Field $field Field object.
	 * @return mixed
	 */
	public static function escape( $override, $meta_value, $field_args, $field ) {
		unset( $field );

		if ( null !== $override ) {
			return $override;
		}

		$digits_mode = self::resolve_digits_mode( isset( $field_args['jalali_digits'] ) ? $field_args['jalali_digits'] : 'locale' );

		if ( is_array( $meta_value ) ) {
			return array_map(
				static function ( $value ) use ( $digits_mode ) {
					return CMB2_Jalali_Date_Field::format_stored_value( $value, $digits_mode );
				},
				$meta_value
			);
		}

		return self::format_stored_value( $meta_value, $digits_mode );
	}

	/**
	 * Resolve the configured digit presentation mode.
	 *
	 * @param mixed $mode Field option.
	 * @return string persian|latin
	 */
	private static function resolve_digits_mode( $mode ) {
		$mode = is_string( $mode ) ? strtolower( trim( $mode ) ) : 'locale';
		if ( 'persian' === $mode || 'latin' === $mode ) {
			return $mode;
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return 0 === strpos( (string) $locale, 'fa' ) ? 'persian' : 'latin';
	}

	/**
	 * Read a boolean field argument while preserving an explicit false value.
	 *
	 * @param CMB2_Field $field Field object.
	 * @param string     $key Argument key.
	 * @param bool       $default Default value.
	 * @return bool
	 */
	private static function field_bool_arg( $field, $key, $default ) {
		$args = $field->args();
		if ( ! is_array( $args ) || ! array_key_exists( $key, $args ) ) {
			return (bool) $default;
		}

		return (bool) $args[ $key ];
	}

	/**
	 * Read and clamp an integer field argument.
	 *
	 * @param CMB2_Field $field Field object.
	 * @param string     $key Argument key.
	 * @param int        $default Default value.
	 * @param int        $min Minimum value.
	 * @param int        $max Maximum value.
	 * @return int
	 */
	private static function field_int_arg( $field, $key, $default, $min, $max ) {
		$value = $field->args( $key );
		$value = is_numeric( $value ) ? (int) $value : (int) $default;
		return max( $min, min( $max, $value ) );
	}

	/**
	 * Record that an invalid value reached server-side validation.
	 *
	 * @param array $field_args Current field arguments.
	 * @return void
	 */
	private static function mark_invalid_submission( $field_args ) {
		if ( ! is_admin() || ! function_exists( 'get_current_user_id' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return;
		}

		$key    = 'cmb2_jalali_date_invalid_' . $user_id;
		$stored = get_transient( $key );
		$labels = is_array( $stored ) ? $stored : array();
		$label  = isset( $field_args['name'] ) && is_scalar( $field_args['name'] )
			? sanitize_text_field( (string) $field_args['name'] )
			: '';

		if ( '' !== $label && ! in_array( $label, $labels, true ) ) {
			$labels[] = $label;
		}

		set_transient( $key, $labels, MINUTE_IN_SECONDS );
	}

	/**
	 * Show a one-time server-side validation fallback notice.
	 *
	 * @return void
	 */
	public static function validation_notice() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return;
		}

		$key    = 'cmb2_jalali_date_invalid_' . $user_id;
		$labels = get_transient( $key );
		if ( false === $labels ) {
			return;
		}

		delete_transient( $key );

		$message = __( 'One or more invalid Jalali dates were not saved. Use Y/m/d format, for example 1405/01/01.', 'cmb2-jalali-date' );

		echo '<div class="notice notice-error is-dismissible"><p>'
			. esc_html( $message );

		if ( is_array( $labels ) && ! empty( $labels ) ) {
			echo ' <span class="screen-reader-text">'
				. esc_html( sprintf(
					/* translators: %s: comma-separated field labels. */
					__( 'Affected fields: %s', 'cmb2-jalali-date' ),
					implode( ', ', $labels )
				) )
				. '</span>';
		}

		echo '</p></div>';
	}

	/**
	 * Show a dependency notice when CMB2 is not active.
	 *
	 * @return void
	 */
	public static function dependency_notice() {
		if ( function_exists( 'new_cmb2_box' ) || class_exists( 'CMB2' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>'
			. esc_html__( 'CMB2 Jalali Date requires CMB2 to be installed and active.', 'cmb2-jalali-date' )
			. '</p></div>';
	}

	/**
	 * Sanitize one scalar input value.
	 *
	 * Integer values are treated as already-canonical timestamps (useful for
	 * programmatic/REST integrations). HTML form values arrive as strings and
	 * are always parsed as Jalali dates.
	 *
	 * @param mixed $value Input value.
	 * @return int|false
	 */
	private static function sanitize_single( $value ) {
		if ( is_int( $value ) ) {
			return $value;
		}

		return CMB2_Jalali_Date::to_timestamp( $value );
	}

	/**
	 * Format one stored value.
	 *
	 * @param mixed  $value Stored value.
	 * @param string $digits_mode persian|latin.
	 * @return string
	 */
	public static function format_stored_value( $value, $digits_mode = 'latin' ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		$formatted = CMB2_Jalali_Date::from_timestamp( $value );
		return 'persian' === $digits_mode ? CMB2_Jalali_Date::to_persian_digits( $formatted ) : $formatted;
	}
}
