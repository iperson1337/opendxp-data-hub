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

namespace OpenDxp\Bundle\DataHubBundle\Event;

final class ConfigurationEvents
{
    /**
     * Fired after a configuration was deleted
     *
     * Arguments:
     *  - configuration | the original configuration instance
     *
     * @Event("OpenDxp\Event\Model\GenericEvent")
     */
    const string CONFIGURATION_POST_DELETE = 'opendxp.datahub.configuration.postDelete';

    /**
     * Fired before a configuration gets saved
     *
     * Arguments:
     *  - configuration | the original configuration instance
     *
     * @Event("OpenDxp\Event\Model\GenericEvent")
     */
    const string CONFIGURATION_PRE_SAVE = 'opendxp.datahub.configuration.preSave';

    /**
     * Fired after a configuration was saved
     *
     * Arguments:
     *  - configuration | the original configuration instance
     *
     * @Event("OpenDxp\Event\Model\GenericEvent")
     */
    const string CONFIGURATION_POST_SAVE = 'opendxp.datahub.configuration.postSave';
}
