<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
