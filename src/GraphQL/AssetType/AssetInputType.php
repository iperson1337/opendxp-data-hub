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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\AssetType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementTag;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class AssetInputType extends InputObjectType
{
    use ServiceTrait;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'AssetInput'], $context = [])
    {
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $config['fields'] = [
            'filename' => Type::string(),
            'parentId' => Type::int(),
            'data' => [
                'type' => Type::string(),
            ],
            'tags' => ElementTag::getElementTagInputTypeDefinition(),
            'metadata' => [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'MetadataItem',
                    'fields' => [
                        'name' => Type::nonNull(Type::string()),
                        'type' => Type::nonNull(Type::string()),
                        'data' => Type::string(),
                        'language' => Type::string(),
                    ],
                ])),
            ],
        ];
    }
}
