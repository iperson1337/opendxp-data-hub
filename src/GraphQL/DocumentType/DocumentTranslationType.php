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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class DocumentTranslationType extends ObjectType
{
    use ServiceTrait;

    public function __construct(Service $graphQlService, array $config = ['name' => 'document_translation', 'fields' => []])
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
        $anyTargetType = $this->graphQlService->buildGeneralType('document_tree');
        $documentResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Document(new \OpenDxp\Model\Document\Service(), $this->getGraphQlService());

        $config['fields']['id'] = Type::int();
        $config['fields']['language'] = Type::string();
        $config['fields']['target'] = [
            'type' => $anyTargetType,
            'resolve' => $documentResolver->resolveTranslationTarget(...),
        ];
    }
}
