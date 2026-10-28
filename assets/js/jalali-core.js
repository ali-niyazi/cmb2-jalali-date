(function (root, factory) {
	'use strict';

	if (typeof module === 'object' && module.exports) {
		module.exports = factory();
	} else {
		root.CMB2JalaliDate = factory();
	}
}(typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	var BREAKS = [
		-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181,
		1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178
	];

	function div(a, b) {
		var value = a / b;
		return value < 0 ? Math.ceil(value) : Math.floor(value);
	}

	function mod(a, b) {
		return a - div(a, b) * b;
	}

	function normalizeDigits(value) {
		var map = {
			'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
			'۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
			'٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
			'٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
		};

		return String(value == null ? '' : value).replace(/[۰-۹٠-٩]/g, function (digit) {
			return map[digit] || digit;
		});
	}

	function jalCal(jy, withoutLeap) {
		var bl = BREAKS.length;
		var gy = jy + 621;
		var leapJ = -14;
		var jp = BREAKS[0];
		var jump = 0;
		var jm;
		var n;
		var leapG;
		var march;
		var leap;
		var i;

		if (jy < jp || jy >= BREAKS[bl - 1]) {
			throw new RangeError('Jalali year is outside the supported range.');
		}

		for (i = 1; i < bl; i += 1) {
			jm = BREAKS[i];
			jump = jm - jp;
			if (jy < jm) {
				break;
			}
			leapJ += div(jump, 33) * 8 + div(mod(jump, 33), 4);
			jp = jm;
		}

		n = jy - jp;
		leapJ += div(n, 33) * 8 + div(mod(n, 33) + 3, 4);

		if (mod(jump, 33) === 4 && jump - n === 4) {
			leapJ += 1;
		}

		leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
		march = 20 + leapJ - leapG;

		if (withoutLeap) {
			return { gy: gy, march: march };
		}

		if (jump - n < 6) {
			n = n - jump + div(jump + 4, 33) * 33;
		}

		leap = mod(mod(n + 1, 33) - 1, 4);
		if (leap === -1) {
			leap = 4;
		}

		return { leap: leap, gy: gy, march: march };
	}

	function g2d(gy, gm, gd) {
		var d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4)
			+ div(153 * mod(gm + 9, 12) + 2, 5)
			+ gd - 34840408;
		d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
		return d;
	}

	function d2g(jdn) {
		var j = 4 * jdn + 139361631;
		var i;
		var gd;
		var gm;
		var gy;

		j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
		i = div(mod(j, 1461), 4) * 5 + 308;
		gd = div(mod(i, 153), 5) + 1;
		gm = mod(div(i, 153), 12) + 1;
		gy = div(j, 1461) - 100100 + div(8 - gm, 6);

		return { gy: gy, gm: gm, gd: gd };
	}

	function j2d(jy, jm, jd) {
		var result = jalCal(jy, true);
		return g2d(result.gy, 3, result.march)
			+ (jm - 1) * 31
			- div(jm, 7) * (jm - 7)
			+ jd - 1;
	}

	function d2j(jdn) {
		var gregorian = d2g(jdn);
		var jy = gregorian.gy - 621;
		var result = jalCal(jy, false);
		var jdn1f = g2d(gregorian.gy, 3, result.march);
		var k = jdn - jdn1f;

		if (k >= 0) {
			if (k <= 185) {
				return { jy: jy, jm: 1 + div(k, 31), jd: mod(k, 31) + 1 };
			}
			k -= 186;
		} else {
			jy -= 1;
			k += 179;
			if (result.leap === 1) {
				k += 1;
			}
		}

		return { jy: jy, jm: 7 + div(k, 30), jd: mod(k, 30) + 1 };
	}

	function isLeapYear(year) {
		try {
			return jalCal(Number(year), false).leap === 0;
		} catch (error) {
			return false;
		}
	}

	function monthLength(year, month) {
		year = Number(year);
		month = Number(month);

		if (month < 1 || month > 12) {
			return 0;
		}
		if (month <= 6) {
			return 31;
		}
		if (month <= 11) {
			return 30;
		}
		return isLeapYear(year) ? 30 : 29;
	}

	function isValid(year, month, day) {
		year = Number(year);
		month = Number(month);
		day = Number(day);

		if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
			return false;
		}
		if (year < -61 || year > 3177 || month < 1 || month > 12 || day < 1) {
			return false;
		}
		return day <= monthLength(year, month);
	}

	function parse(value) {
		var normalized = normalizeDigits(value)
			.replace(/[\u061C\u200E\u200F]/g, '')
			.trim();
		var match = /^(\d{1,4})\/(\d{1,2})\/(\d{1,2})$/.exec(normalized);
		var year;
		var month;
		var day;

		if (!match) {
			return null;
		}

		year = Number(match[1]);
		month = Number(match[2]);
		day = Number(match[3]);

		if (!isValid(year, month, day)) {
			return null;
		}

		return { year: year, month: month, day: day };
	}

	function toGregorian(year, month, day) {
		if (!isValid(year, month, day)) {
			throw new RangeError('Invalid Jalali date.');
		}
		var result = d2g(j2d(Number(year), Number(month), Number(day)));
		return { year: result.gy, month: result.gm, day: result.gd };
	}

	function isValidGregorian(year, month, day) {
		var date = new Date(Date.UTC(year, month - 1, day));
		return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
	}

	function toJalali(year, month, day) {
		year = Number(year);
		month = Number(month);
		day = Number(day);

		if (!isValidGregorian(year, month, day)) {
			throw new RangeError('Invalid Gregorian date.');
		}

		var result = d2j(g2d(year, month, day));
		return { year: result.jy, month: result.jm, day: result.jd };
	}

	function pad(value) {
		return String(value).padStart(2, '0');
	}

	function format(date) {
		return String(date.year).padStart(4, '0') + '/' + pad(date.month) + '/' + pad(date.day);
	}

	return {
		normalizeDigits: normalizeDigits,
		parse: parse,
		isValid: isValid,
		isLeapYear: isLeapYear,
		monthLength: monthLength,
		toGregorian: toGregorian,
		toJalali: toJalali,
		format: format
	};
}));
