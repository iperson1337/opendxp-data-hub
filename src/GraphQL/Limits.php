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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL;

use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;

/**
 * Ограничения на аргументы листингов и запросов, задаваемые в `opendxp_data_hub.graphql`.
 *
 * Значения читаются из параметра контейнера; в тестах их можно переопределить через
 * {@see self::configure()}.
 */
final class Limits
{
    public const int DEFAULT_MAX_FIRST = 1000;

    public const int DEFAULT_QUERY_DEPTH = 15;

    public const int DEFAULT_QUERY_COMPLEXITY = 1000;

    /** @var array<string, int>|null */
    private static ?array $overrides = null;

    /**
     * @param array<string, int>|null $config ключи `max_first`, `query_depth_limit`, `query_complexity_limit`;
     *                                        `null` возвращает чтение из контейнера
     */
    public static function configure(?array $config): void
    {
        self::$overrides = $config;
    }

    public static function maxFirst(): int
    {
        return self::read('max_first', self::DEFAULT_MAX_FIRST);
    }

    public static function queryDepth(): int
    {
        return self::read('query_depth_limit', self::DEFAULT_QUERY_DEPTH);
    }

    public static function queryComplexity(): int
    {
        return self::read('query_complexity_limit', self::DEFAULT_QUERY_COMPLEXITY);
    }

    /**
     * Приводит аргумент `first` к допустимому лимиту: без аргумента — максимум,
     * больше максимума — ошибка потребителю, а не тихое усечение.
     */
    public static function first(mixed $first): int
    {
        $max = self::maxFirst();
        if ($first === null) {
            return $max;
        }

        $first = (int) $first;
        if ($first < 0) {
            throw new ClientSafeException('first must not be negative');
        }
        if ($max > 0 && $first > $max) {
            throw new ClientSafeException(sprintf('first must not exceed %d', $max));
        }

        return $first;
    }

    /**
     * `sortBy` приходит от потребителя списком строк; допускаем только имена колонок
     * вида `name` или `table.name`, чтобы ничего лишнего не попало в ORDER BY.
     *
     * @param string|array<int, string> $sortBy
     *
     * @return string|array<int, string>
     */
    public static function assertSortKeys(string|array $sortBy): string|array
    {
        foreach ((array) $sortBy as $key) {
            if (!is_string($key) || !preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $key)) {
                throw new ClientSafeException('invalid sortBy value ' . json_encode($key));
            }
        }

        return $sortBy;
    }

    private static function read(string $key, int $default): int
    {
        if (self::$overrides !== null) {
            return (int) (self::$overrides[$key] ?? $default);
        }

        $container = \OpenDxp::getContainer();
        if (!$container || !$container->hasParameter('opendxp_data_hub')) {
            return $default;
        }

        $config = $container->getParameter('opendxp_data_hub');

        return (int) ($config['graphql'][$key] ?? $default);
    }
}
