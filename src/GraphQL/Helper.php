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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Layout;
use OpenDxp\Model\DataObject\Listing;
use stdClass;

/**
 * @internal
 */
class Helper
{
    /**
     * Подменяемое соединение для юнит-тестов: `Db::get()` требует поднятого контейнера.
     * Нужны только методы `quote(string): string` и `quoteIdentifier(string): string`.
     */
    private static ?object $connection = null;

    public static function useConnection(?object $connection): void
    {
        self::$connection = $connection;
    }

    private static function getDb(): object
    {
        return self::$connection ?? Db::get();
    }

    /**
     * @param Listing\Concrete $list
     * @param stdClass|array $filter
     * @param array $columns
     * @param array $mappingTable
     */
    public static function addJoins(&$list, $filter, $columns, &$mappingTable = [])
    {
        $filterKeys = self::collectFilterKeys($filter);
        if ($filterKeys === []) {
            return;
        }

        foreach ($columns as $column) {
            $attributes = $column['attributes'] ?? [];
            $name = $attributes['attribute'] ?? null;
            if (!is_string($name) || !str_contains($name, '~')) {
                continue;
            }

            [$brickName, $brickKey] = explode('~', $name, 2);
            // Джойним brick-таблицу только если фильтр реально обращается к её полю:
            // раньше при любом фильтре подключались ВСЕ brick'и конфигурации.
            if (!isset($filterKeys[$brickKey]) && !isset($filterKeys[$name])) {
                continue;
            }

            $list->addObjectbrick($brickName);
            $mappingTable[$brickKey] = 1;
        }
    }

    /**
     * Рекурсивно собирает имена полей, к которым обращается фильтр (без операторов `$…`).
     *
     * @param mixed $filter
     *
     * @return array<string, true>
     */
    private static function collectFilterKeys($filter, array &$keys = []): array
    {
        if ($filter instanceof stdClass) {
            $filter = get_object_vars($filter);
        }
        if (!is_array($filter)) {
            return $keys;
        }

        foreach ($filter as $key => $value) {
            if (is_string($key) && !str_starts_with($key, '$')) {
                $keys[$key] = true;
            }
            self::collectFilterKeys($value, $keys);
        }

        return $keys;
    }

