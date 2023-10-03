<?php
namespace TEC\Tickets\phpqrcode;

/*
 * PHP QR Code encoder
 *
 * Common constants
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

	// Encoding modes

	define('TEC_TICKETS_QR_MODE_NUL', -1);
	define('TEC_TICKETS_QR_MODE_NUM', 0);
	define('TEC_TICKETS_QR_MODE_AN', 1);
	define('TEC_TICKETS_QR_MODE_8', 2);
	define('TEC_TICKETS_QR_MODE_KANJI', 3);
	define('TEC_TICKETS_QR_MODE_STRUCTURE', 4);

	// Levels of error correction.

	define('TEC_TICKETS_QR_ECLEVEL_L', 0);
	define('TEC_TICKETS_QR_ECLEVEL_M', 1);
	define('TEC_TICKETS_QR_ECLEVEL_Q', 2);
	define('TEC_TICKETS_QR_ECLEVEL_H', 3);

	// Supported output formats

	define('TEC_TICKETS_QR_FORMAT_TEXT', 0);
	define('TEC_TICKETS_QR_FORMAT_PNG',  1);

	class qrstr {
		public static function set(&$srctab, $x, $y, $repl, $replLen = false) {
			$srctab[$y] = substr_replace($srctab[$y], ($replLen !== false)?substr($repl,0,$replLen):$repl, $x, ($replLen !== false)?$replLen:strlen($repl));
		}
	}