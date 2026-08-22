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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

class UrlSlugType extends ObjectType
{
    use ServiceTrait;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, ?Data $fieldDefinition = null, $config = [], $context = [])
    {
        $this->fieldDefinition = $fieldDefinition;
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $config['fields'] = self::getFieldConfig($this->getGraphQlService());
    }

    /**
     * @return array[]
     */
    public static function getFieldConfig(Service $graphQlService)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\UrlSlug();
        $resolver->setGraphQLService($graphQlService);
        $fields = [
            'slug' => [
                'type' => Type::string(),
                'resolve' => $resolver->resolveSlug(...),
            ],
            'siteId' => [
                'type' => Type::int(),
                'resolve' => $resolver->resolveSiteId(...),
            ],
        ];

        return $fields;
    }
}
