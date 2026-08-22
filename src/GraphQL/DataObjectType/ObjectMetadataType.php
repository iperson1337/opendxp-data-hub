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
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\ObjectMetadata;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use OpenDxp\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;

class ObjectMetadataType extends ObjectType
{
    use ServiceTrait;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param ClassDefinition|null $class
     * @param array $config
     */
    public function __construct(
        Service $graphQlService,
        ?Data $fieldDefinition = null,
        protected $class = null,
        $config = []
    ) {
        $this->setGraphQLService($graphQlService);
        $this->fieldDefinition = $fieldDefinition;
        if ($this->class instanceof ObjectbrickDefinition) {
            $config['name'] = 'objectbrick_' . $this->class->getKey() . '_' . $fieldDefinition->getName();
        } elseif ($this->class instanceof FieldcollectionDefinition) {
            $config['name'] = 'fieldcollection_' . $this->class->getKey() . '_' . $fieldDefinition->getName();
        } else {
            $config['name'] = 'object_' . $this->class->getName() . '_' . $fieldDefinition->getName();
        }
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();
        /** @var Data\AdvancedManyToManyObjectRelation $fieldDefinition */
        $fieldDefinition = $this->fieldDefinition;
        $class = $this->class;

        $className = $fieldDefinition->getAllowedClassId();
        $elementTypeDefinition = ClassTypeDefinitions::get($className);
        $metadataKeyValuePairType = ElementMetadataKeyValuePairType::getInstance();
        $resolver = new ObjectMetadata($fieldDefinition, $class, $fieldHelper);

        $fields = ['element' =>
            [
                'type' => $elementTypeDefinition,
                'resolve' => $resolver->resolveElement(...),
            ],
            'metadata' => [
                'type' => Type::listOf($metadataKeyValuePairType),
                'resolve' => $resolver->resolveMetadata(...),

            ]];

        $config['fields'] = $fields;

        return;
    }
}
