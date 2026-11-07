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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\AssetType;
use PHPUnit\Framework\TestCase;

/**
 * `url` и `checksum` ассета берутся из ядра (Asset::getFrontendFullPath(), Asset::getChecksum());
 * без ассета в значении — null, а не ошибка.
 */
class AssetUrlChecksumTest extends TestCase
{
    public function testWithoutAssetBothAreNull(): void
    {
        if (!class_exists(\OpenDxp\Model\Asset::class)) {
            self::markTestSkipped('OpenDXP core is not installed');
        }

        $resolver = (new \ReflectionClass(AssetType::class))->newInstanceWithoutConstructor();

        self::assertNull($resolver->resolveUrl(null));
        self::assertNull($resolver->resolveChecksum(null));
    }
}
