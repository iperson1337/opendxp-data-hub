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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class BaseOperator extends Base
{
    /**
     * @param array $nodeDef
     */
    public function __construct($nodeDef)
    {
        parent::__construct($nodeDef);
    }

    /**
     * @param Concrete|AbstractData $object
     * @param array $newValue
     * @param array $args
     * @param array $context
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $class = $object->getClass();
        $parentProcessor = $this->getParentProcessor($this->nodeDef, $class);
        if ($parentProcessor) {
            // nothing to do with the value1
            call_user_func_array($parentProcessor, [$object, $newValue, $args, $context, $info]);
        }
    }
}
