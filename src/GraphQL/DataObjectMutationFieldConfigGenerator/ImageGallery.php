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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectMutationFieldConfigGenerator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Override;

class ImageGallery extends Base
{
    /** {@inheritdoc } */
    #[Override]
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container = null, $params = [])
    {
        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\ImageGallery($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $imageInput = new InputObjectType([
            'name' => 'GalleryImageInput',
            'fields' => [
                'id' => Type::int(),
            ],
        ]);

        $inputType = new InputObjectType([
            'name' => 'ImageGalleryInput',
            'fields' => [
                'replace' => [
                    'type' => Type::boolean(),
                    'description' => 'if true then the entire gallery list will be overwritten',
                ],
                'images' => [
                    'type' => Type::listOf($imageInput),
                ],
            ],
        ]);

        return [
            'arg' => $inputType,
            'processor' => $processor->process(...),
        ];
    }
}
