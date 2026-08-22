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

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Element\ElementInterface;

class RelationHelper
{
    /**
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor
     */
    public static function processRelation(ElementInterface $relation, Service $graphQlService, $args, $context, ResolveInfo $resolveInfo)
    {
        $data = new ElementDescriptor($relation);
        $graphQlService->extractData($data, $relation, $args, $context, $resolveInfo);

        return $data;
    }
}
