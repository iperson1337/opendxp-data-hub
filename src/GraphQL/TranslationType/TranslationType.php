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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\TranslationType;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType\JsonType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class TranslationType extends ObjectType
{
    use ServiceTrait;

    protected string $fieldname;

    /**
     * @throws Exception
     */
    public function __construct(Service $graphQlService, array $config = ['name' => 'translation', 'fields' => []])
    {
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function build(array &$config)
    {
        $config['fields'] = [
            'key' => Type::string(),
            'creationDate' => Type::int(),
            'modificationDate' => Type::int(),
            'domain' => Type::string(),
            'type' => Type::string(),
            'translations' => [
                'type' => new JsonType(),
            ],
        ];
    }
}
