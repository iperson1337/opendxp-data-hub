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

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Model\Element\ElementInterface;
use stdClass;

class Text extends AbstractOperator
{
    /**
     * @param array|null $context
     */
    public function __construct(array $config, $context = null)
    {
        parent::__construct($config, $context);
    }

    /**
     * @param ElementInterface|null $element
     *
     * @return stdClass
     */
    public function getLabeledValue($element, ?ResolveInfo $resolveInfo = null)
    {
        $result = new stdClass();
        $result->label = $this->label;
        $result->value = $result->label;

        return $result;
    }
}
