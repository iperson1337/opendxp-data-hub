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

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class ElementTag extends ObjectType
{
    use ServiceTrait;

    protected static $tagTypeCache = [];

    /**
     * Type definition for ElementTag
     *
     * @return array
     */
    public static function getElementTagInputTypeDefinition()
    {
        if (!isset(self::$tagTypeCache['ElementTag'])) {
            self::$tagTypeCache['ElementTag'] = [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'ElementTag',
                    'fields' => [
                        'id' => Type::id(),
                        'path' => Type::string(),
                    ],
                ])),
            ];
        }

        return self::$tagTypeCache['ElementTag'];
    }

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = [])
    {
        $this->graphQlService = $graphQlService;
        $config['name'] = 'element_tag';
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $config['fields'] = [
            'id' => Type::id(),
            'name' => Type::string(),
            'path' => Type::string(),
        ];
    }
}
