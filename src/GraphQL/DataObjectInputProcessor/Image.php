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
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use Override;

class Image extends Base
{
    /**
     * @param Concrete|AbstractData $object
     * @param array $newValue
     * @param array $args
     * @param array $context
     *
     * @return void|null
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();

        if (!array_key_exists('id', $newValue)) {
            if (OpenDxpDataHubBundle::getNotAllowedPolicy() == OpenDxpDataHubBundle::NOT_ALLOWED_POLICY_EXCEPTION) {
                throw new UserError("Field {$attribute}.id was not provided.");
            }

            return null;
        }

        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue) {
            $image = null;

            if (isset($newValue['id'])) {
                $asset = Asset::getById($newValue['id']);
                if ($asset instanceof Asset\Image) {
                    $image = $asset;
                }
            }

            return $container->$setter($image);
        });
    }
}
