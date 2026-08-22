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

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\ElementDescriptorInputType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use Override;

class ManyToManyRelation extends Base
{
    protected $elementInputType;

    public function __construct(Service $graphQlService, ElementDescriptorInputType $elementInputType)
    {
        $this->elementInputType = $elementInputType;
        parent::__construct($graphQlService);
    }

    /** {@inheritdoc } */
    #[Override]
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container = null, $params = [])
    {
        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\ManyToManyRelation($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $inputType = $this->getGraphQlService()->getDataObjectTypeDefinition('elementdescriptor_input');

        return [
            'arg' => ['type' => Type::listOf($inputType)],
            'processor' => $processor->process(...),
        ];
    }
}
