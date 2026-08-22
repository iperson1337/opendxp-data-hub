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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\Event\GraphQL;

final class OutputCacheEvents
{
    /**
     * Fired to determine if a response should be cached.
     *
     * @Event("OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\CachePreLoadEvent")
     */
    const string PRE_LOAD = 'opendxp.datahub.graphql.cache.preLoad';

    /**
     * Fired before the response is written to cache. Can be used to set or purge
     * data on the cached response.
     *
     * @Event("OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\CachePreSaveEvent")
     */
    const string PRE_SAVE = 'opendxp.datahub.graphql.cache.preSave';
}
