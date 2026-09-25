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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL;

use Closure;
use Doctrine\DBAL\Connection;

/**
 * Собирает SQL-условие прав воркспейса для листингов GraphQL.
 *
 * Исторически условие было двумя коррелированными подзапросами к
 * `plugin_datahub_workspaces_*`, каждый со своим `ORDER BY LENGTH(cpath) DESC LIMIT 1`.
 * MySQL выполнял их для КАЖДОЙ строки листинга, поэтому стоимость запроса не зависела
 * ни от размера страницы, ни от смещения: на боевом `Product` (≈394k объектов)
 * один `SELECT COUNT(*)` для поля `totalCount` занимал ~45 секунд.
 *
 * Строк воркспейса всегда единицы, поэтому их можно вычитать один раз и развернуть
 * в константное выражение `CASE`. Семантика сохранена дословно:
 *
 *  - выигрывает воркспейс с самым длинным `cpath` (`LENGTH()` в MySQL считает БАЙТЫ,
 *    поэтому сортировка идёт по `strlen()`, а не по `mb_strlen()`);
 *  - совпадение ищется в обе стороны: воркспейс — предок объекта и объект — предок
 *    воркспейса;
 *  - сравнение остаётся на `LOCATE(...) = 1`, а не на `LIKE`, чтобы не экранировать
 *    `%`/`_` в путях.
 *
 * Отличие от исходных подзапросов: совпадение проверяется по границе сегмента пути
 * (`/foo` покрывает `/foo` и `/foo/…`, но не `/foobar`), как и PHP-проверка
 * `WorkspaceHelper::isAllowed`, которая работает по id элемента.
 */
final class WorkspaceConditionBuilder
{
    /** @var Closure(string): string */
    private Closure $quoteString;

    /** @var Closure(string): string */
    private Closure $quoteIdentifier;

    /**
     * @param callable(string): string $quoteString
     * @param callable(string): string $quoteIdentifier
     */
    public function __construct(callable $quoteString, callable $quoteIdentifier)
    {
        $this->quoteString = Closure::fromCallable($quoteString);
        $this->quoteIdentifier = Closure::fromCallable($quoteIdentifier);
    }

    public static function forConnection(Connection $db): self
    {
        return new self(
            static fn (string $value): string => $db->quote($value),
            static fn (string $value): string => $db->quoteIdentifier($value),
        );
    }

    /**
     * @param string $tableName  таблица листинга (`assets`, `object_localized_Product_ru`, …)
     * @param string $nameColumn колонка с именем элемента (`key` у объектов, `filename` у ассетов)
     * @param array<int, array<string, mixed>> $workspaces строки `plugin_datahub_workspaces_*`
     *                                                    с ключами `cpath` и `read`
     *
     * @return string|null SQL-условие либо `null`, если условие тождественно истинно
     *                     и добавлять его в запрос не нужно
     */
    public function build(string $tableName, string $nameColumn, array $workspaces): ?string
    {
        $rows = [];
        foreach ($workspaces as $workspace) {
            $cpath = (string) ($workspace['cpath'] ?? '');
            // Хвостовой слэш из YAML-конфига нормализуем: `/foo/` и `/foo` — один воркспейс.
            if ($cpath !== '/') {
                $cpath = rtrim($cpath, '/');
            }
            if ($cpath === '') {
                continue;
            }

            $rows[] = [
                'cpath' => $cpath,
                'read' => (int) ($workspace['read'] ?? 0) === 1 ? 1 : 0,
            ];
        }

        if ($rows === []) {
            // Ни одного воркспейса — читать нечего. Исходный подзапрос в этом случае
            // возвращал NULL, а `NULL = 1` отсекало все строки. Сохраняем «запрещено всё»:
            // подменить это на «условия нет» значило бы открыть весь класс.
            return '(0 = 1)';
        }

        usort($rows, static fn (array $a, array $b): int => strlen($b['cpath']) <=> strlen($a['cpath']));

        if ($this->grantsEverything($rows)) {
            return null;
        }

        $fullpath = sprintf(
            'CONCAT(%s.%s, %s.%s)',
            ($this->quoteIdentifier)($tableName),
            ($this->quoteIdentifier)('path'),
            ($this->quoteIdentifier)($tableName),
            ($this->quoteIdentifier)($nameColumn),
        );

        $quoteString = $this->quoteString;

        // Воркспейс — предок элемента (или сам элемент). Сравнение по границе сегмента:
        // `/foo` не должен матчить `/foobar/…` — голый LOCATE(cpath, fullpath) = 1 это допускал.
        $workspaceAboveElement = $this->buildCase(
            $rows,
            static function (string $cpath) use ($fullpath, $quoteString): string {
                if ($cpath === '/') {
                    return sprintf('LOCATE(%s, %s) = 1', $quoteString('/'), $fullpath);
                }

                return sprintf(
                    '(%1$s = %2$s OR LOCATE(%3$s, %1$s) = 1)',
                    $fullpath,
                    $quoteString($cpath),
                    $quoteString($cpath . '/'),
                );
            },
        );

        // Элемент — предок воркспейса: папки на пути к разрешённому воркспейсу видны.
        $elementAboveWorkspace = $this->buildCase(
            $rows,
            static function (string $cpath) use ($fullpath, $quoteString): string {
                if ($cpath === '/') {
                    return sprintf('%s = %s', $fullpath, $quoteString('/'));
                }

                // Корень (`path` = '/', `key` = '') — предок любого воркспейса.
                return sprintf(
                    '(%1$s = %3$s OR %1$s = %2$s OR LOCATE(CONCAT(%1$s, %3$s), %4$s) = 1)',
                    $fullpath,
                    $quoteString($cpath),
                    $quoteString('/'),
                    $quoteString($cpath . '/'),
                );
            },
        );

        return sprintf('(%s = 1 OR %s = 1)', $workspaceAboveElement, $elementAboveWorkspace);
    }

    /**
     * Права на корень при отсутствии запрещающих строк делают условие тождественно истинным:
     * полный путь любого элемента начинается с `/`, а все кандидаты дают `read = 1`.
     *
     * @param array<int, array{cpath: string, read: int}> $rows
     */
    private function grantsEverything(array $rows): bool
    {
        $hasRoot = false;
        foreach ($rows as $row) {
            if ($row['read'] !== 1) {
                return false;
            }
            if ($row['cpath'] === '/') {
                $hasRoot = true;
            }
        }

        return $hasRoot;
    }

    /**
     * @param array<int, array{cpath: string, read: int}> $rows отсортированы по убыванию длины `cpath`
     * @param callable(string): string $predicate получает НЕквотированный cpath
     */
    private function buildCase(array $rows, callable $predicate): string
    {
        $whens = [];
        foreach ($rows as $row) {
            $whens[] = sprintf('WHEN %s THEN %d', $predicate($row['cpath']), $row['read']);
        }

        return '(CASE ' . implode(' ', $whens) . ' ELSE 0 END)';
    }
}
