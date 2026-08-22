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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL;

use ArrayObject;

abstract class BaseDescriptor extends ArrayObject
{
    /**
     * ElementDescriptor constructor - an ElementDescriptor describes something that implements
     * the OpenDxp\Model\Element\ElementInterface
     */
    public function __construct()
    {
        parent::__construct([], self::STD_PROP_LIST | self::ARRAY_AS_PROPS);
    }
}
