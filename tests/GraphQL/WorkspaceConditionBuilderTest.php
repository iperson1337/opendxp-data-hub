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

namespace OpenDxp\Bundle\DataHubBundle\Tests\GraphQL;

use OpenDxp\Bundle\DataHubBundle\GraphQL\WorkspaceConditionBuilder;
use PHPUnit\Framework\TestCase;

class WorkspaceConditionBuilderTest extends TestCase
{
    private function builder(): WorkspaceConditionBuilder
    {
        return new WorkspaceConditionBuilder(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'",
            static fn (string $value): string => '`' . $value . '`',
        );
    }

    public function testNoWorkspacesDeniesEverything(): void
    {
        // Исходный подзапрос возвращал NULL, а `NULL = 1` отсекало все строки.
        // Подмена этого на «условия нет» открыла бы весь класс.
        self::assertSame('(0 = 1)', $this->builder()->build('objects', 'key', []));
    }

    public function testRowsWithEmptyCpathAreIgnored(): void
    {
        self::assertSame('(0 = 1)', $this->builder()->build('objects', 'key', [
            ['cpath' => '', 'read' => 1],
            ['cpath' => null, 'read' => 1],
        ]));
    }

    public function testRootReadGrantMakesConditionRedundant(): void
    {
        $condition = $this->builder()->build('objects', 'key', [
            ['cpath' => '/', 'read' => 1],
            ['cpath' => '/Товар/Справочники', 'read' => 1],
        ]);

        self::assertNull($condition);
    }

    public function testRootReadGrantWithADenyRowStillBuildsACondition(): void
    {
        $condition = $this->builder()->build('objects', 'key', [
            ['cpath' => '/', 'read' => 1],
            ['cpath' => '/Товар/Служебное', 'read' => 0],
        ]);

        self::assertNotNull($condition);
        self::assertStringContainsString("LOCATE('/Товар/Служебное'", $condition);
    }

    public function testConditionShape(): void
    {
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/', 'read' => 0],
            ['cpath' => '/Товар/Справочники', 'read' => 1],
        ]);

        $fullpath = 'CONCAT(`t`.`path`, `t`.`key`)';
        $expected = sprintf(
            '((CASE WHEN LOCATE(%1$s, %3$s) = 1 THEN 1 WHEN LOCATE(%2$s, %3$s) = 1 THEN 0 ELSE 0 END) = 1'
            . ' OR (CASE WHEN LOCATE(%3$s, %1$s) = 1 THEN 1 WHEN LOCATE(%3$s, %2$s) = 1 THEN 0 ELSE 0 END) = 1)',
            "'/Товар/Справочники'",
            "'/'",
            $fullpath,
        );

        self::assertSame($expected, $condition);
    }

    public function testLongestCpathWinsByByteLengthLikeMysqlLength(): void
    {
        // MySQL LENGTH() считает байты: '/Товар' — 11 байт против 9 у '/abcdefgh'.
        // Сортировка по символам поставила бы их в обратном порядке.
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/abcdefgh', 'read' => 1],
            ['cpath' => '/Товар', 'read' => 0],
        ]);

        self::assertNotNull($condition);
        self::assertLessThan(
            strpos($condition, "'/abcdefgh'"),
            strpos($condition, "'/Товар'"),
            '/Товар длиннее в байтах и должен проверяться первым'
        );
    }

    public function testAssetListingUsesFilenameColumn(): void
    {
        $condition = $this->builder()->build('assets', 'filename', [
            ['cpath' => '/Сертификаты', 'read' => 1],
        ]);

        self::assertNotNull($condition);
        self::assertStringContainsString('CONCAT(`assets`.`path`, `assets`.`filename`)', $condition);
    }

    public function testReadFlagIsNormalisedToZeroOrOne(): void
    {
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/a', 'read' => '1'],
            ['cpath' => '/bb', 'read' => null],
        ]);

        self::assertNotNull($condition);
        self::assertStringContainsString("LOCATE('/bb', CONCAT(`t`.`path`, `t`.`key`)) = 1 THEN 0", $condition);
        self::assertStringContainsString("LOCATE('/a', CONCAT(`t`.`path`, `t`.`key`)) = 1 THEN 1", $condition);
    }
}
