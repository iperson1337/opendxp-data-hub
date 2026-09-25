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

// Юнит-тесты бандла не требуют ядра OpenDXP: если оно не установлено (composer install
// только с dev-зависимостями), подставляются минимальные заглушки из tests/Stubs.

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

if (!class_exists(\OpenDxp\Db::class)) {
    require __DIR__ . '/Stubs/Db.php';
}

if (!interface_exists(\GraphQL\Error\ClientAware::class)) {
    require __DIR__ . '/Stubs/ClientAware.php';
}
