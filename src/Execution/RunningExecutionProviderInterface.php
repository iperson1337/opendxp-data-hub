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

/**
 * Источник прогресса для индикатора «Идёт выполнение» под деревом конфигов Data Hub.
 *
 * Подключение необязательно: бандл, которому индикатор не нужен, интерфейс просто не реализует.
 * Сервис с включённым autoconfigure получает тег {@see TAG} сам (OpenDxpDataHubExtension),
 * без autoconfigure тег нужно поставить вручную.
 *
 * Вызывается не чаще раза в `opendxp_data_hub.running_executions.cache_ttl` секунд на под, но
 * всё равно должен быть дешёвым: один запрос на все конфиги адаптера, без чтения самих данных
 * и без загрузки DataObject.
 */
interface RunningExecutionProviderInterface
{
    public const TAG = 'opendxp.datahub.running_execution_provider';

    /**
     * Только выполняющиеся сейчас конфиги. Права пользователя проверять не нужно —
     * реестр отфильтрует по `Configuration::isAllowed('read')`.
     *
     * @return iterable<RunningExecution>
     */
    public function getRunningExecutions(): iterable;
}
