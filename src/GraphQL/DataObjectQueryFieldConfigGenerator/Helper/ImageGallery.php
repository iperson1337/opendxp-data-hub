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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\Helper;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\BaseDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service as GraphQlService;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\Element\Service;

/**
 * Class ImageGallery
 *
 * @package OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\Helper
 */
class ImageGallery
{
    use ServiceTrait;

    /**
     * @var ClassDefinition\Data\ImageGallery
     */
    public $fieldDefinition;

    /**
     * @param string $attribute
     * @param ClassDefinition|Fieldcollection\Definition $class
     */
    public function __construct(
        GraphQlService $graphQlService,
        public $attribute,
        ClassDefinition\Data\ImageGallery $fieldDefinition,
        public $class
    ) {
        $this->fieldDefinition = $fieldDefinition;
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param BaseDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor[]|null
     *
     * @throws Exception
     */
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $result = [];
        $relations = GraphQlService::resolveValue($value, $this->fieldDefinition, $this->attribute, $args);
        if ($relations) {
            foreach ($relations as $relation) {
                if ($relation instanceof Hotspotimage) {
                    $image = $relation->getImage();
                } else {
                    continue;
                }

                if ($image instanceof Asset) {
                    if (!WorkspaceHelper::checkPermission($image, 'read')) {
                        continue;
                    }

                    $data = new ElementDescriptor($image);
                    $this->getGraphQlService()->extractData($data, $image, $args, $context, $resolveInfo);

                    $data['data'] = isset($data['data']) ? base64_encode((string) $data['data']) : null;
                    $data['crop'] = $relation->getCrop();
                    $data['hotspots'] = $relation->getHotspots();
                    $data['marker'] = $relation->getMarker();
                    $data['img'] = $image;
                    $data['image'] = $image->getType();
                    $data['__elementType'] = Service::getElementType($image);
                    $data['__elementSubtype'] = $image->getType();
                } else {
                    continue;
                }

                $result[] = $data;
            }
        }

        return !empty($result) ? $result : null;
    }
}
