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

namespace OpenDxp\Bundle\DataHubBundle\Event\GraphQL;

final class QueryEvents
{
    /**
     * @Event("OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\QueryTypeEvent")
     */
    const string PRE_BUILD = 'opendxp.datahub.graphql.query.preBuild';

    /**
     * @Event("OpenDxp\Bundle\DataHubBundle\Event\GraphQL\Model\QueryTypeEvent")
     */
    const string POST_BUILD = 'opendxp.datahub.graphql.query.postBuild';
}