    /**
     * @param string $defaultTable
     * @param string|array|stdClass $q
     * @param string|null $op
     * @param string|null $subject
     * @param array $fieldMappingTable
     *
     * @return string
     */
    public static function buildSqlCondition($defaultTable, $q, $op = null, $subject = null, $fieldMappingTable = [])
    {
        // Examples:
        //
        //q={"o_modificationDate" : {"$gt" : "1000"}}
        //where ((`o_modificationDate` > '1000') )
        //
        //
        //
        //
        //q=[{"o_modificationDate" : {"$gt" : "1000"}}, {"o_modificationDate" : {"$lt" : "9999"}}]
        //where ( ((`o_modificationDate` > '1000') )  AND  ((`o_modificationDate` < '9999') )  )
        //
        //
        //
        //
        //q={"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3", "o_key": {"$like" :"%lorem-ipsum%"}}]}
        //where ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR  ((`o_key` LIKE '%lorem-ipsum%') )  )  )
        //
        // q={"$and" : [{"o_published": "0"}, {"o_modificationDate" : {"$gt" : "1000"}, "$or": [{"o_id": "3", "o_key": {"$like" :"%lorem-ipsum%"}}]}]}
        //
        // where ( ((`o_published` = '0') )  AND  ((`o_modificationDate` > '1000') AND  ((`o_id` = '3') OR (`o_key` LIKE '%lorem-ipsum%') )  )  )

        if (!$op) {
            $op = 'AND';
        }
        $mappingTable = [
            '$gt' => '>',
            '$gte' => '>=',
            '$lt' => '<',
            '$lte' => '<=',
            '$like' => 'LIKE',
            '$notlike' => 'NOT LIKE',
            '$notnull' => 'IS NOT NULL',
            '$not' => 'NOT',
        ];
        $ops = array_keys($mappingTable);

        $db = self::getDb();

        $parts = [];
        if (!is_array($q) && !$q instanceof stdClass) {
            // Раньше строка возвращалась в SQL как есть — прямая SQL-инъекция через `filter`.
            throw new ClientSafeException('invalid filter: expected an object or a list of objects, got ' . get_debug_type($q));
        }

        foreach ($q as $key => $value) {
            if (array_search(strtolower((string) $key), ['$and', '$or']) !== false) {
                $childOp = strtolower((string) $key) == '$and' ? 'AND' : 'OR';

                if (is_array($value)) {
                    $childParts = [];
                    foreach ($value as $arrItem) {
                        $childParts[] = self::buildSqlCondition(
                            $defaultTable,
                            $arrItem,
                            $childOp,
                            $subject,
                            $fieldMappingTable
                        );
                    }
                    $parts[] = implode(' ' . $childOp . ' ', $childParts);
                } elseif ($value instanceof stdClass) {
                    $parts[] = self::buildSqlCondition($defaultTable, $value, $childOp, $subject, $fieldMappingTable);
                } else {
                    throw new ClientSafeException('invalid filter: ' . $key . ' expects a list of conditions');
                }
            } else {
                if (is_array($value)) {
                    $scalars = array_filter($value, static fn ($item) => $item === null || is_scalar($item));
                    if (count($scalars) === count($value)) {
                        // Список скаляров — трактуем как `$in`, а не как сырой SQL.
                        $parts[] = self::buildInCondition($defaultTable, (string) $key, $value, $fieldMappingTable);
                    } else {
                        foreach ($value as $subValue) {
                            if (!$subValue instanceof stdClass) {
                                throw new ClientSafeException('invalid filter: mixed list for ' . $key);
                            }
                            $parts[] = self::buildSqlCondition($defaultTable, $subValue, null, null, $fieldMappingTable);
                        }
                    }
                } elseif ($value instanceof stdClass) {
                    $objectVars = get_object_vars($value);
                    foreach ($objectVars as $objectVar => $objectValue) {
                        if ((strtolower((string) $objectVar) === '$in' || strtolower((string) $objectVar) === 'in') && is_array($objectValue)) {
                            $parts[] = self::buildInCondition($defaultTable, (string) $key, $objectValue, $fieldMappingTable);
                        } elseif (array_search(strtolower((string) $objectVar), $ops) !== false) {
                            $innerOp = $mappingTable[strtolower((string) $objectVar)];
                            if ($innerOp == 'NOT') {
                                $valuePart = ' IS NULL';
                                if (!is_null($objectValue)) {
                                    $valuePart = ' =' . self::quoteScalar($objectValue);
                                }

                                if (isset($fieldMappingTable[$key])) {
                                    $parts[] = '( NOT ' . $db->quoteIdentifier(self::assertColumnName($key)) . $valuePart . ')';
                                } else {
                                    $parts[] = '( NOT ' . self::quoteAbsoluteColumnName(
                                        $defaultTable,
                                        $key
                                    ) . $valuePart . ')';
                                }
                            } else {
                                $parts[] = '(' . self::quoteAbsoluteColumnName(
                                    $defaultTable,
                                    $key
                                ) . ' ' . $innerOp . ' ' . self::quoteScalar($objectValue) . ')';
                            }
                        } else {
                            if ($objectValue instanceof stdClass) {
                                $parts[] = self::buildSqlCondition($defaultTable, $objectValue, null, $objectVar);
                            } else {
                                if (is_null($objectValue)) {
                                    $parts[] = '(' . self::quoteAbsoluteColumnName(
                                        $defaultTable,
                                        $objectVar
                                    ) . ' IS NULL)';
                                } else {
                                    $parts[] = '(' . self::quoteAbsoluteColumnName(
                                        $defaultTable,
                                        $objectVar
                                    ) . ' = ' . self::quoteScalar($objectValue) . ')';
                                }
                            }
                        }
                    }
                    $combinedParts = implode(' ' . $op . ' ', $parts);
                    $parts = [$combinedParts];
                } else {
                    if (array_search(strtolower((string) $key), $ops) !== false) {
                        $innerOp = $mappingTable[strtolower((string) $key)];
                        if ($innerOp == 'NOT') {
                            $parts[] = '(NOT' . self::quoteAbsoluteColumnName(
                                $defaultTable,
                                $subject
                            ) . ' = ' . self::quoteScalar($value) . ')';
                        } else {
                            $parts[] = '(' . self::quoteAbsoluteColumnName(
                                $defaultTable,
                                $subject
                            ) . ' ' . $innerOp . ' ' . self::quoteScalar($value) . ')';
                        }
                    } else {
                        if (isset($fieldMappingTable[$key])) {
                            if (is_null($value)) {
                                $parts[] = '(' . $db->quoteIdentifier(self::assertColumnName($key)) . ' IS NULL)';
                            } else {
                                $parts[] = '(' . $db->quoteIdentifier(self::assertColumnName($key)) . ' = ' . self::quoteScalar($value) . ')';
                            }
                        } else {
                            if (is_null($value)) {
                                $parts[] = '(' . self::quoteAbsoluteColumnName($defaultTable, $key) . ' IS NULL)';
                            } else {
                                $parts[] = '(' . self::quoteAbsoluteColumnName(
                                    $defaultTable,
                                    $key
                                ) . ' = ' . self::quoteScalar($value) . ')';
                            }
                        }
                    }
                }
            }
        }

        $subCondition = ' (' . implode(' ' . $op . ' ', $parts) . ' ) ';

        return $subCondition;
    }

