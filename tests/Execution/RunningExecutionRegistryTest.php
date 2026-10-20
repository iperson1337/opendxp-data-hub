<?php

declare(strict_types=1);

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

namespace OpenDxp\Bundle\DataHubBundle\Tests\Execution;

use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecution;
use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecutionProviderInterface;
use OpenDxp\Bundle\DataHubBundle\Execution\RunningExecutionRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class RunningExecutionRegistryTest extends TestCase
{
    public function testMergesProvidersSortedByConfigName(): void
    {
        $registry = $this->registry([
            $this->provider([new RunningExecution('products', 3, 10)]),
            $this->provider([new RunningExecution('assets', 1, 2, 'перемещено 5 файлов')]),
        ]);

        $names = array_map(fn (RunningExecution $e) => $e->configName, $registry->getRunningExecutions());

        $this->assertSame(['assets', 'products'], $names);
    }

    public function testCachesProvidersWithinTtl(): void
    {
        $provider = $this->provider([new RunningExecution('products', 3, 10)]);
        $registry = $this->registry([$provider]);

        $registry->getRunningExecutions();
        $registry->getRunningExecutions();

        $this->assertSame(1, $provider->calls);
    }

    public function testZeroTtlBypassesCache(): void
    {
        $provider = $this->provider([]);
        $registry = $this->registry([$provider], cacheTtl: 0);

        $registry->getRunningExecutions();
        $registry->getRunningExecutions();

        $this->assertSame(2, $provider->calls);
    }

    public function testDisabledRegistryDoesNotAskProviders(): void
    {
        $provider = $this->provider([new RunningExecution('products', 3, 10)]);
        $registry = $this->registry([$provider], enabled: false);

        $this->assertSame([], $registry->getRunningExecutions());
        $this->assertSame(0, $provider->calls);
    }

    public function testFailingProviderDoesNotHideOthers(): void
    {
        $failing = new class () implements RunningExecutionProviderInterface {
            public function getRunningExecutions(): iterable
            {
                throw new RuntimeException('queue table is gone');
            }
        };

        $registry = $this->registry([$failing, $this->provider([new RunningExecution('products', 3, 10)])]);

        $this->assertCount(1, $registry->getRunningExecutions());
    }

    public function testProgressIsClampedAndUnknownTotalIsZero(): void
    {
        $this->assertSame(0.3, (new RunningExecution('a', 3, 10))->getProgress());
        $this->assertSame(1.0, (new RunningExecution('a', 12, 10))->getProgress());
        $this->assertSame(0.0, (new RunningExecution('a', 5, 0))->getProgress());
    }

    /**
     * @param list<RunningExecutionProviderInterface> $providers
     */
    private function registry(array $providers, bool $enabled = true, int $cacheTtl = 5): RunningExecutionRegistry
    {
        return new RunningExecutionRegistry($providers, new ArrayAdapter(), new NullLogger(), $enabled, $cacheTtl);
    }

    /**
     * @param list<RunningExecution> $executions
     */
    private function provider(array $executions): CountingRunningExecutionProvider
    {
        return new CountingRunningExecutionProvider($executions);
    }
}
