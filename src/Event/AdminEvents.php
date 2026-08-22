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

final class AdminEvents
{
    /**
     * Allows you to modify/append the the configuration list.
     *
     * Arguments:
     *  - list | the configuration list
     *
     * @Event("OpenDxp\Event\Model\GenericEvent")
     */
    const string CONFIGURATION_LIST = 'opendxp.datahub.admin.configuration.list';
}
