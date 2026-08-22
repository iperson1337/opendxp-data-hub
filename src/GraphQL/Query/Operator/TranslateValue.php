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

class TranslateValue extends AbstractOperator
{
    private $prefix;

    /**
     * @param array|null $context
     */
    public function __construct(array $config = [], $context = null)
    {
        //TODO use translator factory from grid config
        parent::__construct($config, $context);

        $this->prefix = $config['prefix'];
    }

    /**
     * @param ElementInterface|null $element
     *
     * @return stdClass|null
     *
     * @throws Exception
     */
    public function getLabeledValue($element, ?ResolveInfo $resolveInfo = null)
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = null;

        $translator = $this->getGraphQlService()->getTranslator();
        $children = $this->getChildren();

        if ($children) {
            $valueResolver = $this->getGraphQlService()->buildValueResolverFromAttributes($children[0]);

            $childResult = $valueResolver->getLabeledValue($element, $resolveInfo);
            if ($childResult) {
                if (is_array($childResult->value)) {
                    $result->value = [];
                    foreach ($childResult->value as $childValue) {
                        $result->value[] = $translator->trans($this->prefix . $childValue, []);
                    }
                } else {
                    $result->value = $translator->trans($this->prefix . $childResult->value, []);
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
