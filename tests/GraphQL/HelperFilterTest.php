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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Helper;
use PHPUnit\Framework\TestCase;

/**
 * Фильтр листинга — единственное место, где JSON от потребителя превращается в SQL.
 * Раньше строка внутри фильтра возвращалась в запрос дословно (SQL-инъекция).
 */
class HelperFilterTest extends TestCase
{
    protected function setUp(): void
    {
        // Квотирование как у PDO MySQL, без реального соединения.
        Helper::useConnection(new class {
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
        });
    }

    protected function tearDown(): void
    {
        Helper::useConnection(null);
    }

    private function build(string $json): string
    {
        return Helper::buildSqlCondition('objects', json_decode($json, false, 512, JSON_THROW_ON_ERROR));
    }

    public function testSimpleEquality(): void
    {
        self::assertSame(" ((`objects`.`name` = 'foo') ) ", $this->build('{"name": "foo"}'));
    }

    public function testValuesAreQuoted(): void
    {
        $sql = $this->build('{"name": "x\' OR 1=1 -- "}');

        self::assertStringContainsString("'x\\' OR 1=1 -- '", $sql);
        self::assertStringNotContainsString("= 'x' OR", $sql);
    }

    public function testOperators(): void
    {
        $sql = $this->build('{"price": {"$gt": 10, "$lte": "20"}, "name": {"$like": "%a%"}}');

        self::assertStringContainsString("(`objects`.`price` > '10')", $sql);
        self::assertStringContainsString("(`objects`.`price` <= '20')", $sql);
        self::assertStringContainsString("(`objects`.`name` LIKE '%a%')", $sql);
    }

    public function testInAndOrGroups(): void
    {
        $sql = $this->build('{"$or": [{"id": {"$in": [1, 2]}}, {"key": {"$in": []}}]}');

        self::assertStringContainsString("(`objects`.`id` IN ('1', '2'))", $sql);
        self::assertStringContainsString('(1=0)', $sql);
        self::assertStringContainsString(' OR ', $sql);
    }

    public function testRawStringAtTopLevelIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        Helper::buildSqlCondition('objects', '1=1');
    }

    public function testRawStringInsideOrIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        $this->build('{"$or": "1=1"}');
    }

    public function testRawStringInsideAndListIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        $this->build('{"$and": ["1=1) OR (1=1"]}');
    }

    public function testScalarListForFieldBecomesInCondition(): void
    {
        $sql = $this->build('{"name": ["a", "b) OR (1=1"]}');

        self::assertStringContainsString("(`objects`.`name` IN ('a', 'b) OR (1=1'))", $sql);
    }

    public function testIllegalColumnNameIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        $this->build('{"name` = 1 OR 1=1 -- ": "x"}');
    }

    public function testQualifiedColumnNameIsAllowed(): void
    {
        self::assertStringContainsString('`object_localized_Product_ru`.`name`', $this->build('{"object_localized_Product_ru.name": "x"}'));
    }

    public function testArrayValueForScalarOperatorIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        $this->build('{"name": {"$like": ["x"]}}');
    }

    public function testNullBecomesIsNull(): void
    {
        self::assertStringContainsString('(`objects`.`name` IS NULL)', $this->build('{"name": null}'));
    }
}
