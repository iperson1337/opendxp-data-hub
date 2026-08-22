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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class ImageGallery extends Base
{
    /**
     * @param Concrete|AbstractData $object
     * @param mixed $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        $getter = 'get' . ucfirst((string) $attribute);
        $currentGallery = $object->$getter();

        if ($currentGallery instanceof \OpenDxp\Model\DataObject\Data\ImageGallery) {
            $currentItems = $currentGallery->getItems() ?: [];
        } else {
            $currentItems = [];
        }

        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue, $currentItems) {
            $hotspotImages = [];
            $newGallery = [];

            if ($newValue === null) {
                return $container->$setter($newGallery);
            }

            if (! ($newValue['replace'] ?? false)) {
                foreach ($currentItems as $currentItem) {
                    if ($currentItem instanceof Hotspotimage) {
                        $hotspotImages[] = $currentItem;
                    }
                }
            }

            if (is_array($newValue['images'])) {
                foreach ($newValue['images'] as $imageValue) {
                    $hotspotImages[] = new Hotspotimage($imageValue['id']);
                }

                $newGallery = new \OpenDxp\Model\DataObject\Data\ImageGallery($hotspotImages);
            }

            return $container->$setter($newGallery);
        });
    }
}
