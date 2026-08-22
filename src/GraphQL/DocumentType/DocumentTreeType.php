<?php
declare(strict_types=1);

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

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;

class DocumentTreeType extends UnionType
{
    use ServiceTrait;

    private $types;

    private $customTypes = [];

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'document_tree'])
    {
        $this->setGraphQLService($graphQlService);
        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $types = [];

        $supportedTypes = [
            '_document_folder',
            'document_email',
            'document_hardlink',
            'document_link',
            'document_page',
            'document_snippet',
        ];
        foreach ($supportedTypes as $supportedType) {
            $this->types[$supportedType] = $this->getGraphQlService()->getDocumentTypeDefinition($supportedType);
            $types[] = $this->types[$supportedType];
        }

        $document = $this->getGraphQlService()->getDocumentTypeDefinition('document');
        if (count($document->getCustomDataTypes())) {
            foreach ($document->getCustomDataTypes() as $ckey => $customType) {
                $this->customTypes[$ckey] = $this->getGraphQlService()->getDocumentTypeDefinition($ckey);
                $types[] = $this->customTypes[$ckey];
            }
        }

        return $types;
    }

    /**
     * @param ElementInterface $element
     * @param array $context
     *
     * @return mixed
     */
    public function resolveType($element, $context, ResolveInfo $info)
    {
        $rawElement = $element;
        $element = Document::getById($element['id']);

        if ($element instanceof Document\Folder) {
            return $this->types['_document_folder'];
        }
        if ($element instanceof Document\Page) {
            return $this->types['document_page'];
        }
        if ($element instanceof Document\Link) {
            return $this->types['document_link'];
        }
        if ($element instanceof Document\Email) {
            return $this->types['document_email'];
        }
        if ($element instanceof Document\Hardlink) {
            return $this->types['document_hardlink'];
        }
        if ($element instanceof Document\Snippet) {
            return $this->types['document_snippet'];
        }

        if (count($this->customTypes)) {
            foreach ($this->customTypes as $customType) {
                if ($customType->isTypeof($rawElement, $context, $info)) {
                    return $customType;
                }
            }
        }

        return null;
    }
}
