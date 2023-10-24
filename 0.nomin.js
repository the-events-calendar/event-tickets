(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[0],{

/***/ "1rrs":
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("FsYZ");


/***/ }),

/***/ "5Q0V":
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__("cDf5")["default"];
function _toPrimitive(input, hint) {
  if (_typeof(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if (_typeof(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
module.exports = _toPrimitive, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "8OQS":
/***/ (function(module, exports) {

function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;
  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }
  return target;
}
module.exports = _objectWithoutPropertiesLoose, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "8oxB":
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),

/***/ "FsYZ":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.formatDay = formatDay;
exports.formatMonthTitle = formatMonthTitle;
exports.formatWeekdayShort = formatWeekdayShort;
exports.formatWeekdayLong = formatWeekdayLong;
exports.getFirstDayOfWeek = getFirstDayOfWeek;
exports.getMonths = getMonths;
exports.formatDate = formatDate;
exports.parseDate = parseDate;

var _moment = __webpack_require__("wy2R");

var _moment2 = _interopRequireDefault(_moment);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function formatDay(day) {
  var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'en';

  return (0, _moment2.default)(day).locale(locale).format('ddd ll');
} /* eslint-disable import/no-extraneous-dependencies, no-underscore-dangle */

function formatMonthTitle(date) {
  var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'en';

  return (0, _moment2.default)(date).locale(locale).format('MMMM YYYY');
}

function formatWeekdayShort(day) {
  var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'en';

  return _moment2.default.localeData(locale).weekdaysMin()[day];
}

function formatWeekdayLong(day) {
  var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'en';

  return _moment2.default.localeData(locale).weekdays()[day];
}

function getFirstDayOfWeek() {
  var locale = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'en';

  return _moment2.default.localeData(locale).firstDayOfWeek();
}

function getMonths() {
  var locale = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'en';

  return _moment2.default.localeData(locale).months();
}

function formatDate(date) {
  var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'L';
  var locale = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'en';

  return (0, _moment2.default)(date).locale(locale).format(Array.isArray(format) ? format[0] : format);
}

function parseDate(str) {
  var format = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'L';
  var locale = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'en';

  var m = (0, _moment2.default)(str, format, locale, true);
  if (m.isValid()) {
    return m.toDate();
  }
  return undefined;
}

exports.default = {
  formatDay: formatDay,
  formatMonthTitle: formatMonthTitle,
  formatWeekdayShort: formatWeekdayShort,
  formatWeekdayLong: formatWeekdayLong,
  getFirstDayOfWeek: getFirstDayOfWeek,
  getMonths: getMonths,
  formatDate: formatDate,
  parseDate: parseDate
};
//# sourceMappingURL=MomentLocaleUtils.js.map

/***/ }),

/***/ "QILm":
/***/ (function(module, exports, __webpack_require__) {

var objectWithoutPropertiesLoose = __webpack_require__("8OQS");
function _objectWithoutProperties(source, excluded) {
  if (source == null) return {};
  var target = objectWithoutPropertiesLoose(source, excluded);
  var key, i;
  if (Object.getOwnPropertySymbols) {
    var sourceSymbolKeys = Object.getOwnPropertySymbols(source);
    for (i = 0; i < sourceSymbolKeys.length; i++) {
      key = sourceSymbolKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
      target[key] = source[key];
    }
  }
  return target;
}
module.exports = _objectWithoutProperties, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "aZ9c":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export NumberFormatBase */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return NumericFormat; });
/* unused harmony export PatternFormat */
/* unused harmony export getNumericCaretBoundary */
/* unused harmony export getPatternCaretBoundary */
/* unused harmony export numericFormatter */
/* unused harmony export patternFormatter */
/* unused harmony export removeNumericFormat */
/* unused harmony export removePatternFormat */
/* unused harmony export useNumericFormat */
/* unused harmony export usePatternFormat */
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("cDcd");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/**
 * react-number-format - 5.3.1
 * Author : Sudhanshu Yadav
 * Copyright (c) 2016, 2023 to Sudhanshu Yadav, released under the MIT license.
 * https://github.com/s-yadav/react-number-format
 */



/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */

function __rest(s, e) {
    var t = {};
    for (var p in s) { if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
        { t[p] = s[p]; } }
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
        { for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
            if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
                { t[p[i]] = s[p[i]]; }
        } }
    return t;
}

var SourceType;
(function (SourceType) {
    SourceType["event"] = "event";
    SourceType["props"] = "prop";
})(SourceType || (SourceType = {}));

// basic noop function
function noop() { }
function memoizeOnce(cb) {
    var lastArgs;
    var lastValue = undefined;
    return function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        if (lastArgs &&
            args.length === lastArgs.length &&
            args.every(function (value, index) { return value === lastArgs[index]; })) {
            return lastValue;
        }
        lastArgs = args;
        lastValue = cb.apply(void 0, args);
        return lastValue;
    };
}
function charIsNumber(char) {
    return !!(char || '').match(/\d/);
}
function isNil(val) {
    return val === null || val === undefined;
}
function isNanValue(val) {
    return typeof val === 'number' && isNaN(val);
}
function isNotValidValue(val) {
    return isNil(val) || isNanValue(val) || (typeof val === 'number' && !isFinite(val));
}
function escapeRegExp(str) {
    return str.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
}
function getThousandsGroupRegex(thousandsGroupStyle) {
    switch (thousandsGroupStyle) {
        case 'lakh':
            return /(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/g;
        case 'wan':
            return /(\d)(?=(\d{4})+(?!\d))/g;
        case 'thousand':
        default:
            return /(\d)(?=(\d{3})+(?!\d))/g;
    }
}
function applyThousandSeparator(str, thousandSeparator, thousandsGroupStyle) {
    var thousandsGroupRegex = getThousandsGroupRegex(thousandsGroupStyle);
    var index = str.search(/[1-9]/);
    index = index === -1 ? str.length : index;
    return (str.substring(0, index) +
        str.substring(index, str.length).replace(thousandsGroupRegex, '$1' + thousandSeparator));
}
function usePersistentCallback(cb) {
    var callbackRef = Object(react__WEBPACK_IMPORTED_MODULE_0__["useRef"])(cb);
    // keep the callback ref upto date
    callbackRef.current = cb;
    /**
     * initialize a persistent callback which never changes
     * through out the component lifecycle
     */
    var persistentCbRef = Object(react__WEBPACK_IMPORTED_MODULE_0__["useRef"])(function () {
        var args = [], len = arguments.length;
        while ( len-- ) args[ len ] = arguments[ len ];

        return callbackRef.current.apply(callbackRef, args);
    });
    return persistentCbRef.current;
}
//spilt a float number into different parts beforeDecimal, afterDecimal, and negation
function splitDecimal(numStr, allowNegative) {
    if ( allowNegative === void 0 ) allowNegative = true;

    var hasNegation = numStr[0] === '-';
    var addNegation = hasNegation && allowNegative;
    numStr = numStr.replace('-', '');
    var parts = numStr.split('.');
    var beforeDecimal = parts[0];
    var afterDecimal = parts[1] || '';
    return {
        beforeDecimal: beforeDecimal,
        afterDecimal: afterDecimal,
        hasNegation: hasNegation,
        addNegation: addNegation,
    };
}
function fixLeadingZero(numStr) {
    if (!numStr)
        { return numStr; }
    var isNegative = numStr[0] === '-';
    if (isNegative)
        { numStr = numStr.substring(1, numStr.length); }
    var parts = numStr.split('.');
    var beforeDecimal = parts[0].replace(/^0+/, '') || '0';
    var afterDecimal = parts[1] || '';
    return ("" + (isNegative ? '-' : '') + beforeDecimal + (afterDecimal ? ("." + afterDecimal) : ''));
}
/**
 * limit decimal numbers to given scale
 * Not used .fixedTo because that will break with big numbers
 */
function limitToScale(numStr, scale, fixedDecimalScale) {
    var str = '';
    var filler = fixedDecimalScale ? '0' : '';
    for (var i = 0; i <= scale - 1; i++) {
        str += numStr[i] || filler;
    }
    return str;
}
function repeat(str, count) {
    return Array(count + 1).join(str);
}
function toNumericString(num) {
    var _num = num + ''; // typecast number to string
    // store the sign and remove it from the number.
    var sign = _num[0] === '-' ? '-' : '';
    if (sign)
        { _num = _num.substring(1); }
    // split the number into cofficient and exponent
    var ref = _num.split(/[eE]/g);
    var coefficient = ref[0];
    var exponent = ref[1];
    // covert exponent to number;
    exponent = Number(exponent);
    // if there is no exponent part or its 0, return the coffiecient with sign
    if (!exponent)
        { return sign + coefficient; }
    coefficient = coefficient.replace('.', '');
    /**
     * for scientific notation the current decimal index will be after first number (index 0)
     * So effective decimal index will always be 1 + exponent value
     */
    var decimalIndex = 1 + exponent;
    var coffiecientLn = coefficient.length;
    if (decimalIndex < 0) {
        // if decimal index is less then 0 add preceding 0s
        // add 1 as join will have
        coefficient = '0.' + repeat('0', Math.abs(decimalIndex)) + coefficient;
    }
    else if (decimalIndex >= coffiecientLn) {
        // if decimal index is less then 0 add leading 0s
        coefficient = coefficient + repeat('0', decimalIndex - coffiecientLn);
    }
    else {
        // else add decimal point at proper index
        coefficient =
            (coefficient.substring(0, decimalIndex) || '0') + '.' + coefficient.substring(decimalIndex);
    }
    return sign + coefficient;
}
/**
 * This method is required to round prop value to given scale.
 * Not used .round or .fixedTo because that will break with big numbers
 */
function roundToPrecision(numStr, scale, fixedDecimalScale) {
    //if number is empty don't do anything return empty string
    if (['', '-'].indexOf(numStr) !== -1)
        { return numStr; }
    var shouldHaveDecimalSeparator = (numStr.indexOf('.') !== -1 || fixedDecimalScale) && scale;
    var ref = splitDecimal(numStr);
    var beforeDecimal = ref.beforeDecimal;
    var afterDecimal = ref.afterDecimal;
    var hasNegation = ref.hasNegation;
    var floatValue = parseFloat(("0." + (afterDecimal || '0')));
    var floatValueStr = afterDecimal.length <= scale ? ("0." + afterDecimal) : floatValue.toFixed(scale);
    var roundedDecimalParts = floatValueStr.split('.');
    var intPart = beforeDecimal
        .split('')
        .reverse()
        .reduce(function (roundedStr, current, idx) {
        if (roundedStr.length > idx) {
            return ((Number(roundedStr[0]) + Number(current)).toString() +
                roundedStr.substring(1, roundedStr.length));
        }
        return current + roundedStr;
    }, roundedDecimalParts[0]);
    var decimalPart = limitToScale(roundedDecimalParts[1] || '', scale, fixedDecimalScale);
    var negation = hasNegation ? '-' : '';
    var decimalSeparator = shouldHaveDecimalSeparator ? '.' : '';
    return ("" + negation + intPart + decimalSeparator + decimalPart);
}
/** set the caret positon in an input field **/
function setCaretPosition(el, caretPos) {
    el.value = el.value;
    // ^ this is used to not only get 'focus', but
    // to make sure we don't have it everything -selected-
    // (it causes an issue in chrome, and having it doesn't hurt any other browser)
    if (el !== null) {
        /* @ts-ignore */
        if (el.createTextRange) {
            /* @ts-ignore */
            var range = el.createTextRange();
            range.move('character', caretPos);
            range.select();
            return true;
        }
        // (el.selectionStart === 0 added for Firefox bug)
        if (el.selectionStart || el.selectionStart === 0) {
            el.focus();
            el.setSelectionRange(caretPos, caretPos);
            return true;
        }
        // fail city, fortunately this never happens (as far as I've tested) :)
        el.focus();
        return false;
    }
}
var findChangeRange = memoizeOnce(function (prevValue, newValue) {
    var i = 0, j = 0;
    var prevLength = prevValue.length;
    var newLength = newValue.length;
    while (prevValue[i] === newValue[i] && i < prevLength)
        { i++; }
    //check what has been changed from last
    while (prevValue[prevLength - 1 - j] === newValue[newLength - 1 - j] &&
        newLength - j > i &&
        prevLength - j > i) {
        j++;
    }
    return {
        from: { start: i, end: prevLength - j },
        to: { start: i, end: newLength - j },
    };
});
/*
  Returns a number whose value is limited to the given range
*/
function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
}
function geInputCaretPosition(el) {
    /*Max of selectionStart and selectionEnd is taken for the patch of pixel and other mobile device caret bug*/
    return Math.max(el.selectionStart, el.selectionEnd);
}
function addInputMode() {
    return (typeof navigator !== 'undefined' &&
        !(navigator.platform && /iPhone|iPod/.test(navigator.platform)));
}
function getDefaultChangeMeta(value) {
    return {
        from: {
            start: 0,
            end: 0,
        },
        to: {
            start: 0,
            end: value.length,
        },
        lastValue: '',
    };
}
function getMaskAtIndex(mask, index) {
    if ( mask === void 0 ) mask = ' ';

    if (typeof mask === 'string') {
        return mask;
    }
    return mask[index] || ' ';
}
function defaultIsCharacterSame(ref) {
    var currentValue = ref.currentValue;
    var formattedValue = ref.formattedValue;
    var currentValueIndex = ref.currentValueIndex;
    var formattedValueIndex = ref.formattedValueIndex;

    return currentValue[currentValueIndex] === formattedValue[formattedValueIndex];
}
function getCaretPosition(newFormattedValue, lastFormattedValue, curValue, curCaretPos, boundary, isValidInputCharacter, 
/**
 * format function can change the character, the caret engine relies on mapping old value and new value
 * In such case if character is changed, parent can tell which chars are equivalent
 * Some example, all allowedDecimalCharacters are updated to decimalCharacters, 2nd case if user is coverting
 * number to different numeric system.
 */
isCharacterSame) {
    if ( isCharacterSame === void 0 ) isCharacterSame = defaultIsCharacterSame;

    /**
     * if something got inserted on empty value, add the formatted character before the current value,
     * This is to avoid the case where typed character is present on format characters
     */
    var firstAllowedPosition = boundary.findIndex(function (b) { return b; });
    var prefixFormat = newFormattedValue.slice(0, firstAllowedPosition);
    if (!lastFormattedValue && !curValue.startsWith(prefixFormat)) {
        lastFormattedValue = prefixFormat;
        curValue = prefixFormat + curValue;
        curCaretPos = curCaretPos + prefixFormat.length;
    }
    var curValLn = curValue.length;
    var formattedValueLn = newFormattedValue.length;
    // create index map
    var addedIndexMap = {};
    var indexMap = new Array(curValLn);
    for (var i = 0; i < curValLn; i++) {
        indexMap[i] = -1;
        for (var j = 0, jLn = formattedValueLn; j < jLn; j++) {
            var isCharSame = isCharacterSame({
                currentValue: curValue,
                lastValue: lastFormattedValue,
                formattedValue: newFormattedValue,
                currentValueIndex: i,
                formattedValueIndex: j,
            });
            if (isCharSame && addedIndexMap[j] !== true) {
                indexMap[i] = j;
                addedIndexMap[j] = true;
                break;
            }
        }
    }
    /**
     * For current caret position find closest characters (left and right side)
     * which are properly mapped to formatted value.
     * The idea is that the new caret position will exist always in the boundary of
     * that mapped index
     */
    var pos = curCaretPos;
    while (pos < curValLn && (indexMap[pos] === -1 || !isValidInputCharacter(curValue[pos]))) {
        pos++;
    }
    // if the caret position is on last keep the endIndex as last for formatted value
    var endIndex = pos === curValLn || indexMap[pos] === -1 ? formattedValueLn : indexMap[pos];
    pos = curCaretPos - 1;
    while (pos > 0 && indexMap[pos] === -1)
        { pos--; }
    var startIndex = pos === -1 || indexMap[pos] === -1 ? 0 : indexMap[pos] + 1;
    /**
     * case where a char is added on suffix and removed from middle, example 2sq345 becoming $2,345 sq
     * there is still a mapping but the order of start index and end index is changed
     */
    if (startIndex > endIndex)
        { return endIndex; }
    /**
     * given the current caret position if it closer to startIndex
     * keep the new caret position on start index or keep it closer to endIndex
     */
    return curCaretPos - startIndex < endIndex - curCaretPos ? startIndex : endIndex;
}
/* This keeps the caret within typing area so people can't type in between prefix or suffix or format characters */
function getCaretPosInBoundary(value, caretPos, boundary, direction) {
    var valLn = value.length;
    // clamp caret position to [0, value.length]
    caretPos = clamp(caretPos, 0, valLn);
    if (direction === 'left') {
        while (caretPos >= 0 && !boundary[caretPos])
            { caretPos--; }
        // if we don't find any suitable caret position on left, set it on first allowed position
        if (caretPos === -1)
            { caretPos = boundary.indexOf(true); }
    }
    else {
        while (caretPos <= valLn && !boundary[caretPos])
            { caretPos++; }
        // if we don't find any suitable caret position on right, set it on last allowed position
        if (caretPos > valLn)
            { caretPos = boundary.lastIndexOf(true); }
    }
    // if we still don't find caret position, set it at the end of value
    if (caretPos === -1)
        { caretPos = valLn; }
    return caretPos;
}
function caretUnknownFormatBoundary(formattedValue) {
    var boundaryAry = Array.from({ length: formattedValue.length + 1 }).map(function () { return true; });
    for (var i = 0, ln = boundaryAry.length; i < ln; i++) {
        // consider caret to be in boundary if it is before or after numeric value
        boundaryAry[i] = Boolean(charIsNumber(formattedValue[i]) || charIsNumber(formattedValue[i - 1]));
    }
    return boundaryAry;
}
function useInternalValues(value, defaultValue, valueIsNumericString, format, removeFormatting, onValueChange) {
    if ( onValueChange === void 0 ) onValueChange = noop;

    var getValues = usePersistentCallback(function (value, valueIsNumericString) {
        var formattedValue, numAsString;
        if (isNotValidValue(value)) {
            numAsString = '';
            formattedValue = '';
        }
        else if (typeof value === 'number' || valueIsNumericString) {
            numAsString = typeof value === 'number' ? toNumericString(value) : value;
            formattedValue = format(numAsString);
        }
        else {
            numAsString = removeFormatting(value, undefined);
            formattedValue = format(numAsString);
        }
        return { formattedValue: formattedValue, numAsString: numAsString };
    });
    var ref = Object(react__WEBPACK_IMPORTED_MODULE_0__["useState"])(function () {
        return getValues(isNil(value) ? defaultValue : value, valueIsNumericString);
    });
    var values = ref[0];
    var setValues = ref[1];
    var _onValueChange = function (newValues, sourceInfo) {
        if (newValues.formattedValue !== values.formattedValue) {
            setValues({
                formattedValue: newValues.formattedValue,
                numAsString: newValues.value,
            });
        }
        // call parent on value change if only if formatted value is changed
        onValueChange(newValues, sourceInfo);
    };
    // if value is switch from controlled to uncontrolled, use the internal state's value to format with new props
    var _value = value;
    var _valueIsNumericString = valueIsNumericString;
    if (isNil(value)) {
        _value = values.numAsString;
        _valueIsNumericString = true;
    }
    var newValues = getValues(_value, _valueIsNumericString);
    Object(react__WEBPACK_IMPORTED_MODULE_0__["useMemo"])(function () {
        setValues(newValues);
    }, [newValues.formattedValue]);
    return [values, _onValueChange];
}

