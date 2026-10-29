# CMB2 Jalali Date

Standalone Jalali/Persian date-field extension for CMB2 with canonical Unix timestamp storage.

**Development status:** `0.7.0-dev`


## Requirements

- WordPress with CMB2 installed and active
- PHP 7.4 or newer

The plugin does not currently enforce a minimum CMB2 version. Automated integration testing currently runs against CMB2 2.13.5.

## Installation

1. Install and activate CMB2.
2. Copy or clone this plugin into `wp-content/plugins/cmb2-jalali-date`.
3. Activate **CMB2 Jalali Date** in WordPress.
4. Register fields using the `jalali_date_timestamp` field type.

## Field type

```php
$cmb->add_field(
    array(
        'name' => 'Jalali date',
        'id'   => 'jalali_date',
        'type' => 'jalali_date_timestamp',
    )
);
```

The field accepts a Jalali date in `Y/m/d` form and stores a Unix timestamp:

```text
1405/01/01 → 2026-03-21 → 1774051200
```

Persian and Arabic-Indic input digits are normalized before validation.


For programmatic integrations, an integer value is treated as an already-canonical Unix timestamp. String values are always parsed as Jalali `Y/m/d` input.

## Storage contract

```text
Jalali input / display
        ↕
strict validation + conversion
        ↕
canonical Unix timestamp
```

The extension does not store Persian-formatted dates as canonical meta values and does not create custom database tables.

Each accepted Jalali date is converted to its corresponding Gregorian calendar date and stored as the Unix timestamp for `00:00:00 UTC` on that date. Conversion back to Jalali display values also uses UTC, so the date does not shift with the WordPress site timezone.

## Repeatable fields

The field type can be used with CMB2 repeatable fields. Valid submitted dates are converted to Unix timestamps while preserving their order. If any non-empty submitted date is invalid, the previous stored repeatable value is preserved.

```php
$cmb->add_field(
    array(
        'name'       => 'Jalali dates',
        'id'         => 'jalali_dates',
        'type'       => 'jalali_date_timestamp',
        'repeatable' => true,
    )
);
```

## Group fields

The field type can also be used inside CMB2 repeatable groups. Each group subfield stores the corresponding Jalali date as a Unix timestamp.

```php
$group_id = $cmb->add_field(
    array(
        'id'   => 'events',
        'type' => 'group',
    )
);

$cmb->add_group_field(
    $group_id,
    array(
        'name' => 'Jalali date',
        'id'   => 'jalali_date',
        'type' => 'jalali_date_timestamp',
    )
);
```

## Field options

```php
$cmb->add_field(
    array(
        'name'              => 'Jalali date',
        'id'                => 'jalali_date',
        'type'              => 'jalali_date_timestamp',
        'jalali_digits'     => 'locale', // locale | persian | latin.
        'jalali_picker'     => true,
        'jalali_year_range' => 100,      // Radius around the active year.
        'jalali_show_today' => true,
        'jalali_show_clear' => true,
    )
);
```

### `jalali_digits`

- `locale` — default. `fa_IR` uses Persian digits; other locales use Latin digits.
- `persian` — always display Persian digits.
- `latin` — always display Latin digits.

Digit presentation never changes the stored timestamp.

### `jalali_picker`

Set to `false` to disable the popup calendar while preserving client-side and server-side date validation.

### `jalali_year_range`

Controls the year-selector radius around the currently active year. Values are clamped to `1..500`. It is a UI range, not a server-side minimum/maximum date constraint.

### `jalali_show_today` / `jalali_show_clear`

Show or hide the corresponding picker actions.

## Implemented behavior

- strict Jalali validation
- Persian and Arabic-Indic digit normalization
- Jalali ↔ Gregorian conversion
- canonical Unix timestamp storage
- repeatable CMB2 fields
- repeatable CMB2 groups
- dynamic initialization after `cmb2_add_row`
- dependency-free Jalali picker
- RTL-aware navigation
- keyboard navigation and focus management
- responsive picker positioning
- inline accessible validation errors
- server-side validation fallback notices
- locale-aware digit presentation
- WordPress text-domain based localization

## Localization

Text domain: `cmb2-jalali-date`

The plugin ships with:

- `languages/cmb2-jalali-date.pot`
- `languages/cmb2-jalali-date-fa_IR.po`
- `languages/cmb2-jalali-date-fa_IR.mo`

The source strings remain English and the Persian UI is supplied through the normal WordPress translation system.

## Deliberately not in the v1 API yet

The following are deferred until matching server-side semantics and tests exist:

- arbitrary `date_format`
- minimum/maximum dates
- disabled-date rules
- datetime/time fields
- date ranges

## Validation

Invalid dates such as `1404/12/30`, month `13`, or day `32` are rejected rather than rolled forward silently.

Client-side validation improves UX, but PHP validation remains authoritative so values submitted without JavaScript cannot bypass date integrity checks.

## Testing

### Automated testing

- PHPUnit unit test suite
- CI compatibility matrix: PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5
- Jalali ↔ Gregorian conversion
- Persian and Arabic-Indic digit normalization
- leap and non-leap Esfand boundaries
- invalid date rejection
- stored timestamp → Jalali display round-trip
- WordPress + CMB2 2.13.5 integration smoke test in GitHub Actions
- canonical Unix timestamp post-meta storage
- invalid scalar field submissions preserve the previous valid value
- repeatable field storage and ordering
- repeatable group storage
- clearing a field removes its stored post meta

### Manual browser testing

- RTL/LTR picker interaction
- manual-input-only mode
- compact picker interaction
- clear action
- newly added repeatable rows
- newly added group rows
- save/reload persistence
- Persian-digit input with Latin-digit display mode

### Local static checks

- PHP syntax checks
- JavaScript syntax checks
