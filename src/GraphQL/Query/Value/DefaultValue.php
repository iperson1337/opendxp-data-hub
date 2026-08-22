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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Query\Value;

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

class DefaultValue extends AbstractValue
{
    /**
     * @param ElementInterface|null $element
     *
     * @return stdClass|null
     */
    public function getLabeledValue($element, ?ResolveInfo $resolveInfo = null)
    {
        if ($element instanceof Concrete) {
            $result = new stdClass();

            if ($this->dataType == 'system') {
                $getter = 'get' . ucfirst($this->attribute);
                $result->value = $element->$getter();

                return $result;
            }

            $class = $element->getClass();
            $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
            $fieldDefinition = $fieldHelper->getFieldDefinitionFromKey($class, $this->attribute);

            $valueParams = new ElementDescriptor($element);

            $resolveFn = $this->getGraphQlService()->buildDataObjectDataQueryResolver($this->attribute, $fieldDefinition, $class);
            $args = [];

            $value = $resolveFn($valueParams, $args, $this->context, $resolveInfo);
            if (!$value) {
                return null;
            }

            $result->value = $this->getGraphQlService()->getElementFromArrayObject($value);

            return $result;
        }

        return null;
    }
}
