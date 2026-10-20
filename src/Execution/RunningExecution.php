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
 * Выполнение конфига Data Hub, идущее прямо сейчас: сколько единиц работы из скольких сделано.
 * Что считать единицей — решает адаптер (строка очереди импорта, группа файлов и т. п.).
 */
final readonly class RunningExecution
{
    public function __construct(
        public string $configName,
        public int $processed,
        public int $total,
        // Необязательная подробность для подсказки: «перемещено 12 файлов», «этап: разбор файла».
        public ?string $message = null,
    ) {
    }

    /**
     * Доля выполненного от 0 до 1. Пока общее число неизвестно (`total = 0`), прогресс — 0.
     */
    public function getProgress(): float
    {
        if ($this->total <= 0) {
            return 0.0;
        }

        return round(min(1, max(0, $this->processed / $this->total)), 2);
    }

    /**
     * @return array{name: string, processed: int, total: int, progress: float, message: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->configName,
            'processed' => $this->processed,
            'total' => $this->total,
            'progress' => $this->getProgress(),
            'message' => $this->message,
        ];
    }
}
