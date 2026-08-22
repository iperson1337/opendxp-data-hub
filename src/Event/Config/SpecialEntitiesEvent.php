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

namespace OpenDxp\Bundle\DataHubBundle\Event\Config;

use OpenDxp\Bundle\DataHubBundle\Model\SpecialEntitySetting;
use Symfony\Contracts\EventDispatcher\Event;

class SpecialEntitiesEvent extends Event
{
    protected $specialSettings;

    protected $config;

    public function __construct(array $specialSettings, array $config)
    {
        $this->specialSettings = $specialSettings;
        $this->config = $config;
    }

    /**
     * @return SpecialEntitySetting[]
     */
    public function getSpecialSettings(): array
    {
        return $this->specialSettings;
    }

    public function addSpecialSetting(SpecialEntitySetting $setting)
    {
        $this->specialSettings[] = $setting;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
