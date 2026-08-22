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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentResolver;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\RelationHelper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;

class Hardlink
{
    use ServiceTrait;

    public function __construct()
    {
    }

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|null
     *
     * @throws Exception
     */
    public function resolveTarget($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $documentId = $value['id'];
        $document = Document::getById($documentId);

        if ($document instanceof Document\Hardlink) {
            if ($relation = $document->getSourceDocument()) {
                return RelationHelper::processRelation(
                    $relation,
                    $this->getGraphQlService(),
                    $args,
                    $context,
                    $resolveInfo
                );
            }
        }

        return null;
    }
}