    /**
     * @param string $defaultTable
     * @param string $columnName
     *
     * @return string
     */
    protected static function quoteAbsoluteColumnName($defaultTable, $columnName)
    {
        $columnName = self::assertColumnName($columnName);
        $db = self::getDb();
        $absoluteColumnName = (str_contains($columnName, '.')) ? $columnName : $defaultTable . '.' . $columnName;

        return $db->quoteIdentifier($absoluteColumnName);
    }

    /**
     * Имя колонки фильтра: только буквы, цифры, `_`, опционально `table.column`.
     * Квотирование идентификатора защищает от выхода из обратных кавычек, но не от
     * обращения к произвольным колонкам/таблицам с экзотическими именами.
     */
    private static function assertColumnName(mixed $columnName): string
    {
        if (!is_string($columnName) || !preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $columnName)) {
            throw new ClientSafeException('invalid filter: illegal column name ' . json_encode($columnName));
        }

        return $columnName;
    }

    private static function quoteScalar(mixed $value): string
    {
        if ($value === null || is_scalar($value)) {
            return self::getDb()->quote(is_bool($value) ? (int) $value : (string) $value);
        }

        throw new ClientSafeException('invalid filter: scalar value expected, got ' . get_debug_type($value));
    }

    /**
     * @param array<int, mixed> $values
     */
    private static function buildInCondition(string $defaultTable, string $key, array $values, array $fieldMappingTable): string
    {
        if ($values === []) {
            return '(1=0)';
        }

        $inList = implode(', ', array_map(self::quoteScalar(...), array_values($values)));
        $column = isset($fieldMappingTable[$key])
            ? self::getDb()->quoteIdentifier(self::assertColumnName($key))
            : self::quoteAbsoluteColumnName($defaultTable, $key);

        return '(' . $column . ' IN (' . $inList . '))';
    }

    /**
     * @param Layout|Data $def
     */
    public static function extractDataDefinitions($def, &$fieldDefinitions = [])
    {
        if ($def instanceof Layout || $def instanceof Data\Block || $def instanceof Data\Localizedfields) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    self::extractDataDefinitions($child, $fieldDefinitions);
                }
            }
        } elseif ($def instanceof Data) {
            $fieldDefinitions[$def->getName()] = $def;
        }
    }
}
