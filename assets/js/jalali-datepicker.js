(function (window, document) {
	'use strict';

	var Core = window.CMB2JalaliDate;
	var config = window.cmb2JalaliDatePickerL10n || {};
	var FIELD_SELECTOR = 'input.cmb2-jalali-date';
	var PICKER_ID = 'cmb2-jalali-datepicker';
	var activeInput = null;
	var picker = null;
	var state = null;
	var isRtl = false;
	var suppressNextFocusOpen = false;

	if (!Core) {
		return;
	}

	function activeDigitsMode(input) {
		var field = input || activeInput;
		return field && field.dataset && field.dataset.jalaliDigits === 'persian' ? 'persian' : 'latin';
	}

	function digits(value, input) {
		var string = String(value);
		if (activeDigitsMode(input) !== 'persian') {
			return string;
		}
		return string.replace(/\d/g, function (digit) {
			return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(digit));
		});
	}

	function fieldBool(input, key, fallback) {
		if (!input || !input.dataset || typeof input.dataset[key] === 'undefined') {
			return !!fallback;
		}
		return input.dataset[key] !== '0';
	}

	function pickerEnabled(input) {
		return fieldBool(input, 'jalaliPicker', true);
	}

	function fieldYearRange(input) {
		var value = input && input.dataset ? Number(input.dataset.jalaliYearRange) : NaN;
		if (!Number.isFinite(value)) {
			value = Number(config.yearRangeRadius || 100);
		}
		return Math.max(1, Math.min(500, Math.round(value)));
	}

	function todayJalali() {
		var today = new Date();
		return Core.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
	}

	function normalizeState(input) {
		var parsed = Core.parse(input.value);
		var initial = parsed || todayJalali();
		return {
			year: initial.year,
			month: initial.month,
			day: initial.day,
			selected: parsed
		};
	}

	function ensurePicker() {
		if (picker) {
			return picker;
		}

		picker = document.createElement('div');
		picker.id = PICKER_ID;
		picker.className = 'cmb2-jalali-datepicker';
		picker.setAttribute('role', 'dialog');
		picker.setAttribute('aria-modal', 'false');
		picker.setAttribute('aria-label', config.dialogLabel || 'Jalali date picker');
		picker.hidden = true;
		picker.style.position = 'absolute';
		picker.style.zIndex = '100000';
		document.body.appendChild(picker);
		return picker;
	}

	function enhanceInput(input) {
		if (pickerEnabled(input)) {
			input.setAttribute('aria-haspopup', 'dialog');
			if (!input.hasAttribute('aria-expanded')) {
				input.setAttribute('aria-expanded', 'false');
			}
			input.setAttribute('aria-controls', PICKER_ID);
		} else {
			input.removeAttribute('aria-haspopup');
			input.removeAttribute('aria-expanded');
			input.removeAttribute('aria-controls');
		}
		input.setAttribute('data-jalali-date-format', config.formatHint || 'Y/m/d');
	}


	function errorId(input) {
		var base = input.id || input.name || 'cmb2-jalali-date';
		return String(base).replace(/[^A-Za-z0-9_-]/g, '-') + '-jalali-error';
	}

	function getErrorElement(input) {
		var id = errorId(input);
		var error = input.nextElementSibling;

		// CMB2 may clone repeatable/group rows. Reuse an adjacent cloned
		// validation element, but always assign the ID derived from the current
		// input so aria-describedby remains unique after cloning.
		if (!error || !error.classList.contains('cmb2-jalali-date__error')) {
			error = document.createElement('span');
			error.className = 'cmb2-jalali-date__error';
			error.setAttribute('role', 'alert');
			error.hidden = true;
			input.insertAdjacentElement('afterend', error);
		}

		error.id = id;
		return error;
	}

	function describedByTokens(input) {
		return (input.getAttribute('aria-describedby') || '')
			.split(/\s+/)
			.filter(Boolean);
	}

	function setValidationState(input, valid, showMessage) {
		var error = getErrorElement(input);
		var tokens = describedByTokens(input);
		var id = error.id;
		var index = tokens.indexOf(id);

		if (valid) {
			input.classList.remove('cmb2-jalali-date--invalid');
			input.removeAttribute('aria-invalid');
			input.setCustomValidity('');
			error.hidden = true;
			error.textContent = '';
			if (index !== -1) {
				tokens.splice(index, 1);
			}
		} else {
			input.classList.add('cmb2-jalali-date--invalid');
			input.setAttribute('aria-invalid', 'true');
			input.setCustomValidity(config.invalidDateText || 'Enter a valid Jalali date.');
			error.textContent = config.invalidDateText || 'Enter a valid Jalali date.';
			error.hidden = !showMessage;
			if (index === -1) {
				tokens.push(id);
			}
		}

		if (tokens.length) {
			input.setAttribute('aria-describedby', tokens.join(' '));
		} else {
			input.removeAttribute('aria-describedby');
		}

		return valid;
	}

	function validateInput(input, showMessage) {
		var value = Core.normalizeDigits(input.value || '').trim();
		if (!value) {
			return setValidationState(input, true, false);
		}
		return setValidationState(input, !!Core.parse(value), !!showMessage);
	}

	function scan(root) {
		var container = root && root.querySelectorAll ? root : document;
		Array.prototype.forEach.call(container.querySelectorAll(FIELD_SELECTOR), enhanceInput);
		if (root && root.matches && root.matches(FIELD_SELECTOR)) {
			enhanceInput(root);
		}
	}

	function monthOptions() {
		var output = '';
		var names = config.monthNames || [];
		var i;
		for (i = 1; i <= 12; i += 1) {
			output += '<option value="' + i + '"' + (i === state.month ? ' selected' : '') + '>'
				+ escapeHtml(names[i - 1] || String(i)) + '</option>';
		}
		return output;
	}

	function yearOptions() {
		var radius = fieldYearRange(activeInput);
		var min = Math.max(-61, state.year - radius);
		var max = Math.min(3177, state.year + radius);
		var output = '';
		var year;
		for (year = min; year <= max; year += 1) {
			output += '<option value="' + year + '"' + (year === state.year ? ' selected' : '') + '>'
				+ escapeHtml(digits(year)) + '</option>';
		}
		return output;
	}

	function weekdayForJalali(year, month, day) {
		var gregorian = Core.toGregorian(year, month, day);
		return new Date(Date.UTC(gregorian.year, gregorian.month - 1, gregorian.day)).getUTCDay();
	}

	function weekIndexForJalali(year, month, day) {
		return (weekdayForJalali(year, month, day) + 1) % 7;
	}

	function sameDate(a, b) {
		return !!a && !!b && a.year === b.year && a.month === b.month && a.day === b.day;
	}

	function dayCell(day, today) {
		var date = { year: state.year, month: state.month, day: day };
		var weekday = weekdayForJalali(state.year, state.month, day);
		var classes = ['cmb2-jalali-datepicker__day'];
		var selected = sameDate(date, state.selected);
		var current = sameDate(date, today);
		var label = (config.fullDateLabel || '{year}/{month}/{day}')
			.replace('{year}', digits(state.year))
			.replace('{month}', escapeHtml((config.monthNames || [])[state.month - 1] || state.month))
			.replace('{day}', digits(day));

		if (weekday === 5) {
			classes.push('cmb2-jalali-datepicker__day--holiday');
		}
		if (current) {
			classes.push('cmb2-jalali-datepicker__day--today');
		}
		if (selected) {
			classes.push('cmb2-jalali-datepicker__day--selected');
		}

		return '<td role="gridcell">'
			+ '<button type="button" class="' + classes.join(' ') + '"'
			+ ' data-day="' + day + '" aria-label="' + escapeAttr(label) + '"'
			+ (selected ? ' aria-selected="true"' : ' aria-selected="false"')
			+ (current ? ' aria-current="date"' : '')
			+ ' tabindex="' + (state.day === day ? '0' : '-1') + '">'
			+ escapeHtml(digits(day)) + '</button></td>';
	}

	function calendarRows() {
		var firstWeekday = weekdayForJalali(state.year, state.month, 1);
		var startOffset = (firstWeekday + 1) % 7;
		var days = Core.monthLength(state.year, state.month);
		var today = todayJalali();
		var cells = [];
		var rows = '';
		var i;
		var day;

		for (i = 0; i < startOffset; i += 1) {
			cells.push('<td aria-hidden="true"><span class="cmb2-jalali-datepicker__empty"></span></td>');
		}
		for (day = 1; day <= days; day += 1) {
			cells.push(dayCell(day, today));
		}
		while (cells.length % 7 !== 0) {
			cells.push('<td aria-hidden="true"><span class="cmb2-jalali-datepicker__empty"></span></td>');
		}
		for (i = 0; i < cells.length; i += 7) {
			rows += '<tr>' + cells.slice(i, i + 7).join('') + '</tr>';
		}
		return rows;
	}

	function weekdayHeaders() {
		var min = config.dayNamesMin || ['Sa', 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr'];
		var full = config.dayNames || min;
		return min.map(function (name, index) {
			return '<th scope="col"><span title="' + escapeAttr(full[index] || name) + '">' + escapeHtml(name) + '</span></th>';
		}).join('');
	}

	function currentMonthLabel() {
		var names = config.monthNames || [];
		return (names[state.month - 1] || state.month) + ' ' + digits(state.year);
	}

	function render() {
		var element = ensurePicker();
		var prevText = escapeAttr(config.prevText || 'Previous month');
		var nextText = escapeAttr(config.nextText || 'Next month');
		var prevSymbol = isRtl ? '›' : '‹';
		var nextSymbol = isRtl ? '‹' : '›';

		element.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
		element.innerHTML = ''
			+ '<span class="cmb2-jalali-datepicker__status" role="status" aria-live="polite">' + escapeHtml(currentMonthLabel()) + '</span>'
			+ '<div class="cmb2-jalali-datepicker__header">'
			+ '<button type="button" class="cmb2-jalali-datepicker__nav cmb2-jalali-datepicker__prev" aria-label="' + prevText + '">' + prevSymbol + '</button>'
			+ '<div class="cmb2-jalali-datepicker__controls">'
			+ '<span class="cmb2-jalali-datepicker__select-wrap"><select class="cmb2-jalali-datepicker__select cmb2-jalali-datepicker__month" aria-label="' + escapeAttr(config.monthSelectLabel || 'Month') + '">' + monthOptions() + '</select></span>'
			+ '<span class="cmb2-jalali-datepicker__select-wrap"><select class="cmb2-jalali-datepicker__select cmb2-jalali-datepicker__year" aria-label="' + escapeAttr(config.yearSelectLabel || 'Year') + '">' + yearOptions() + '</select></span>'
			+ '</div>'
			+ '<button type="button" class="cmb2-jalali-datepicker__nav cmb2-jalali-datepicker__next" aria-label="' + nextText + '">' + nextSymbol + '</button>'
			+ '</div>'
			+ '<table class="cmb2-jalali-datepicker__calendar" role="grid" aria-label="' + escapeAttr(currentMonthLabel()) + '"><thead><tr>' + weekdayHeaders() + '</tr></thead><tbody>'
			+ calendarRows() + '</tbody></table>'
			+ ((fieldBool(activeInput, 'jalaliShowToday', true) || fieldBool(activeInput, 'jalaliShowClear', true)) ? '<div class="cmb2-jalali-datepicker__footer">' : '')
			+ (fieldBool(activeInput, 'jalaliShowToday', true) ? '<button type="button" class="cmb2-jalali-datepicker__action cmb2-jalali-datepicker__action--today">' + escapeHtml(config.todayText || 'Today') + '</button>' : '')
			+ (fieldBool(activeInput, 'jalaliShowClear', true) ? '<button type="button" class="cmb2-jalali-datepicker__action cmb2-jalali-datepicker__action--clear">' + escapeHtml(config.clearText || 'Clear') + '</button>' : '')
			+ ((fieldBool(activeInput, 'jalaliShowToday', true) || fieldBool(activeInput, 'jalaliShowClear', true)) ? '</div>' : '');

		bindPickerEvents();
		positionPicker();
	}

	function focusControl(selector) {
		var control = picker && picker.querySelector(selector);
		if (control) {
			control.focus();
		}
	}

	function bindPickerEvents() {
		picker.querySelector('.cmb2-jalali-datepicker__prev').addEventListener('click', function () {
			moveMonth(-1, '.cmb2-jalali-datepicker__prev');
		});
		picker.querySelector('.cmb2-jalali-datepicker__next').addEventListener('click', function () {
			moveMonth(1, '.cmb2-jalali-datepicker__next');
		});
		picker.querySelector('.cmb2-jalali-datepicker__month').addEventListener('change', function (event) {
			state.month = Number(event.target.value);
			state.day = Math.min(state.day, Core.monthLength(state.year, state.month));
			render();
			focusControl('.cmb2-jalali-datepicker__month');
		});
		picker.querySelector('.cmb2-jalali-datepicker__year').addEventListener('change', function (event) {
			state.year = Number(event.target.value);
			state.day = Math.min(state.day, Core.monthLength(state.year, state.month));
			render();
			focusControl('.cmb2-jalali-datepicker__year');
		});
		var todayButton = picker.querySelector('.cmb2-jalali-datepicker__action--today');
		var clearButton = picker.querySelector('.cmb2-jalali-datepicker__action--clear');
		if (todayButton) {
			todayButton.addEventListener('click', function () {
				var today = todayJalali();
				state.year = today.year;
				state.month = today.month;
				state.day = today.day;
				selectDay(today.day);
			});
		}
		if (clearButton) {
			clearButton.addEventListener('click', clearValue);
		}
		Array.prototype.forEach.call(picker.querySelectorAll('[data-day]'), function (dayButton) {
			dayButton.addEventListener('click', function () {
				selectDay(Number(dayButton.dataset.day));
			});
			dayButton.addEventListener('keydown', onDayKeydown);
		});
	}

	function moveMonth(delta, focusSelector) {
		state.month += delta;
		if (state.month < 1) {
			state.month = 12;
			state.year -= 1;
		} else if (state.month > 12) {
			state.month = 1;
			state.year += 1;
		}
		state.year = Math.max(-61, Math.min(3177, state.year));
		state.day = Math.min(state.day, Core.monthLength(state.year, state.month));
		render();
		if (focusSelector) {
			focusControl(focusSelector);
		}
	}

	function moveYear(delta) {
		state.year = Math.max(-61, Math.min(3177, state.year + delta));
		state.day = Math.min(state.day, Core.monthLength(state.year, state.month));
		render();
		focusDay(state.day);
	}

	function moveFocusedDay(delta) {
		var current = { year: state.year, month: state.month, day: state.day };
		var gregorian = Core.toGregorian(current.year, current.month, current.day);
		var date = new Date(Date.UTC(gregorian.year, gregorian.month - 1, gregorian.day + delta));
		var jalali = Core.toJalali(date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate());
		state.year = jalali.year;
		state.month = jalali.month;
		state.day = jalali.day;
		render();
		focusDay(state.day);
	}

	function onDayKeydown(event) {
		var delta = 0;
		var weekIndex;

		if (event.key === 'ArrowRight') {
			delta = isRtl ? -1 : 1;
		} else if (event.key === 'ArrowLeft') {
			delta = isRtl ? 1 : -1;
		} else if (event.key === 'ArrowDown') {
			delta = 7;
		} else if (event.key === 'ArrowUp') {
			delta = -7;
		} else if (event.key === 'Home') {
			weekIndex = weekIndexForJalali(state.year, state.month, state.day);
			delta = -weekIndex;
		} else if (event.key === 'End') {
			weekIndex = weekIndexForJalali(state.year, state.month, state.day);
			delta = 6 - weekIndex;
		} else if (event.key === 'PageDown' && event.shiftKey) {
			event.preventDefault();
			moveYear(1);
			return;
		} else if (event.key === 'PageUp' && event.shiftKey) {
			event.preventDefault();
			moveYear(-1);
			return;
		} else if (event.key === 'PageDown') {
			event.preventDefault();
			moveMonth(1);
			focusDay(state.day);
			return;
		} else if (event.key === 'PageUp') {
			event.preventDefault();
			moveMonth(-1);
			focusDay(state.day);
			return;
		} else if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault();
			selectDay(Number(event.currentTarget.dataset.day));
			return;
		} else if (event.key === 'Escape') {
			event.preventDefault();
			closePicker(true);
			return;
		}

		if (delta !== 0) {
			event.preventDefault();
			moveFocusedDay(delta);
		}
	}

	function focusDay(day) {
		var button = picker && picker.querySelector('[data-day="' + day + '"]');
		if (button) {
			Array.prototype.forEach.call(picker.querySelectorAll('[data-day]'), function (candidate) {
				candidate.setAttribute('tabindex', '-1');
			});
			button.setAttribute('tabindex', '0');
			button.focus();
		}
	}

	function selectDay(day) {
		var selected = { year: state.year, month: state.month, day: day };
		activeInput.value = digits(Core.format(selected), activeInput);
		setValidationState(activeInput, true, false);
		activeInput.dispatchEvent(new Event('input', { bubbles: true }));
		activeInput.dispatchEvent(new Event('change', { bubbles: true }));
		closePicker(true);
	}

	function clearValue() {
		if (!activeInput) {
			return;
		}
		activeInput.value = '';
		setValidationState(activeInput, true, false);
		activeInput.dispatchEvent(new Event('input', { bubbles: true }));
		activeInput.dispatchEvent(new Event('change', { bubbles: true }));
		closePicker(true);
	}

	function positionPicker() {
		var rect;
		var width;
		var height;
		var left;
		var top;
		var maxLeft;
		var viewportHeight;

		if (!activeInput || !picker || picker.hidden) {
			return;
		}

		rect = activeInput.getBoundingClientRect();
		width = picker.offsetWidth || 304;
		height = picker.offsetHeight || 360;
		left = window.pageXOffset + (isRtl ? rect.right - width : rect.left);
		maxLeft = window.pageXOffset + document.documentElement.clientWidth - width - 8;
		left = Math.max(window.pageXOffset + 8, Math.min(left, maxLeft));
		viewportHeight = document.documentElement.clientHeight;
		top = window.pageYOffset + rect.bottom + 6;

		if (rect.bottom + height + 6 > viewportHeight && rect.top > height + 6) {
			top = window.pageYOffset + rect.top - height - 6;
		}

		picker.style.left = left + 'px';
		picker.style.top = top + 'px';
	}

	function openPicker(input, focusCalendar) {
		if (!pickerEnabled(input)) {
			return;
		}
		ensurePicker();
		enhanceInput(input);
		activeInput = input;
		state = normalizeState(input);
		isRtl = document.documentElement.dir === 'rtl' || input.dir === 'rtl' || !!input.closest('[dir="rtl"]');
		picker.hidden = false;
		input.setAttribute('aria-expanded', 'true');
		render();
		if (focusCalendar) {
			window.setTimeout(function () {
				focusDay(state.day);
			}, 0);
		}
	}

	function closePicker(returnFocus) {
		var input = activeInput;
		if (!picker || picker.hidden) {
			return;
		}
		picker.hidden = true;
		if (input) {
			input.setAttribute('aria-expanded', 'false');
			if (returnFocus) {
				suppressNextFocusOpen = true;
				input.focus();
			}
		}
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function escapeAttr(value) {
		return escapeHtml(value);
	}

	document.addEventListener('focusin', function (event) {
		if (event.target && event.target.matches && event.target.matches(FIELD_SELECTOR)) {
			if (suppressNextFocusOpen) {
				suppressNextFocusOpen = false;
				return;
			}
			openPicker(event.target, false);
		}
	});

	document.addEventListener('click', function (event) {
		if (!event.target || !event.target.matches || !event.target.matches(FIELD_SELECTOR) || !pickerEnabled(event.target)) {
			return;
		}

		// After selecting or clearing a date, focus is intentionally returned to
		// the input. A subsequent click on an already-focused input does not fire
		// focusin, so explicitly reopen a closed picker here.
		if (!picker || picker.hidden || activeInput !== event.target) {
			openPicker(event.target, false);
		}
	});

	document.addEventListener('focusout', function (event) {
		if (event.target && event.target.matches && event.target.matches(FIELD_SELECTOR)) {
			var parsed = Core.parse(event.target.value);
			if (parsed) {
				event.target.value = digits(Core.format(parsed), event.target);
				setValidationState(event.target, true, false);
			} else {
				validateInput(event.target, true);
			}
		}
	});


	document.addEventListener('input', function (event) {
		if (!event.target || !event.target.matches || !event.target.matches(FIELD_SELECTOR)) {
			return;
		}

		var value = Core.normalizeDigits(event.target.value || '').trim();
		if (!value || Core.parse(value)) {
			setValidationState(event.target, true, false);
		}
	});

	document.addEventListener('submit', function (event) {
		if (!event.target || !event.target.querySelectorAll) {
			return;
		}

		var fields = event.target.querySelectorAll(FIELD_SELECTOR);
		var firstInvalid = null;

		Array.prototype.forEach.call(fields, function (input) {
			if (!validateInput(input, true) && !firstInvalid) {
				firstInvalid = input;
			}
		});

		if (firstInvalid) {
			event.preventDefault();
			closePicker(false);
			firstInvalid.focus();
			if (typeof firstInvalid.reportValidity === 'function') {
				firstInvalid.reportValidity();
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		if (picker && !picker.hidden && event.key === 'Escape' && picker.contains(event.target)) {
			event.preventDefault();
			closePicker(true);
			return;
		}

		if (event.target && event.target.matches && event.target.matches(FIELD_SELECTOR)) {
			if (event.key === 'ArrowDown' && pickerEnabled(event.target)) {
				event.preventDefault();
				openPicker(event.target, true);
			} else if (event.key === 'Escape') {
				event.preventDefault();
				closePicker(false);
			}
		}
	});

	document.addEventListener('mousedown', function (event) {
		if (!picker || picker.hidden) {
			return;
		}
		if (picker.contains(event.target) || event.target === activeInput) {
			return;
		}
		closePicker(false);
	});

	window.addEventListener('resize', positionPicker);
	window.addEventListener('scroll', positionPicker, true);

	if (window.jQuery) {
		window.jQuery(document).on('cmb2_add_row', function (event, row) {
			scan(row && row.jquery ? row[0] : (row || event.target));
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { scan(document); });
	} else {
		scan(document);
	}

	window.CMB2JalaliDatePicker = {
		open: function (input) { openPicker(input, false); },
		close: function () { closePicker(false); },
		refresh: function (root) { scan(root || document); }
	};
}(window, document));
