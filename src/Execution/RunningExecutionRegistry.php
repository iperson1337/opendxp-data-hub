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

namespace OpenDxp\Bundle\DataHubBundle\Execution;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * Собирает выполняющиеся конфиги со всех {@see RunningExecutionProviderInterface}.
 *
 * Результат кешируется на `cache_ttl` секунд без привязки к пользователю: сколько бы
 * человек ни держали открытой вкладку Data Hub, поставщики опрашиваются раз в несколько секунд.
 * Права на конкретный конфиг проверяет вызывающий код уже после кеша.
 */
class RunningExecutionRegistry
{
    private const CACHE_KEY = 'opendxp_datahub_running_executions';

    /**
     * @param iterable<RunningExecutionProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(RunningExecutionProviderInterface::TAG)]
        private readonly iterable $providers,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled = true,
        private readonly int $cacheTtl = 5,
    ) {
    }

    /**
     * @return list<RunningExecution> отсортированы по имени конфига
     */
    public function getRunningExecutions(): array
    {
        if (!$this->enabled) {
            return [];
        }

        if ($this->cacheTtl <= 0) {
            return $this->collect();
        }

        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter($this->cacheTtl);

            return $this->collect();
        });
    }

    /**
     * @return list<RunningExecution>
     */
    private function collect(): array
    {
        $executions = [];
        foreach ($this->providers as $provider) {
            // Сломанный поставщик не должен гасить индикатор для остальных адаптеров.
            try {
                foreach ($provider->getRunningExecutions() as $execution) {
                    $executions[$execution->configName] = $execution;
                }
            } catch (Throwable $e) {
                $this->logger->error('Data Hub: не удалось получить выполняющиеся конфиги', [
                    'provider' => $provider::class,
                    'exception' => $e,
                ]);
            }
        }

        ksort($executions);

        return array_values($executions);
    }
}
