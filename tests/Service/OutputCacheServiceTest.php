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

namespace OpenDxp\Bundle\DataHubBundle\Tests\Service;

use OpenDxp\Bundle\DataHubBundle\Service\OutputCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class OutputCacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // useCache() спрашивает OpenDxp::inDebugMode(), а это ядро
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('isDebug')->willReturn(false);
        \OpenDxp::setKernel($kernel);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(\OpenDxp::class, 'kernel'))->setValue(null, null);
    }

    public function testSmallResponseIsSaved(): void
    {
        $service = $this->createService(['output_cache_max_size' => 1024]);

        $service->save($this->createQueryRequest(), new JsonResponse(['data' => ['x' => 'small']]));

        $this->assertCount(1, $service->saved);
    }

    public function testResponseLargerThanMaxSizeIsNotSaved(): void
    {
        $service = $this->createService(['output_cache_max_size' => 1024]);

        $service->save($this->createQueryRequest(), new JsonResponse(['data' => ['x' => str_repeat('a', 2048)]]));

        $this->assertSame([], $service->saved);
    }

    public function testZeroMaxSizeDisablesLimit(): void
    {
        $service = $this->createService(['output_cache_max_size' => 0]);

        $service->save($this->createQueryRequest(), new JsonResponse(['data' => ['x' => str_repeat('a', 2048)]]));

        $this->assertCount(1, $service->saved);
    }

    public function testDefaultMaxSizeIsFiveMegabytes(): void
    {
        $service = $this->createService([]);

        $service->save($this->createQueryRequest(), new JsonResponse(['data' => ['x' => str_repeat('a', 5 * 1024 * 1024)]]));
        $this->assertSame([], $service->saved);

        $service->save($this->createQueryRequest(), new JsonResponse(['data' => ['x' => str_repeat('a', 1024 * 1024)]]));
        $this->assertCount(1, $service->saved);
    }

    /**
     * @param array<string, mixed> $graphqlConfig
     */
    private function createService(array $graphqlConfig): RecordingOutputCacheService
    {
        $container = new ContainerBuilder();
        $container->setParameter('opendxp_data_hub', [
            'graphql' => ['output_cache_enabled' => true] + $graphqlConfig,
        ]);

        return new RecordingOutputCacheService(new ContainerBag($container), new EventDispatcher());
    }

    private function createQueryRequest(): Request
    {
        $request = Request::create(
            '/opendxp-graphql-webservices/test',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['query' => '{ getProductListing { totalCount } }']),
        );
        $request->attributes->set('clientname', 'test');

        return $request;
    }
}

/**
 * Вместо записи в кэш запоминает ключи, которые были бы сохранены.
 */
class RecordingOutputCacheService extends OutputCacheService
{
    /** @var list<string> */
    public array $saved = [];

    protected function saveToCache($key, $item, $tags = []): void
    {
        $this->saved[] = $key;
    }
}