function defaultRemoveFormatting(value) {
    return value.replace(/[^0-9]/g, '');
}
function defaultFormat(value) {
    return value;
}
function NumberFormatBase(props) {
    var type = props.type; if ( type === void 0 ) type = 'text';
    var displayType = props.displayType; if ( displayType === void 0 ) displayType = 'input';
    var customInput = props.customInput;
    var renderText = props.renderText;
    var getInputRef = props.getInputRef;
    var format = props.format; if ( format === void 0 ) format = defaultFormat;
    var removeFormatting = props.removeFormatting; if ( removeFormatting === void 0 ) removeFormatting = defaultRemoveFormatting;
    var defaultValue = props.defaultValue;
    var valueIsNumericString = props.valueIsNumericString;
    var onValueChange = props.onValueChange;
    var isAllowed = props.isAllowed;
    var onChange = props.onChange; if ( onChange === void 0 ) onChange = noop;
    var onKeyDown = props.onKeyDown; if ( onKeyDown === void 0 ) onKeyDown = noop;
    var onMouseUp = props.onMouseUp; if ( onMouseUp === void 0 ) onMouseUp = noop;
    var onFocus = props.onFocus; if ( onFocus === void 0 ) onFocus = noop;
    var onBlur = props.onBlur; if ( onBlur === void 0 ) onBlur = noop;
    var propValue = props.value;
    var getCaretBoundary = props.getCaretBoundary; if ( getCaretBoundary === void 0 ) getCaretBoundary = caretUnknownFormatBoundary;
    var isValidInputCharacter = props.isValidInputCharacter; if ( isValidInputCharacter === void 0 ) isValidInputCharacter = charIsNumber;
    var isCharacterSame = props.isCharacterSame;
    var otherProps = __rest(props, ["type", "displayType", "customInput", "renderText", "getInputRef", "format", "removeFormatting", "defaultValue", "valueIsNumericString", "onValueChange", "isAllowed", "onChange", "onKeyDown", "onMouseUp", "onFocus", "onBlur", "value", "getCaretBoundary", "isValidInputCharacter", "isCharacterSame"]);
    var ref = useInternalValues(propValue, defaultValue, Boolean(valueIsNumericString), format, removeFormatting, onValueChange);
    var ref_0 = ref[0];
    var formattedValue = ref_0.formattedValue;
    var numAsString = ref_0.numAsString;
    var onFormattedValueChange = ref[1];
    var lastUpdatedValue = Object(react__WEBPACK_IMPORTED_MODULE_0__["useRef"])({ formattedValue: formattedValue, numAsString: numAsString });
    var _onValueChange = function (values, source) {
        lastUpdatedValue.current = { formattedValue: values.formattedValue, numAsString: values.value };
        onFormattedValueChange(values, source);
    };
    var ref$1 = Object(react__WEBPACK_IMPORTED_MODULE_0__["useState"])(false);
    var mounted = ref$1[0];
    var setMounted = ref$1[1];
    var focusedElm = Object(react__WEBPACK_IMPORTED_MODULE_0__["useRef"])(null);
    var timeout = Object(react__WEBPACK_IMPORTED_MODULE_0__["useRef"])({
        setCaretTimeout: null,
        focusTimeout: null,
    });
    Object(react__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(function () {
        setMounted(true);
        return function () {
            clearTimeout(timeout.current.setCaretTimeout);
            clearTimeout(timeout.current.focusTimeout);
        };
    }, []);
    var _format = format;
    var getValueObject = function (formattedValue, numAsString) {
        var floatValue = parseFloat(numAsString);
        return {
            formattedValue: formattedValue,
            value: numAsString,
            floatValue: isNaN(floatValue) ? undefined : floatValue,
        };
    };
    var setPatchedCaretPosition = function (el, caretPos, currentValue) {
        // don't reset the caret position when the whole input content is selected
        if (el.selectionStart === 0 && el.selectionEnd === el.value.length)
            { return; }
        /* setting caret position within timeout of 0ms is required for mobile chrome,
        otherwise browser resets the caret position after we set it
        We are also setting it without timeout so that in normal browser we don't see the flickering */
        setCaretPosition(el, caretPos);
        timeout.current.setCaretTimeout = setTimeout(function () {
            if (el.value === currentValue && el.selectionStart !== el.selectionEnd) {
                setCaretPosition(el, caretPos);
            }
        }, 0);
    };
    /* This keeps the caret within typing area so people can't type in between prefix or suffix */
    var correctCaretPosition = function (value, caretPos, direction) {
        return getCaretPosInBoundary(value, caretPos, getCaretBoundary(value), direction);
    };
    var getNewCaretPosition = function (inputValue, newFormattedValue, caretPos) {
        var caretBoundary = getCaretBoundary(newFormattedValue);
        var updatedCaretPos = getCaretPosition(newFormattedValue, formattedValue, inputValue, caretPos, caretBoundary, isValidInputCharacter, isCharacterSame);
        //correct caret position if its outside of editable area
        updatedCaretPos = getCaretPosInBoundary(newFormattedValue, updatedCaretPos, caretBoundary);
        return updatedCaretPos;
    };
    var updateValueAndCaretPosition = function (params) {
        var newFormattedValue = params.formattedValue; if ( newFormattedValue === void 0 ) newFormattedValue = '';
        var input = params.input;
        var setCaretPosition = params.setCaretPosition; if ( setCaretPosition === void 0 ) setCaretPosition = true;
        var source = params.source;
        var event = params.event;
        var numAsString = params.numAsString;
        var caretPos = params.caretPos;
        if (input) {
            //calculate caret position if not defined
            if (caretPos === undefined && setCaretPosition) {
                var inputValue = params.inputValue || input.value;
                var currentCaretPosition = geInputCaretPosition(input);
                /**
                 * set the value imperatively, this is required for IE fix
                 * This is also required as if new caret position is beyond the previous value.
                 * Caret position will not be set correctly
                 */
                input.value = newFormattedValue;
                //get the caret position
                caretPos = getNewCaretPosition(inputValue, newFormattedValue, currentCaretPosition);
            }
            /**
             * set the value imperatively, as we set the caret position as well imperatively.
             * This is to keep value and caret position in sync
             */
            input.value = newFormattedValue;
            //set caret position, and value imperatively when element is provided
            if (setCaretPosition && caretPos !== undefined) {
                //set caret position
                setPatchedCaretPosition(input, caretPos, newFormattedValue);
            }
        }
        if (newFormattedValue !== formattedValue) {
            // trigger onValueChange synchronously, so parent is updated along with the number format. Fix for #277, #287
            _onValueChange(getValueObject(newFormattedValue, numAsString), { event: event, source: source });
        }
    };
    /**
     * if the formatted value is not synced to parent, or if the formatted value is different from last synced value sync it
     * we also don't need to sync to the parent if no formatting is applied
     * if the formatting props is removed, in which case last formatted value will be different from the numeric string value
     * in such case we need to inform the parent.
     */
    Object(react__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(function () {
        var ref = lastUpdatedValue.current;
        var lastFormattedValue = ref.formattedValue;
        var lastNumAsString = ref.numAsString;
        if (formattedValue !== lastFormattedValue &&
            (formattedValue !== numAsString || lastFormattedValue !== lastNumAsString)) {
            _onValueChange(getValueObject(formattedValue, numAsString), {
                event: undefined,
                source: SourceType.props,
            });
        }
    }, [formattedValue, numAsString]);
    // also if formatted value is changed from the props, we need to update the caret position
    // keep the last caret position if element is focused
    var currentCaretPosition = focusedElm.current
        ? geInputCaretPosition(focusedElm.current)
        : undefined;
    // needed to prevent warning with useLayoutEffect on server
    var useIsomorphicLayoutEffect = typeof window !== 'undefined' ? react__WEBPACK_IMPORTED_MODULE_0__["useLayoutEffect"] : react__WEBPACK_IMPORTED_MODULE_0__["useEffect"];
    useIsomorphicLayoutEffect(function () {
        var input = focusedElm.current;
        if (formattedValue !== lastUpdatedValue.current.formattedValue && input) {
            var caretPos = getNewCaretPosition(lastUpdatedValue.current.formattedValue, formattedValue, currentCaretPosition);
            /**
             * set the value imperatively, as we set the caret position as well imperatively.
             * This is to keep value and caret position in sync
             */
            input.value = formattedValue;
            setPatchedCaretPosition(input, caretPos, formattedValue);
        }
    }, [formattedValue]);
    var formatInputValue = function (inputValue, event, source) {
        var changeRange = findChangeRange(formattedValue, inputValue);
        var changeMeta = Object.assign(Object.assign({}, changeRange), { lastValue: formattedValue });
        var _numAsString = removeFormatting(inputValue, changeMeta);
        var _formattedValue = _format(_numAsString);
        // formatting can remove some of the number chars, so we need to fine number string again
        _numAsString = removeFormatting(_formattedValue, undefined);
        if (isAllowed && !isAllowed(getValueObject(_formattedValue, _numAsString))) {
            //reset the caret position
            var input = event.target;
            var currentCaretPosition = geInputCaretPosition(input);
            var caretPos = getNewCaretPosition(inputValue, formattedValue, currentCaretPosition);
            input.value = formattedValue;
            setPatchedCaretPosition(input, caretPos, formattedValue);
            return false;
        }
        updateValueAndCaretPosition({
            formattedValue: _formattedValue,
            numAsString: _numAsString,
            inputValue: inputValue,
            event: event,
            source: source,
            setCaretPosition: true,
            input: event.target,
        });
        return true;
    };
    var _onChange = function (e) {
        var el = e.target;
        var inputValue = el.value;
        var changed = formatInputValue(inputValue, e, SourceType.event);
        if (changed)
            { onChange(e); }
    };
    var _onKeyDown = function (e) {
        var el = e.target;
        var key = e.key;
        var selectionStart = el.selectionStart;
        var selectionEnd = el.selectionEnd;
        var value = el.value; if ( value === void 0 ) value = '';
        var expectedCaretPosition;
        //Handle backspace and delete against non numerical/decimal characters or arrow keys
        if (key === 'ArrowLeft' || key === 'Backspace') {
            expectedCaretPosition = Math.max(selectionStart - 1, 0);
        }
        else if (key === 'ArrowRight') {
            expectedCaretPosition = Math.min(selectionStart + 1, value.length);
        }
        else if (key === 'Delete') {
            expectedCaretPosition = selectionStart;
        }
        //if expectedCaretPosition is not set it means we don't want to Handle keyDown
        // also if multiple characters are selected don't handle
        if (expectedCaretPosition === undefined || selectionStart !== selectionEnd) {
            onKeyDown(e);
            return;
        }
        var newCaretPosition = expectedCaretPosition;
        if (key === 'ArrowLeft' || key === 'ArrowRight') {
            var direction = key === 'ArrowLeft' ? 'left' : 'right';
            newCaretPosition = correctCaretPosition(value, expectedCaretPosition, direction);
            // arrow left or right only moves the caret, so no need to handle the event, if we are handling it manually
            if (newCaretPosition !== expectedCaretPosition) {
                e.preventDefault();
            }
        }
        else if (key === 'Delete' && !isValidInputCharacter(value[expectedCaretPosition])) {
            // in case of delete go to closest caret boundary on the right side
            newCaretPosition = correctCaretPosition(value, expectedCaretPosition, 'right');
        }
        else if (key === 'Backspace' && !isValidInputCharacter(value[expectedCaretPosition])) {
            // in case of backspace go to closest caret boundary on the left side
            newCaretPosition = correctCaretPosition(value, expectedCaretPosition, 'left');
        }
        if (newCaretPosition !== expectedCaretPosition) {
            setPatchedCaretPosition(el, newCaretPosition, value);
        }
        /* NOTE: this is just required for unit test as we need to get the newCaretPosition,
                Remove this when you find different solution */
        /* @ts-ignore */
        if (e.isUnitTestRun) {
            setPatchedCaretPosition(el, newCaretPosition, value);
        }
        onKeyDown(e);
    };
    /** required to handle the caret position when click anywhere within the input **/
    var _onMouseUp = function (e) {
        var el = e.target;
        /**
         * NOTE: we have to give default value for value as in case when custom input is provided
         * value can come as undefined when nothing is provided on value prop.
         */
        var selectionStart = el.selectionStart;
        var selectionEnd = el.selectionEnd;
        var value = el.value; if ( value === void 0 ) value = '';
        if (selectionStart === selectionEnd) {
            var caretPosition = correctCaretPosition(value, selectionStart);
            if (caretPosition !== selectionStart) {
                setPatchedCaretPosition(el, caretPosition, value);
            }
        }
        onMouseUp(e);
    };
    var _onFocus = function (e) {
        // Workaround Chrome and Safari bug https://bugs.chromium.org/p/chromium/issues/detail?id=779328
        // (onFocus event target selectionStart is always 0 before setTimeout)
        if (e.persist)
            { e.persist(); }
        var el = e.target;
        focusedElm.current = el;
        timeout.current.focusTimeout = setTimeout(function () {
            var selectionStart = el.selectionStart;
            var selectionEnd = el.selectionEnd;
            var value = el.value; if ( value === void 0 ) value = '';
            var caretPosition = correctCaretPosition(value, selectionStart);
            //setPatchedCaretPosition only when everything is not selected on focus (while tabbing into the field)
            if (caretPosition !== selectionStart &&
                !(selectionStart === 0 && selectionEnd === value.length)) {
                setPatchedCaretPosition(el, caretPosition, value);
            }
            onFocus(e);
        }, 0);
    };
    var _onBlur = function (e) {
        focusedElm.current = null;
        clearTimeout(timeout.current.focusTimeout);
        clearTimeout(timeout.current.setCaretTimeout);
        onBlur(e);
    };
    // add input mode on element based on format prop and device once the component is mounted
    var inputMode = mounted && addInputMode() ? 'numeric' : undefined;
    var inputProps = Object.assign({ inputMode: inputMode }, otherProps, {
        type: type,
        value: formattedValue,
        onChange: _onChange,
        onKeyDown: _onKeyDown,
        onMouseUp: _onMouseUp,
        onFocus: _onFocus,
        onBlur: _onBlur,
    });
    if (displayType === 'text') {
        return renderText ? (react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement(react__WEBPACK_IMPORTED_MODULE_0___default.a.Fragment, null, renderText(formattedValue, otherProps) || null)) : (react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement("span", Object.assign({}, otherProps, { ref: getInputRef }), formattedValue));
    }
    else if (customInput) {
        var CustomInput = customInput;
        /* @ts-ignore */
        return react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement(CustomInput, Object.assign({}, inputProps, { ref: getInputRef }));
    }
    return react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement("input", Object.assign({}, inputProps, { ref: getInputRef }));
}

function format(numStr, props) {
    var decimalScale = props.decimalScale;
    var fixedDecimalScale = props.fixedDecimalScale;
    var prefix = props.prefix; if ( prefix === void 0 ) prefix = '';
    var suffix = props.suffix; if ( suffix === void 0 ) suffix = '';
    var allowNegative = props.allowNegative;
    var thousandsGroupStyle = props.thousandsGroupStyle; if ( thousandsGroupStyle === void 0 ) thousandsGroupStyle = 'thousand';
    // don't apply formatting on empty string or '-'
    if (numStr === '' || numStr === '-') {
        return numStr;
    }
    var ref = getSeparators(props);
    var thousandSeparator = ref.thousandSeparator;
    var decimalSeparator = ref.decimalSeparator;
    /**
     * Keep the decimal separator
     * when decimalScale is not defined or non zero and the numStr has decimal in it
     * Or if decimalScale is > 0 and fixeDecimalScale is true (even if numStr has no decimal)
     */
    var hasDecimalSeparator = (decimalScale !== 0 && numStr.indexOf('.') !== -1) || (decimalScale && fixedDecimalScale);
    var ref$1 = splitDecimal(numStr, allowNegative);
    var beforeDecimal = ref$1.beforeDecimal;
    var afterDecimal = ref$1.afterDecimal;
    var addNegation = ref$1.addNegation; // eslint-disable-line prefer-const
    //apply decimal precision if its defined
    if (decimalScale !== undefined) {
        afterDecimal = limitToScale(afterDecimal, decimalScale, !!fixedDecimalScale);
    }
    if (thousandSeparator) {
        beforeDecimal = applyThousandSeparator(beforeDecimal, thousandSeparator, thousandsGroupStyle);
    }
    //add prefix and suffix when there is a number present
    if (prefix)
        { beforeDecimal = prefix + beforeDecimal; }
    if (suffix)
        { afterDecimal = afterDecimal + suffix; }
    //restore negation sign
    if (addNegation)
        { beforeDecimal = '-' + beforeDecimal; }
    numStr = beforeDecimal + ((hasDecimalSeparator && decimalSeparator) || '') + afterDecimal;
    return numStr;
}
function getSeparators(props) {
    var decimalSeparator = props.decimalSeparator; if ( decimalSeparator === void 0 ) decimalSeparator = '.';
    var thousandSeparator = props.thousandSeparator;
    var allowedDecimalSeparators = props.allowedDecimalSeparators;
    if (thousandSeparator === true) {
        thousandSeparator = ',';
    }
    if (!allowedDecimalSeparators) {
        allowedDecimalSeparators = [decimalSeparator, '.'];
    }
    return {
        decimalSeparator: decimalSeparator,
        thousandSeparator: thousandSeparator,
        allowedDecimalSeparators: allowedDecimalSeparators,
    };
}
function handleNegation(value, allowNegative) {
    if ( value === void 0 ) value = '';

    var negationRegex = new RegExp('(-)');
    var doubleNegationRegex = new RegExp('(-)(.)*(-)');
    // Check number has '-' value
    var hasNegation = negationRegex.test(value);
    // Check number has 2 or more '-' values
    var removeNegation = doubleNegationRegex.test(value);
    //remove negation
    value = value.replace(/-/g, '');
    if (hasNegation && !removeNegation && allowNegative) {
        value = '-' + value;
    }
    return value;
}
function getNumberRegex(decimalSeparator, global) {
    return new RegExp(("(^-)|[0-9]|" + (escapeRegExp(decimalSeparator))), global ? 'g' : undefined);
}
function isNumericString(val, prefix, suffix) {
    // for empty value we can always treat it as numeric string
    if (val === '')
        { return true; }
    return (!(prefix === null || prefix === void 0 ? void 0 : prefix.match(/\d/)) && !(suffix === null || suffix === void 0 ? void 0 : suffix.match(/\d/)) && typeof val === 'string' && !isNaN(Number(val)));
}
function removeFormatting(value, changeMeta, props) {
    var assign;

    if ( changeMeta === void 0 ) changeMeta = getDefaultChangeMeta(value);
    var allowNegative = props.allowNegative;
    var prefix = props.prefix; if ( prefix === void 0 ) prefix = '';
    var suffix = props.suffix; if ( suffix === void 0 ) suffix = '';
    var decimalScale = props.decimalScale;
    var from = changeMeta.from;
    var to = changeMeta.to;
    var start = to.start;
    var end = to.end;
    var ref = getSeparators(props);
    var allowedDecimalSeparators = ref.allowedDecimalSeparators;
    var decimalSeparator = ref.decimalSeparator;
    var isBeforeDecimalSeparator = value[end] === decimalSeparator;
    /**
     * If only a number is added on empty input which matches with the prefix or suffix,
     * then don't remove it, just return the same
     */
    if (charIsNumber(value) &&
        (value === prefix || value === suffix) &&
        changeMeta.lastValue === '') {
        return value;
    }
    /** Check for any allowed decimal separator is added in the numeric format and replace it with decimal separator */
    if (end - start === 1 && allowedDecimalSeparators.indexOf(value[start]) !== -1) {
        var separator = decimalScale === 0 ? '' : decimalSeparator;
        value = value.substring(0, start) + separator + value.substring(start + 1, value.length);
    }
    var stripNegation = function (value, start, end) {
        /**
         * if prefix starts with - we don't allow negative number to avoid confusion
         * if suffix starts with - and the value length is same as suffix length, then the - sign is from the suffix
         * In other cases, if the value starts with - then it is a negation
         */
        var hasNegation = false;
        var hasDoubleNegation = false;
        if (prefix.startsWith('-')) {
            hasNegation = false;
        }
        else if (value.startsWith('--')) {
            hasNegation = false;
            hasDoubleNegation = true;
        }
        else if (suffix.startsWith('-') && value.length === suffix.length) {
            hasNegation = false;
        }
        else if (value[0] === '-') {
            hasNegation = true;
        }
        var charsToRemove = hasNegation ? 1 : 0;
        if (hasDoubleNegation)
            { charsToRemove = 2; }
        // remove negation/double negation from start to simplify prefix logic as negation comes before prefix
        if (charsToRemove) {
            value = value.substring(charsToRemove);
            // account for the removal of the negation for start and end index
            start -= charsToRemove;
            end -= charsToRemove;
        }
        return { value: value, start: start, end: end, hasNegation: hasNegation };
    };
    var toMetadata = stripNegation(value, start, end);
    var hasNegation = toMetadata.hasNegation;
    ((assign = toMetadata, value = assign.value, start = assign.start, end = assign.end));
    var ref$1 = stripNegation(changeMeta.lastValue, from.start, from.end);
    var fromStart = ref$1.start;
    var fromEnd = ref$1.end;
    var lastValue = ref$1.value;
    // if only prefix and suffix part is updated reset the value to last value
    // if the changed range is from suffix in the updated value, and the the suffix starts with the same characters, allow the change
    var updatedSuffixPart = value.substring(start, end);
    if (value.length &&
        lastValue.length &&
        (fromStart > lastValue.length - suffix.length || fromEnd < prefix.length) &&
        !(updatedSuffixPart && suffix.startsWith(updatedSuffixPart))) {
        value = lastValue;
    }
    /**
     * remove prefix
     * Remove whole prefix part if its present on the value
     * If the prefix is partially deleted (in which case change start index will be less the prefix length)
     * Remove only partial part of prefix.
     */
    var startIndex = 0;
    if (value.startsWith(prefix))
        { startIndex += prefix.length; }
    else if (start < prefix.length)
        { startIndex = start; }
    value = value.substring(startIndex);
    // account for deleted prefix for end
    end -= startIndex;
    /**
     * Remove suffix
     * Remove whole suffix part if its present on the value
     * If the suffix is partially deleted (in which case change end index will be greater than the suffixStartIndex)
     * remove the partial part of suffix
     */
    var endIndex = value.length;
    var suffixStartIndex = value.length - suffix.length;
    if (value.endsWith(suffix))
        { endIndex = suffixStartIndex; }
    // if the suffix is removed from the end
    else if (end > suffixStartIndex)
        { endIndex = end; }
    // if the suffix is removed from start
    else if (end > value.length - suffix.length)
        { endIndex = end; }
    value = value.substring(0, endIndex);
    // add the negation back and handle for double negation
    value = handleNegation(hasNegation ? ("-" + value) : value, allowNegative);
    // remove non numeric characters
    value = (value.match(getNumberRegex(decimalSeparator, true)) || []).join('');
    // replace the decimalSeparator with ., and only keep the first separator, ignore following ones
    var firstIndex = value.indexOf(decimalSeparator);
    value = value.replace(new RegExp(escapeRegExp(decimalSeparator), 'g'), function (match, index) {
        return index === firstIndex ? '.' : '';
    });
    //check if beforeDecimal got deleted and there is nothing after decimal,
    //clear all numbers in such case while keeping the - sign
    var ref$2 = splitDecimal(value, allowNegative);
    var beforeDecimal = ref$2.beforeDecimal;
    var afterDecimal = ref$2.afterDecimal;
    var addNegation = ref$2.addNegation; // eslint-disable-line prefer-const
    //clear only if something got deleted before decimal (cursor is before decimal)
    if (to.end - to.start < from.end - from.start &&
        beforeDecimal === '' &&
        isBeforeDecimalSeparator &&
        !parseFloat(afterDecimal)) {
        value = addNegation ? '-' : '';
    }
    return value;
}
function getCaretBoundary(formattedValue, props) {
    var prefix = props.prefix; if ( prefix === void 0 ) prefix = '';
    var suffix = props.suffix; if ( suffix === void 0 ) suffix = '';
    var boundaryAry = Array.from({ length: formattedValue.length + 1 }).map(function () { return true; });
    var hasNegation = formattedValue[0] === '-';
    // fill for prefix and negation
    boundaryAry.fill(false, 0, prefix.length + (hasNegation ? 1 : 0));
    // fill for suffix
    var valLn = formattedValue.length;
    boundaryAry.fill(false, valLn - suffix.length + 1, valLn + 1);
    return boundaryAry;
}
function validateAndUpdateProps(props) {
    var ref = getSeparators(props);
    var thousandSeparator = ref.thousandSeparator;
    var decimalSeparator = ref.decimalSeparator;
    // eslint-disable-next-line prefer-const
    var prefix = props.prefix; if ( prefix === void 0 ) prefix = '';
    var allowNegative = props.allowNegative; if ( allowNegative === void 0 ) allowNegative = true;
    if (thousandSeparator === decimalSeparator) {
        throw new Error(("\n        Decimal separator can't be same as thousand separator.\n        thousandSeparator: " + thousandSeparator + " (thousandSeparator = {true} is same as thousandSeparator = \",\")\n        decimalSeparator: " + decimalSeparator + " (default value for decimalSeparator is .)\n     "));
    }
    if (prefix.startsWith('-') && allowNegative) {
        // TODO: throw error in next major version
        console.error(("\n      Prefix can't start with '-' when allowNegative is true.\n      prefix: " + prefix + "\n      allowNegative: " + allowNegative + "\n    "));
        allowNegative = false;
    }
    return Object.assign(Object.assign({}, props), { allowNegative: allowNegative });
}
function useNumericFormat(props) {
    // validate props
    props = validateAndUpdateProps(props);
    var _decimalSeparator = props.decimalSeparator;
    var _allowedDecimalSeparators = props.allowedDecimalSeparators;
    var thousandsGroupStyle = props.thousandsGroupStyle;
    var suffix = props.suffix;
    var allowNegative = props.allowNegative;
    var allowLeadingZeros = props.allowLeadingZeros;
    var onKeyDown = props.onKeyDown; if ( onKeyDown === void 0 ) onKeyDown = noop;
    var onBlur = props.onBlur; if ( onBlur === void 0 ) onBlur = noop;
    var thousandSeparator = props.thousandSeparator;
    var decimalScale = props.decimalScale;
    var fixedDecimalScale = props.fixedDecimalScale;
    var prefix = props.prefix; if ( prefix === void 0 ) prefix = '';
    var defaultValue = props.defaultValue;
    var value = props.value;
    var valueIsNumericString = props.valueIsNumericString;
    var onValueChange = props.onValueChange;
    var restProps = __rest(props, ["decimalSeparator", "allowedDecimalSeparators", "thousandsGroupStyle", "suffix", "allowNegative", "allowLeadingZeros", "onKeyDown", "onBlur", "thousandSeparator", "decimalScale", "fixedDecimalScale", "prefix", "defaultValue", "value", "valueIsNumericString", "onValueChange"]);
    // get derived decimalSeparator and allowedDecimalSeparators
    var ref = getSeparators(props);
    var decimalSeparator = ref.decimalSeparator;
    var allowedDecimalSeparators = ref.allowedDecimalSeparators;
    var _format = function (numStr) { return format(numStr, props); };
    var _removeFormatting = function (inputValue, changeMeta) { return removeFormatting(inputValue, changeMeta, props); };
    var _value = isNil(value) ? defaultValue : value;
    // try to figure out isValueNumericString based on format prop and value
    var _valueIsNumericString = valueIsNumericString !== null && valueIsNumericString !== void 0 ? valueIsNumericString : isNumericString(_value, prefix, suffix);
    if (!isNil(value)) {
        _valueIsNumericString = _valueIsNumericString || typeof value === 'number';
    }
    else if (!isNil(defaultValue)) {
        _valueIsNumericString = _valueIsNumericString || typeof defaultValue === 'number';
    }
    var roundIncomingValueToPrecision = function (value) {
        if (isNotValidValue(value))
            { return value; }
        if (typeof value === 'number') {
            value = toNumericString(value);
        }
        /**
         * only round numeric or float string values coming through props,
         * we don't need to do it for onChange events, as we want to prevent typing there
         */
        if (_valueIsNumericString && typeof decimalScale === 'number') {
            return roundToPrecision(value, decimalScale, Boolean(fixedDecimalScale));
        }
        return value;
    };
    var ref$1 = useInternalValues(roundIncomingValueToPrecision(value), roundIncomingValueToPrecision(defaultValue), Boolean(_valueIsNumericString), _format, _removeFormatting, onValueChange);
    var ref$1_0 = ref$1[0];
    var numAsString = ref$1_0.numAsString;
    var formattedValue = ref$1_0.formattedValue;
    var _onValueChange = ref$1[1];
    var _onKeyDown = function (e) {
        var el = e.target;
        var key = e.key;
        var selectionStart = el.selectionStart;
        var selectionEnd = el.selectionEnd;
        var value = el.value; if ( value === void 0 ) value = '';
        // if multiple characters are selected and user hits backspace, no need to handle anything manually
        if (selectionStart !== selectionEnd) {
            onKeyDown(e);
            return;
        }
        // if user hits backspace, while the cursor is before prefix, and the input has negation, remove the negation
        if (key === 'Backspace' &&
            value[0] === '-' &&
            selectionStart === prefix.length + 1 &&
            allowNegative) {
            // bring the cursor to after negation
            setCaretPosition(el, 1);
        }
        // don't allow user to delete decimal separator when decimalScale and fixedDecimalScale is set
        if (decimalScale && fixedDecimalScale) {
            if (key === 'Backspace' && value[selectionStart - 1] === decimalSeparator) {
                setCaretPosition(el, selectionStart - 1);
                e.preventDefault();
            }
            else if (key === 'Delete' && value[selectionStart] === decimalSeparator) {
                e.preventDefault();
            }
        }
        // if user presses the allowed decimal separator before the separator, move the cursor after the separator
        if ((allowedDecimalSeparators === null || allowedDecimalSeparators === void 0 ? void 0 : allowedDecimalSeparators.includes(key)) && value[selectionStart] === decimalSeparator) {
            setCaretPosition(el, selectionStart + 1);
        }
        var _thousandSeparator = thousandSeparator === true ? ',' : thousandSeparator;
        // move cursor when delete or backspace is pressed before/after thousand separator
        if (key === 'Backspace' && value[selectionStart - 1] === _thousandSeparator) {
            setCaretPosition(el, selectionStart - 1);
        }
        if (key === 'Delete' && value[selectionStart] === _thousandSeparator) {
            setCaretPosition(el, selectionStart + 1);
        }
        onKeyDown(e);
    };
    var _onBlur = function (e) {
        var _value = numAsString;
        // if there no no numeric value, clear the input
        if (!_value.match(/\d/g)) {
            _value = '';
        }
        // clear leading 0s
        if (!allowLeadingZeros) {
            _value = fixLeadingZero(_value);
        }
        // apply fixedDecimalScale on blur event
        if (fixedDecimalScale && decimalScale) {
            _value = roundToPrecision(_value, decimalScale, fixedDecimalScale);
        }
        if (_value !== numAsString) {
            var formattedValue = format(_value, props);
            _onValueChange({
                formattedValue: formattedValue,
                value: _value,
                floatValue: parseFloat(_value),
            }, {
                event: e,
                source: SourceType.event,
            });
        }
        onBlur(e);
    };
    var isValidInputCharacter = function (inputChar) {
        if (inputChar === decimalSeparator)
            { return true; }
        return charIsNumber(inputChar);
    };
    var isCharacterSame = function (ref) {
        var currentValue = ref.currentValue;
        var lastValue = ref.lastValue;
        var formattedValue = ref.formattedValue;
        var currentValueIndex = ref.currentValueIndex;
        var formattedValueIndex = ref.formattedValueIndex;

        var curChar = currentValue[currentValueIndex];
        var newChar = formattedValue[formattedValueIndex];
        /**
         * NOTE: as thousand separator and allowedDecimalSeparators can be same, we need to check on
         * typed range if we have typed any character from allowedDecimalSeparators, in that case we
         * consider different characters like , and . same within the range of updated value.
         */
        var typedRange = findChangeRange(lastValue, currentValue);
        var to = typedRange.to;
        if (currentValueIndex >= to.start &&
            currentValueIndex < to.end &&
            allowedDecimalSeparators &&
            allowedDecimalSeparators.includes(curChar) &&
            newChar === decimalSeparator) {
            return true;
        }
        return curChar === newChar;
    };
    return Object.assign(Object.assign({}, restProps), { value: formattedValue, valueIsNumericString: false, isValidInputCharacter: isValidInputCharacter,
        isCharacterSame: isCharacterSame, onValueChange: _onValueChange, format: _format, removeFormatting: _removeFormatting, getCaretBoundary: function (formattedValue) { return getCaretBoundary(formattedValue, props); }, onKeyDown: _onKeyDown, onBlur: _onBlur });
}
function NumericFormat(props) {
    var numericFormatProps = useNumericFormat(props);
    return react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement(NumberFormatBase, Object.assign({}, numericFormatProps));
}

function format$1(numStr, props) {
    var format = props.format;
    var allowEmptyFormatting = props.allowEmptyFormatting;
    var mask = props.mask;
    var patternChar = props.patternChar; if ( patternChar === void 0 ) patternChar = '#';
    if (numStr === '' && !allowEmptyFormatting)
        { return ''; }
    var hashCount = 0;
    var formattedNumberAry = format.split('');
    for (var i = 0, ln = format.length; i < ln; i++) {
        if (format[i] === patternChar) {
            formattedNumberAry[i] = numStr[hashCount] || getMaskAtIndex(mask, hashCount);
            hashCount += 1;
        }
    }
    return formattedNumberAry.join('');
}
function removeFormatting$1(value, changeMeta, props) {
    if ( changeMeta === void 0 ) changeMeta = getDefaultChangeMeta(value);

    var format = props.format;
    var patternChar = props.patternChar; if ( patternChar === void 0 ) patternChar = '#';
    var from = changeMeta.from;
    var to = changeMeta.to;
    var lastValue = changeMeta.lastValue; if ( lastValue === void 0 ) lastValue = '';
    var isNumericSlot = function (caretPos) { return format[caretPos] === patternChar; };
    var removeFormatChar = function (string, startIndex) {
        var str = '';
        for (var i = 0; i < string.length; i++) {
            if (isNumericSlot(startIndex + i) && charIsNumber(string[i])) {
                str += string[i];
            }
        }
        return str;
    };
    var extractNumbers = function (str) { return str.replace(/[^0-9]/g, ''); };
    // if format doesn't have any number, remove all the non numeric characters
    if (!format.match(/\d/)) {
        return extractNumbers(value);
    }
    /**
     * if user paste the whole formatted text in an empty input, check if matches to the pattern
     * and remove the format characters, if there is a mismatch on the pattern, do plane number extract
     */
    if (lastValue === '' && value.length === format.length) {
        var str = '';
        for (var i = 0; i < value.length; i++) {
            if (isNumericSlot(i)) {
                if (charIsNumber(value[i])) {
                    str += value[i];
                }
            }
            else if (value[i] !== format[i]) {
                // if there is a mismatch on the pattern, do plane number extract
                return extractNumbers(value);
            }
        }
        return str;
    }
    /**
     * For partial change,
     * where ever there is a change on the input, we can break the number in three parts
     * 1st: left part which is unchanged
     * 2nd: middle part which is changed
     * 3rd: right part which is unchanged
     *
     * The first and third section will be same as last value, only the middle part will change
     * We can consider on the change part all the new characters are non format characters.
     * And on the first and last section it can have partial format characters.
     *
     * We pick first and last section from the lastValue (as that has 1-1 mapping with format)
     * and middle one from the update value.
     */
    var firstSection = lastValue.substring(0, from.start);
    var middleSection = value.substring(to.start, to.end);
    var lastSection = lastValue.substring(from.end);
    return ("" + (removeFormatChar(firstSection, 0)) + (extractNumbers(middleSection)) + (removeFormatChar(lastSection, from.end)));
}
function getCaretBoundary$1(formattedValue, props) {
    var format = props.format;
    var mask = props.mask;
    var patternChar = props.patternChar; if ( patternChar === void 0 ) patternChar = '#';
    var boundaryAry = Array.from({ length: formattedValue.length + 1 }).map(function () { return true; });
    var hashCount = 0;
    var firstEmptySlot = -1;
    var maskAndIndexMap = {};
    format.split('').forEach(function (char, index) {
        var maskAtIndex = undefined;
        if (char === patternChar) {
            hashCount++;
            maskAtIndex = getMaskAtIndex(mask, hashCount - 1);
            if (firstEmptySlot === -1 && formattedValue[index] === maskAtIndex) {
                firstEmptySlot = index;
            }
        }
        maskAndIndexMap[index] = maskAtIndex;
    });
    var isPosAllowed = function (pos) {
        // the position is allowed if the position is not masked and valid number area
        return format[pos] === patternChar && formattedValue[pos] !== maskAndIndexMap[pos];
    };
    for (var i = 0, ln = boundaryAry.length; i < ln; i++) {
        // consider caret to be in boundary if it is before or after numeric value
        // Note: on pattern based format its denoted by patternCharacter
        // we should also allow user to put cursor on first empty slot
        boundaryAry[i] = i === firstEmptySlot || isPosAllowed(i) || isPosAllowed(i - 1);
    }
    // the first patternChar position is always allowed
    boundaryAry[format.indexOf(patternChar)] = true;
    return boundaryAry;
}
function validateProps(props) {
    var mask = props.mask;
    if (mask) {
        var maskAsStr = mask === 'string' ? mask : mask.toString();
        if (maskAsStr.match(/\d/g)) {
            throw new Error(("Mask " + mask + " should not contain numeric character;"));
        }
    }
}
function isNumericString$1(val, format) {
    //we can treat empty string as numeric string
    if (val === '')
        { return true; }
    return !(format === null || format === void 0 ? void 0 : format.match(/\d/)) && typeof val === 'string' && (!!val.match(/^\d+$/) || val === '');
}
function usePatternFormat(props) {
    var mask = props.mask;
    var allowEmptyFormatting = props.allowEmptyFormatting;
    var formatProp = props.format;
    var inputMode = props.inputMode; if ( inputMode === void 0 ) inputMode = 'numeric';
    var onKeyDown = props.onKeyDown; if ( onKeyDown === void 0 ) onKeyDown = noop;
    var patternChar = props.patternChar; if ( patternChar === void 0 ) patternChar = '#';
    var value = props.value;
    var defaultValue = props.defaultValue;
    var valueIsNumericString = props.valueIsNumericString;
    var restProps = __rest(props, ["mask", "allowEmptyFormatting", "format", "inputMode", "onKeyDown", "patternChar", "value", "defaultValue", "valueIsNumericString"]);
    // validate props
    validateProps(props);
    var _getCaretBoundary = function (formattedValue) {
        return getCaretBoundary$1(formattedValue, props);
    };
    var _onKeyDown = function (e) {
        var key = e.key;
        var el = e.target;
        var selectionStart = el.selectionStart;
        var selectionEnd = el.selectionEnd;
        var value = el.value;
        // if multiple characters are selected and user hits backspace, no need to handle anything manually
        if (selectionStart !== selectionEnd) {
            onKeyDown(e);
            return;
        }
        // bring the cursor to closest numeric section
        var caretPos = selectionStart;
        // if backspace is pressed after the format characters, bring it to numeric section
        // if delete is pressed before the format characters, bring it to numeric section
        if (key === 'Backspace' || key === 'Delete') {
            var direction = 'right';
            if (key === 'Backspace') {
                while (caretPos > 0 && formatProp[caretPos - 1] !== patternChar) {
                    caretPos--;
                }
                direction = 'left';
            }
            else {
                var formatLn = formatProp.length;
                while (caretPos < formatLn && formatProp[caretPos] !== patternChar) {
                    caretPos++;
                }
                direction = 'right';
            }
            caretPos = getCaretPosInBoundary(value, caretPos, _getCaretBoundary(value), direction);
        }
        else if (formatProp[caretPos] !== patternChar &&
            key !== 'ArrowLeft' &&
            key !== 'ArrowRight') {
            // if user is typing on format character position, bring user to next allowed caret position
            caretPos = getCaretPosInBoundary(value, caretPos + 1, _getCaretBoundary(value), 'right');
        }
        // if we changing caret position, set the caret position
        if (caretPos !== selectionStart) {
            setCaretPosition(el, caretPos);
        }
        onKeyDown(e);
    };
    // try to figure out isValueNumericString based on format prop and value
    var _value = isNil(value) ? defaultValue : value;
    var isValueNumericString = valueIsNumericString !== null && valueIsNumericString !== void 0 ? valueIsNumericString : isNumericString$1(_value, formatProp);
    var _props = Object.assign(Object.assign({}, props), { valueIsNumericString: isValueNumericString });
    return Object.assign(Object.assign({}, restProps), { value: value,
        defaultValue: defaultValue, valueIsNumericString: isValueNumericString, inputMode: inputMode, format: function (numStr) { return format$1(numStr, _props); }, removeFormatting: function (inputValue, changeMeta) { return removeFormatting$1(inputValue, changeMeta, _props); }, getCaretBoundary: _getCaretBoundary, onKeyDown: _onKeyDown });
}
function PatternFormat(props) {
    var patternFormatProps = usePatternFormat(props);
    return react__WEBPACK_IMPORTED_MODULE_0___default.a.createElement(NumberFormatBase, Object.assign({}, patternFormatProps));
}




/***/ }),

