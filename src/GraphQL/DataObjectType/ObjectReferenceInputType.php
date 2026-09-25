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

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

final class ObjectReferenceInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'ObjectReferenceInput',
            'description' => 'Reference to a DataObject by uuid or by type + id / fullpath.',
            'fields' => [
                'uuid' => [
                    'type' => Type::string(),
                    'description' => 'UUID of the object (if target class has uuid field).',
                ],
                'type' => [
                    'type' => Type::string(),
                    'description' => 'Element type: object, asset, or document.',
                ],
                'id' => [
                    'type' => Type::int(),
                    'description' => 'Element ID.',
                ],
                'fullpath' => [
                    'type' => Type::string(),
                    'description' => 'Element full path.',
                ],
            ],
        ]);
    }
}
