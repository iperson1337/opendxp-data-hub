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

namespace OpenDxp;

/**
 * Заглушка `OpenDxp\Db` для юнит-тестов без ядра: квотирует как MySQL-драйвер PDO.
 */
final class Db
{
    private static ?object $connection = null;

    public static function get(): object
    {
        return self::$connection ??= new class {
            public function quote(mixed $value): string
            {
                return "'" . addcslashes((string) $value, "\\'\0\n\r\"\x1a") . "'";
            }

            public function quoteIdentifier(string $identifier): string
            {
                return implode('.', array_map(
                    static fn (string $part): string => '`' . str_replace('`', '``', $part) . '`',
                    explode('.', $identifier)
                ));
            }
        };
    }
}
