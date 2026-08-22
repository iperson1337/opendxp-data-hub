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

use OpenDxp\Bundle\AdminBundle\DataObject\GridColumnConfig\ConfigElementInterface;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

abstract class AbstractOperator implements OperatorInterface
{
    use ServiceTrait;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var ConfigElementInterface[]
     */
    protected $children;

    /**
     * @param array|null $context
     */
    public function __construct(
        array $config = [],
        protected $context = null
    ) {
        $this->label = $config['label'];
        $this->children = $config['children'];
    }

    /**
     * @return ConfigElementInterface[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function expandLocales()
    {
        return false;
    }

    /**
     * @return array|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     *
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}
