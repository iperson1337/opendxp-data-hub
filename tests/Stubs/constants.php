<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

// Константы, которые ядро OpenDXP объявляет при загрузке приложения. Нужны PHPStan
// и юнит-тестам, где ядро не поднимается.

if (!defined('OPENDXP_CONFIGURATION_DIRECTORY')) {
    define('OPENDXP_CONFIGURATION_DIRECTORY', sys_get_temp_dir() . '/opendxp-config');
}
