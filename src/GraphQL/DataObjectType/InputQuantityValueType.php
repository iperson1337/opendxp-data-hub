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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

class InputQuantityValueType extends QuantityValueType
{
    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, ?Data $fieldDefinition = null, $config = [], $context = [])
    {
        $config['fields'] = [
            'value' => [
                'type' => Type::string(),
            ],
        ];
        parent::__construct($graphQlService, $fieldDefinition, $config, $context);
    }
}
