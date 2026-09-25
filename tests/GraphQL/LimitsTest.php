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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Limits;
use PHPUnit\Framework\TestCase;

class LimitsTest extends TestCase
{
    protected function setUp(): void
    {
        Limits::configure(['max_first' => 100, 'query_depth_limit' => 7, 'query_complexity_limit' => 0]);
    }

    protected function tearDown(): void
    {
        Limits::configure(null);
    }

    public function testConfiguredValuesAreRead(): void
    {
        self::assertSame(100, Limits::maxFirst());
        self::assertSame(7, Limits::queryDepth());
        self::assertSame(0, Limits::queryComplexity());
    }

    public function testMissingFirstFallsBackToMaximum(): void
    {
        self::assertSame(100, Limits::first(null));
    }

    public function testFirstWithinLimitIsKept(): void
    {
        self::assertSame(25, Limits::first(25));
    }

    public function testFirstAboveLimitIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        Limits::first(101);
    }

    public function testNegativeFirstIsRejected(): void
    {
        $this->expectException(ClientSafeException::class);
        Limits::first(-1);
    }

    public function testSortKeysAcceptColumnNames(): void
    {
        self::assertSame(['name', 'o.key'], Limits::assertSortKeys(['name', 'o.key']));
        self::assertSame('modificationDate', Limits::assertSortKeys('modificationDate'));
    }

    public function testSortKeysRejectSqlFragments(): void
    {
        $this->expectException(ClientSafeException::class);
        Limits::assertSortKeys(['name; DROP TABLE objects']);
    }
}
