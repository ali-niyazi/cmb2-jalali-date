# CMB2 Jalali Date

Standalone Jalali/Persian date-field extension for CMB2 with canonical Unix timestamp storage.

**Development status:** `0.7.0-dev`

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

## Storage contract

```text
Jalali input / display
        ↕
strict validation + conversion
        ↕
canonical Unix timestamp
```

The extension does not store Persian-formatted dates as canonical meta values and does not create custom database tables.

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

## Development testing completed so far

- `1405/01/01 ↔ 2026-03-21 ↔ 1774051200`
- Persian digit input
- Arabic-Indic digit input
- leap/non-leap Esfand boundaries
- invalid month/day rejection
- clearing values
- repeatable add/delete/reorder
- group add/reorder
- RTL/LTR picker interaction
- field-level API defaults and overrides
- PHP/JavaScript syntax checks