/***/ "cDf5":
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  return (module.exports = _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports), _typeof(obj);
}
module.exports = _typeof, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "lSNA":
/***/ (function(module, exports, __webpack_require__) {

var toPropertyKey = __webpack_require__("o5UB");
function _defineProperty(obj, key, value) {
  key = toPropertyKey(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}
module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "o5PN":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
function _extends() {
  _extends = Object.assign ? Object.assign.bind() : function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];
      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }
    return target;
  };
  return _extends.apply(this, arguments);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;
  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }
  return target;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }
  return self;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };
  return _setPrototypeOf(o, p);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  _setPrototypeOf(subClass, superClass);
}
// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__("cDcd");

// EXTERNAL MODULE: external "tribe.modules.propTypes"
var external_tribe_modules_propTypes_ = __webpack_require__("rf6O");

// CONCATENATED MODULE: ./node_modules/react-textarea-autosize/dist/react-textarea-autosize.esm.browser.js







var isIE =  !!document.documentElement.currentStyle ;
var HIDDEN_TEXTAREA_STYLE = {
  'min-height': '0',
  'max-height': 'none',
  height: '0',
  visibility: 'hidden',
  overflow: 'hidden',
  position: 'absolute',
  'z-index': '-1000',
  top: '0',
  right: '0'
};
var SIZING_STYLE = ['letter-spacing', 'line-height', 'font-family', 'font-weight', 'font-size', 'font-style', 'tab-size', 'text-rendering', 'text-transform', 'width', 'text-indent', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width', 'box-sizing'];
var computedStyleCache = {};
var hiddenTextarea =  document.createElement('textarea');

var forceHiddenStyles = function forceHiddenStyles(node) {
  Object.keys(HIDDEN_TEXTAREA_STYLE).forEach(function (key) {
    node.style.setProperty(key, HIDDEN_TEXTAREA_STYLE[key], 'important');
  });
};

{
  hiddenTextarea.setAttribute('tab-index', '-1');
  hiddenTextarea.setAttribute('aria-hidden', 'true');
  forceHiddenStyles(hiddenTextarea);
}

function calculateNodeHeight(uiTextNode, uid, useCache, minRows, maxRows) {
  if (useCache === void 0) {
    useCache = false;
  }

  if (minRows === void 0) {
    minRows = null;
  }

  if (maxRows === void 0) {
    maxRows = null;
  }

  if (hiddenTextarea.parentNode === null) {
    document.body.appendChild(hiddenTextarea);
  } // Copy all CSS properties that have an impact on the height of the content in
  // the textbox


  var nodeStyling = calculateNodeStyling(uiTextNode, uid, useCache);

  if (nodeStyling === null) {
    return null;
  }

  var paddingSize = nodeStyling.paddingSize,
      borderSize = nodeStyling.borderSize,
      boxSizing = nodeStyling.boxSizing,
      sizingStyle = nodeStyling.sizingStyle; // Need to have the overflow attribute to hide the scrollbar otherwise
  // text-lines will not calculated properly as the shadow will technically be
  // narrower for content

  Object.keys(sizingStyle).forEach(function (key) {
    hiddenTextarea.style[key] = sizingStyle[key];
  });
  forceHiddenStyles(hiddenTextarea);
  hiddenTextarea.value = uiTextNode.value || uiTextNode.placeholder || 'x';
  var minHeight = -Infinity;
  var maxHeight = Infinity;
  var height = hiddenTextarea.scrollHeight;

  if (boxSizing === 'border-box') {
    // border-box: add border, since height = content + padding + border
    height = height + borderSize;
  } else if (boxSizing === 'content-box') {
    // remove padding, since height = content
    height = height - paddingSize;
  } // measure height of a textarea with a single row


  hiddenTextarea.value = 'x';
  var singleRowHeight = hiddenTextarea.scrollHeight - paddingSize; // Stores the value's rows count rendered in `hiddenTextarea`,
  // regardless if `maxRows` or `minRows` props are passed

  var valueRowCount = Math.floor(height / singleRowHeight);

  if (minRows !== null) {
    minHeight = singleRowHeight * minRows;

    if (boxSizing === 'border-box') {
      minHeight = minHeight + paddingSize + borderSize;
    }

    height = Math.max(minHeight, height);
  }

  if (maxRows !== null) {
    maxHeight = singleRowHeight * maxRows;

    if (boxSizing === 'border-box') {
      maxHeight = maxHeight + paddingSize + borderSize;
    }

    height = Math.min(maxHeight, height);
  }

  var rowCount = Math.floor(height / singleRowHeight);
  return {
    height: height,
    minHeight: minHeight,
    maxHeight: maxHeight,
    rowCount: rowCount,
    valueRowCount: valueRowCount
  };
}

function calculateNodeStyling(node, uid, useCache) {
  if (useCache === void 0) {
    useCache = false;
  }

  if (useCache && computedStyleCache[uid]) {
    return computedStyleCache[uid];
  }

  var style = window.getComputedStyle(node);

  if (style === null) {
    return null;
  }

  var sizingStyle = SIZING_STYLE.reduce(function (obj, name) {
    obj[name] = style.getPropertyValue(name);
    return obj;
  }, {});
  var boxSizing = sizingStyle['box-sizing']; // probably node is detached from DOM, can't read computed dimensions

  if (boxSizing === '') {
    return null;
  } // IE (Edge has already correct behaviour) returns content width as computed width
  // so we need to add manually padding and border widths


  if (isIE && boxSizing === 'border-box') {
    sizingStyle.width = parseFloat(sizingStyle.width) + parseFloat(style['border-right-width']) + parseFloat(style['border-left-width']) + parseFloat(style['padding-right']) + parseFloat(style['padding-left']) + 'px';
  }

  var paddingSize = parseFloat(sizingStyle['padding-bottom']) + parseFloat(sizingStyle['padding-top']);
  var borderSize = parseFloat(sizingStyle['border-bottom-width']) + parseFloat(sizingStyle['border-top-width']);
  var nodeInfo = {
    sizingStyle: sizingStyle,
    paddingSize: paddingSize,
    borderSize: borderSize,
    boxSizing: boxSizing
  };

  if (useCache) {
    computedStyleCache[uid] = nodeInfo;
  }

  return nodeInfo;
}

var purgeCache = function purgeCache(uid) {
  delete computedStyleCache[uid];
};

var noop = function noop() {};

var uid = 0;

var react_textarea_autosize_esm_browser_TextareaAutosize =
/*#__PURE__*/
function (_React$Component) {
  _inheritsLoose(TextareaAutosize, _React$Component);

  function TextareaAutosize(props) {
    var _this;

    _this = _React$Component.call(this, props) || this;

    _this._onRef = function (node) {
      _this._ref = node;
      var inputRef = _this.props.inputRef;

      if (typeof inputRef === 'function') {
        inputRef(node);
        return;
      }

      inputRef.current = node;
    };

    _this._onChange = function (event) {
      if (!_this._controlled) {
        _this._resizeComponent();
      }

      _this.props.onChange(event, _assertThisInitialized(_this));
    };

    _this._resizeComponent = function (callback) {
      if (callback === void 0) {
        callback = noop;
      }

      var nodeHeight = calculateNodeHeight(_this._ref, _this._uid, _this.props.useCacheForDOMMeasurements, _this.props.minRows, _this.props.maxRows);

      if (nodeHeight === null) {
        callback();
        return;
      }

      var height = nodeHeight.height,
          minHeight = nodeHeight.minHeight,
          maxHeight = nodeHeight.maxHeight,
          rowCount = nodeHeight.rowCount,
          valueRowCount = nodeHeight.valueRowCount;
      _this.rowCount = rowCount;
      _this.valueRowCount = valueRowCount;

      if (_this.state.height !== height || _this.state.minHeight !== minHeight || _this.state.maxHeight !== maxHeight) {
        _this.setState({
          height: height,
          minHeight: minHeight,
          maxHeight: maxHeight
        }, callback);

        return;
      }

      callback();
    };

    _this.state = {
      height: props.style && props.style.height || 0,
      minHeight: -Infinity,
      maxHeight: Infinity
    };
    _this._uid = uid++;
    _this._controlled = props.value !== undefined;
    _this._resizeLock = false;
    return _this;
  }

  var _proto = TextareaAutosize.prototype;

  _proto.render = function render() {
    var _this$props = this.props,
        _inputRef = _this$props.inputRef,
        _maxRows = _this$props.maxRows,
        _minRows = _this$props.minRows,
        _onHeightChange = _this$props.onHeightChange,
        _useCacheForDOMMeasurements = _this$props.useCacheForDOMMeasurements,
        props = _objectWithoutPropertiesLoose(_this$props, ["inputRef", "maxRows", "minRows", "onHeightChange", "useCacheForDOMMeasurements"]);

    props.style = _extends({}, props.style, {
      height: this.state.height
    });
    var maxHeight = Math.max(props.style.maxHeight || Infinity, this.state.maxHeight);

    if (maxHeight < this.state.height) {
      props.style.overflow = 'hidden';
    }

    return Object(external_React_["createElement"])("textarea", _extends({}, props, {
      onChange: this._onChange,
      ref: this._onRef
    }));
  };

  _proto.componentDidMount = function componentDidMount() {
    var _this2 = this;

    this._resizeComponent(); // Working around Firefox bug which runs resize listeners even when other JS is running at the same moment
    // causing competing rerenders (due to setState in the listener) in React.
    // More can be found here - facebook/react#6324


    this._resizeListener = function () {
      if (_this2._resizeLock) {
        return;
      }

      _this2._resizeLock = true;

      _this2._resizeComponent(function () {
        _this2._resizeLock = false;
      });
    };

    window.addEventListener('resize', this._resizeListener);
  };

  _proto.componentDidUpdate = function componentDidUpdate(prevProps, prevState) {
    if (prevProps !== this.props) {
      this._resizeComponent();
    }

    if (this.state.height !== prevState.height) {
      this.props.onHeightChange(this.state.height, this);
    }
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    window.removeEventListener('resize', this._resizeListener);
    purgeCache(this._uid);
  };

  return TextareaAutosize;
}(external_React_["Component"]);

react_textarea_autosize_esm_browser_TextareaAutosize.defaultProps = {
  inputRef: noop,
  onChange: noop,
  onHeightChange: noop,
  useCacheForDOMMeasurements: false
};
 false ? undefined : void 0;

/* harmony default export */ var react_textarea_autosize_esm_browser = __webpack_exports__["a"] = (react_textarea_autosize_esm_browser_TextareaAutosize);


/***/ }),

