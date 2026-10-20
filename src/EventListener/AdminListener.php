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

namespace OpenDxp\Bundle\DataHubBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;

class AdminListener
{
    public function __construct(private array $config)
    {
    }

    /**
     * Handles INDEX_ACTION_SETTINGS event and adds custom admin UI settings
     */
    public function addIndexSettings(IndexActionSettingsEvent $event)
    {
        $event->addSetting('data-hub-writeable', (new \OpenDxp\Bundle\DataHubBundle\Configuration(null, null))->isWriteable());
        $this->addEventSetting('allow_introspection', $event);
        $this->addEventSetting('allow_sqlObjectCondition', $event);

        $runningExecutions = $this->config['running_executions'] ?? [];
        $event->addSetting('data-hub-running-executions', [
            'enabled' => $runningExecutions['enabled'] ?? true,
            'activePollInterval' => ($runningExecutions['active_poll_interval'] ?? 10) * 1000,
            'idlePollInterval' => ($runningExecutions['idle_poll_interval'] ?? 30) * 1000,
        ]);
    }

    private function addEventSetting(
        string $key,
        IndexActionSettingsEvent $event
    ): void {
        $value = $this->config['graphql'][$key] ?? true;
        $event->addSetting($key, $value);
    }
}
