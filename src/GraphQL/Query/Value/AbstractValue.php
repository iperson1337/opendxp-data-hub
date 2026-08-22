<?php

declare(strict_types=1);

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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

abstract class AbstractValue implements ValueInterface
{
    use ServiceTrait;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $label;

    /** @var string */
    protected $dataType;

    /**
     * @param array $config
     * @param array|null $context
     */
    public function __construct(
        $config,
        protected $context = null
    ) {
        $this->attribute = $config['attribute'];
        $this->label = $config['label'];
        $this->dataType = $config['dataType'];
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