/***/ "o5UB":
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__("cDf5")["default"];
var toPrimitive = __webpack_require__("5Q0V");
function _toPropertyKey(arg) {
  var key = toPrimitive(arg, "string");
  return _typeof(key) === "symbol" ? key : String(key);
}
module.exports = _toPropertyKey, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "pVnL":
/***/ (function(module, exports) {

function _extends() {
  module.exports = _extends = Object.assign ? Object.assign.bind() : function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];
      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }
    return target;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  return _extends.apply(this, arguments);
}
module.exports = _extends, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "zJgK":
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(process) {/* 
(The MIT License)
Copyright (c) 2014-2021 Halász Ádám <adam@aimform.com>
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

//  Unique Hexatridecimal ID Generator
// ================================================

//  Dependencies
// ================================================
var pid = typeof process !== 'undefined' && process.pid ? process.pid.toString(36) : '' ;
var address = '';
if(false){ var i, networkInterfaces, mac, os; } 

//  Exports
// ================================================
module.exports = module.exports.default = function(prefix, suffix){ return (prefix ? prefix : '') + address + pid + now().toString(36) + (suffix ? suffix : ''); }
module.exports.process = function(prefix, suffix){ return (prefix ? prefix : '') + pid + now().toString(36) + (suffix ? suffix : ''); }
module.exports.time    = function(prefix, suffix){ return (prefix ? prefix : '') + now().toString(36) + (suffix ? suffix : ''); }

//  Helpers
// ================================================
function now(){
    var time = Date.now();
    var last = now.last || time;
    return now.last = time > last ? time : last + 1;
}

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__("8oxB")))

/***/ })

}]);