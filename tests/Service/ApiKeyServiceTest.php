<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\Tests\Service;

use OpenDxp\Bundle\DataHubBundle\Service\ApiKeyService;
use PHPUnit\Framework\TestCase;

class ApiKeyServiceTest extends TestCase
{
    public function testCanBeConstructedWithoutDatabase(): void
    {
        $service = new ApiKeyService();
        $this->assertInstanceOf(ApiKeyService::class, $service);
    }

    public function testMatchesAnyWithCorrectKey(): void
    {
        $keys = ['aaaaaaaaaaaaaaaa', 'bbbbbbbbbbbbbbbb', 'cccccccccccccccc'];

        $this->assertTrue(ApiKeyService::matchesAny('aaaaaaaaaaaaaaaa', $keys));
        $this->assertTrue(ApiKeyService::matchesAny('bbbbbbbbbbbbbbbb', $keys));
        $this->assertTrue(ApiKeyService::matchesAny('cccccccccccccccc', $keys));
    }

    public function testMatchesAnyWithNoMatch(): void
    {
        $keys = ['aaaaaaaaaaaaaaaa', 'bbbbbbbbbbbbbbbb'];

        $this->assertFalse(ApiKeyService::matchesAny('dddddddddddddddd', $keys));
    }

    public function testMatchesAnyWithEmptyList(): void
    {
        $this->assertFalse(ApiKeyService::matchesAny('aaaaaaaaaaaaaaaa', []));
        $this->assertFalse(ApiKeyService::matchesAny('', []));
    }

    public function testMatchesAnyRejectsPrefixAndSuffixMismatch(): void
    {
        $keys = ['aaaaaaaaaaaaaaaa'];

        $this->assertFalse(ApiKeyService::matchesAny('aaaaaaaaaaaaaaa', $keys), 'prefix must not match');
        $this->assertFalse(ApiKeyService::matchesAny('aaaaaaaaaaaaaaaab', $keys), 'longer candidate must not match');
        $this->assertFalse(ApiKeyService::matchesAny('', $keys), 'empty candidate must not match');
    }

    public function testMatchesAnyIgnoresNonStringKeys(): void
    {
        $keys = [null, 123, ['x'], 'aaaaaaaaaaaaaaaa'];

        $this->assertTrue(ApiKeyService::matchesAny('aaaaaaaaaaaaaaaa', $keys));
        $this->assertFalse(ApiKeyService::matchesAny('123', $keys));
    }

    public function testMatchesAnyIsCaseSensitive(): void
    {
        $this->assertFalse(ApiKeyService::matchesAny('AAAAAAAAAAAAAAAA', ['aaaaaaaaaaaaaaaa']));
    }
}
