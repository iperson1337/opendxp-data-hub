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

class Trimmer extends AbstractOperator
{
    const LEFT = 1;

    const RIGHT = 2;

    const BOTH = 3;

    private $trim;

    /**
     * @param array|null $context
     */
    public function __construct(array $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->trim = $config['trim'];
    }

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

        $childResult = $valueResolver->getLabeledValue($element, $resolveInfo);
        if (!$childResult) {
            return $result;
        }

        if ($childValue = $childResult->value) {
            /** @var string $childValue */
            switch ($this->trim) {
                case self::LEFT:
                    $childValue = ltrim($childValue);

                    break;
                case self::RIGHT:
                    $childValue = rtrim($childValue);

                    break;
                case self::BOTH:
                    $childValue = trim($childValue);

                    break;
            }
        }
        $result->value = $childValue;

        return $result;
    }
}
