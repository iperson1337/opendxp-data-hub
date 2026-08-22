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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementIdentificationTrait;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use OpenDxp\Model\Exception\NotFoundException;
use Override;

class ManyToManyObjectRelation extends Base
{
    use ElementIdentificationTrait;

    /**
     * @param Concrete|AbstractData $object
     * @param array $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    #[Override]
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue) {
            $result = [];
            if (is_array($newValue)) {
                foreach ($newValue as $newValueItemKey => $newValueItemValue) {
                    if (isset($newValueItemValue['type']) && $newValueItemValue['type'] !== 'object') {
                        throw new ClientSafeException('expected object type');
                    }

                    $element = $this->getElementByTypeAndIdOrPath($newValueItemValue, 'object');

                    if ($element) {
                        $result[] = $element;
                    } else {
                        throw new NotFoundException(
                            sprintf('Element with id %s or fullpath %s not found',
                                $newValueItemValue['id'],
                                $newValueItemValue['fullpath']
                            )
                        );
                    }
                }
            }

            return $container->$setter($result);
        });
    }
}
