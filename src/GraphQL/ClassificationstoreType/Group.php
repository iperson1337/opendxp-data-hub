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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\ClassificationstoreType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FeatureDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\Classificationstore;

class Group extends ObjectType
{
    use ServiceTrait;

    /** @var Feature */
    protected $featureType;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, Feature $featuresType, $config = ['name' => 'csGroup'], $context = [])
    {
        $this->setGraphQLService($graphQlService);
        $this->featureType = $featuresType;
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\AssetType();
        $resolver->setGraphQLService($this->getGraphQlService());

        $config['fields'] = [
            'id' => Type::int(),
            'name' => Type::string(),
            'description' => Type::string(),
            'features' => [
                'type' => Type::listOf($this->featureType),
                'resolve' => function ($value, $args, $context = [], ?ResolveInfo $resolveInfo = null) {
                    /** @var Classificationstore $csValue */
                    $csValue = $value['_csValue'];
                    $groupId = $value['id'];
                    $language = $value['_language'];
                    if (!$language) {
                        // Let's try to "inherit" the language from what's already been parsed from this query
                        $language = $this->getGraphQlService()->getLocaleService()->getLocale();
                        if (!$language) {
                            $language = 'default';
                        }
                    }

                    $keyRelations = new Classificationstore\KeyGroupRelation\Listing();
                    $keyRelations->setCondition('groupId = ' . $groupId);
                    $keyRelations = $keyRelations->load();

                    $result = [];

                    $service = $this->getGraphQlService();
                    $supportedFeatureTypeNames = $service->getSupportedCsFeatureQueryDataTypes();

                    foreach ($keyRelations as $keyRelation) {
                        $keyDataType = $keyRelation->getType();
                        if (in_array($keyDataType, $supportedFeatureTypeNames)) {
                            $keyId = $keyRelation->getKeyId();
                            //TODO maybe add args for this fallback stuff ?

                            $featureValue = $csValue->getLocalizedKeyValue($groupId, $keyId, $language);
                            $wrappedFeatureValue = new FeatureDescriptor();
                            $wrappedFeatureValue->setId($keyId);
                            $wrappedFeatureValue->setType($keyDataType);
                            $wrappedFeatureValue->setValue($featureValue);
                            $result[] = $wrappedFeatureValue;
                        }
                        //TODO decide whether we want to skip unsupported types (as we do now) or simply add null
                    }

                    return $result;
                },
            ],
        ];
    }
}
