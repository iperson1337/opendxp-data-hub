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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document\Service as DocumentService;

class Document extends Element
{
    use ServiceTrait;

    /**
     * @var DocumentService
     */
    protected $documentService;

    public function __construct(DocumentService $documentService, Service $graphQlService)
    {
        parent::__construct('document', $graphQlService);

        $this->documentService = $documentService;
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    public function resolveTranslations($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null): array
    {
        $languageRequested = $args['defaultLanguage'] ?? null;

        $document = \OpenDxp\Model\Document::getById($value['id']);
        $result = [];

        if ($document) {
            $documentId = $document->getId();
            foreach ($this->documentService->getTranslations($document) as $transLanguage => $transId) {
                if ($transId === $documentId || ($languageRequested && $languageRequested !== $transLanguage)) {
                    continue;
                }

                $result[] = [
                    'id' => $transId,
                    'language' => $transLanguage,
                    'target' => $this->resolveTranslationTarget($value, $args, $context, $resolveInfo),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $value
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    public function resolveTranslationTarget($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null): ?ElementDescriptor
    {
        $document = \OpenDxp\Model\Document::getById($value['id']);
        if ($document instanceof \OpenDxp\Model\Document) {
            return $this->extractSingleElement($document, $args, $context, $resolveInfo);
        }

        return null;
    }
}
