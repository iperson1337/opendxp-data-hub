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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectMutationFieldConfigGenerator;

use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\ManyToOneRelationUuid as ManyToOneRelationUuidProcessor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\ObjectReferenceInputType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\Service\DataObjectByUuidResolver;

final class ManyToOneRelationUuid extends Base
{
    /**
     * @var DataObjectByUuidResolver
     */
    private $uuidResolver;

    /**
     * @var ObjectReferenceInputType
     */
    private $objectReferenceInputType;

    public function __construct(
        Service $graphQlService,
        DataObjectByUuidResolver $uuidResolver,
        ObjectReferenceInputType $objectReferenceInputType
    ) {
        parent::__construct($graphQlService);
        $this->uuidResolver = $uuidResolver;
        $this->objectReferenceInputType = $objectReferenceInputType;
    }

    /** {@inheritdoc } */
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container = null, $params = [])
    {
        $processor = new ManyToOneRelationUuidProcessor($nodeDef, $this->uuidResolver);
        $processor->setGraphQLService($this->getGraphQlService());

        return [
            'arg' => $this->objectReferenceInputType,
            'processor' => [$processor, 'process'],
        ];
    }
}
