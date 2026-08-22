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

class Substring extends AbstractOperator
{
    private $start;

    private $length;

    private $ellipses;

    /**
     * @param array|null $context
     */
    public function __construct(array $config = [], $context = null)
    {
        parent::__construct($config, $context);

        $this->start = $config['start'];
        $this->length = $config['length'];
        $this->ellipses = $config['ellipses'];
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

        $childValue = $childResult->value;
        $showEllipses = false;
        if ($childValue && $this->getEllipses()) {
            $start = $this->getStart() ?: 0;
            $length = $this->getLength() ?: 0;
            if (strlen((string) $childValue) > $start + $length) {
                $showEllipses = true;
            }
        }

        $childValue = substr((string) $childValue, $this->getStart(), $this->getLength());
        if ($showEllipses) {
            $childValue .= '...';
        }
        $result->value = $childValue;

        return $result;
    }

    /**
     * @return int|null
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int|null $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return int|null
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int|null $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return mixed
     */
    public function getEllipses()
    {
        return $this->ellipses;
    }

    /**
     * @param mixed $ellipses
     */
    public function setEllipses($ellipses)
    {
        $this->ellipses = $ellipses;
    }
}
