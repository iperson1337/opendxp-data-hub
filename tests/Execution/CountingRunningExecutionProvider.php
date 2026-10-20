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

final class CountingRunningExecutionProvider implements RunningExecutionProviderInterface
{
    public int $calls = 0;

    /**
     * @param list<RunningExecution> $executions
     */
    public function __construct(private readonly array $executions)
    {
    }

    public function getRunningExecutions(): iterable
    {
        ++$this->calls;

        return $this->executions;
    }
}
