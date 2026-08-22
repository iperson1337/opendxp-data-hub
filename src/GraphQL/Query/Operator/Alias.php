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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Operator;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

class Alias extends AbstractOperator
{
    /**
     * @param ElementInterface|null $element
     *
     * @return stdClass
     *
     * @throws Exception
     */
    public function getLabeledValue($element, ?ResolveInfo $resolveInfo = null)
    {
        $result = new stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        }

        $c = $children[0];

        $valueResolver = $this->getGraphQlService()->buildValueResolverFromAttributes($c);

        $valueFromChild = $valueResolver->getLabeledValue($element, $resolveInfo);
        if ($valueFromChild) {
            $result->value = $valueFromChild->value;
        }

        return $result;
    }
}
