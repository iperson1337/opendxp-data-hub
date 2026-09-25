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

    /**
     * Эмуляция условия в PHP: проверяем, что SQL-выражение отдаёт для конкретного пути.
     * Поддерживает ровно те конструкции, которые генерирует билдер.
     */
    private function evaluate(string $condition, string $fullpath): bool
    {
        $php = $condition;
        $php = str_replace('CONCAT(`t`.`path`, `t`.`key`)', '$fp', $php);
        $php = preg_replace('/LOCATE\(CONCAT\(\$fp, (\'[^\']*\')\), (\'[^\']*\')\) = 1/', 'str_starts_with($2, $fp . $1)', $php);
        $php = preg_replace('/LOCATE\((\'[^\']*\'), \$fp\) = 1/', 'str_starts_with($fp, $1)', $php);
        $php = preg_replace('/\$fp = (\'[^\']*\')/', '$fp === $1', $php);
        $php = preg_replace_callback(
            '/\(CASE (.*?) ELSE 0 END\)/s',
            static function (array $m): string {
                $whens = preg_split('/\s+WHEN\s+/', ' ' . $m[1], -1, PREG_SPLIT_NO_EMPTY);
                $expr = '0';
                foreach (array_reverse($whens) as $when) {
                    [$cond, $then] = preg_split('/\s+THEN\s+/', $when);
                    $expr = '((' . $cond . ') ? ' . $then . ' : ' . $expr . ')';
                }

                return $expr;
            },
            $php
        );
        $php = str_replace(' = 1 OR ', ' == 1 || ', $php);
        $php = preg_replace('/ = 1\)$/', ' == 1)', $php);

        $fp = $fullpath;

        return (bool) eval('return ' . $php . ';');
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
        self::assertStringContainsString("'/Товар/Служебное'", $condition);
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
        self::assertFalse($this->evaluate($condition, '/bb/x'));
        self::assertTrue($this->evaluate($condition, '/a/x'));
    }

    public function testWorkspaceMatchesOnSegmentBoundaryOnly(): void
    {
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/foo', 'read' => 1],
        ]);

        self::assertNotNull($condition);
        self::assertTrue($this->evaluate($condition, '/foo'), 'сам воркспейс');
        self::assertTrue($this->evaluate($condition, '/foo/bar'), 'потомок воркспейса');
        self::assertFalse($this->evaluate($condition, '/foobar'), 'соседний путь с тем же префиксом');
        self::assertFalse($this->evaluate($condition, '/foobar/baz'));
        self::assertTrue($this->evaluate($condition, '/'), 'корень — предок воркспейса');
        self::assertFalse($this->evaluate($condition, '/other'));
    }

    public function testDenyInsideAllowWinsForDescendantsOnly(): void
    {
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/', 'read' => 1],
            ['cpath' => '/Secret', 'read' => 0],
        ]);

        self::assertNotNull($condition);
        self::assertFalse($this->evaluate($condition, '/Secret'));
        self::assertFalse($this->evaluate($condition, '/Secret/x'));
        self::assertTrue($this->evaluate($condition, '/SecretGarden'), 'соседний путь не попадает под запрет');
        self::assertTrue($this->evaluate($condition, '/Public/x'));
    }

    public function testElementAboveWorkspaceIsVisible(): void
    {
        $condition = $this->builder()->build('t', 'key', [
            ['cpath' => '/Products_2024/Shoes', 'read' => 1],
        ]);

        self::assertNotNull($condition);
        self::assertTrue($this->evaluate($condition, '/Products_2024'), 'папка на пути к воркспейсу');
        self::assertFalse($this->evaluate($condition, '/Products_2024/Bags'));
        self::assertFalse($this->evaluate($condition, '/Products_20'), 'префикс без границы сегмента');
    }

    public function testTrailingSlashInCpathIsNormalised(): void
    {
        $withSlash = $this->builder()->build('t', 'key', [['cpath' => '/foo/', 'read' => 1]]);
        $withoutSlash = $this->builder()->build('t', 'key', [['cpath' => '/foo', 'read' => 1]]);

        self::assertSame($withoutSlash, $withSlash);
    }
}
