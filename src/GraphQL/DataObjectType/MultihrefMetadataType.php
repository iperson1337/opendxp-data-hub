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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\MultihrefMetadata;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;

class MultihrefMetadataType extends ObjectType
{
    use ServiceTrait;

    /**
     * @var null
     */
    protected $class;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param ClassDefinition|Definition|null $class
     * @param array $config
     */
    public function __construct(Service $graphQlService, ?Data $fieldDefinition = null, $class = null, $config = [])
    {
        $this->class = $class;
        $this->setGraphQlService($graphQlService);
        $this->fieldDefinition = $fieldDefinition;
        $name = ($class instanceof Definition) ? $class->getKey() : $class->getName();

        $config['name'] = 'object_'.$name.'_'.$fieldDefinition->getName();
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $fieldDefinition = $this->fieldDefinition;
        $class = $this->class;
        $metadataKeyValuePairType = ElementMetadataKeyValuePairType::getInstance();
        $resolver = new MultihrefMetadata($fieldDefinition, $class, $this->getGraphQlService()->getObjectFieldHelper());
        $fields = ['element' =>
                       [
                           'type' => new HrefType($this->getGraphQlService(), $this->fieldDefinition, $this->class),
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
