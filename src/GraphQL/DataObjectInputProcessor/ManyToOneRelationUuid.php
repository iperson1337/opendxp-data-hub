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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ElementIdentificationTrait;
use OpenDxp\Bundle\DataHubBundle\Service\DataObjectByUuidResolver;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;
use OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use OpenDxp\Model\Exception\NotFoundException;

final class ManyToOneRelationUuid extends Base
{
    use ElementIdentificationTrait;

    /**
     * @var DataObjectByUuidResolver
     */
    private $uuidResolver;

    public function __construct(array $nodeDef, DataObjectByUuidResolver $uuidResolver)
    {
        parent::__construct($nodeDef);
        $this->uuidResolver = $uuidResolver;
    }

    /**
     * @param Concrete|AbstractData $object
     * @param array|null $newValue
     *
     * @throws \Exception
     */
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();
        $uuidResolver = $this->uuidResolver;

        Service::setValue($object, $attribute, function ($container, $setter) use ($newValue, $uuidResolver, $attribute) {
            $element = null;

            if (is_array($newValue)) {
                $uuid = isset($newValue['uuid']) && is_string($newValue['uuid']) ? trim($newValue['uuid']) : '';
                if ($uuid !== '') {
                    $targetClassName = $this->getTargetClassName($container);
                    if ($targetClassName !== null) {
                        $element = $uuidResolver->resolve($targetClassName, $uuid);
                        if ($element === null) {
                            throw new ClientSafeException(
                                $attribute . ': object not found by uuid ' . $uuid . ' (class: ' . $targetClassName . ')'
                            );
                        }
                    }
                }
                if ($element === null) {
                    if ($uuid !== '') {
                        throw new ClientSafeException(
                            $attribute . ': cannot resolve by uuid (target class may not have uuid field or relation not configured)'
                        );
                    }
                    $element = $this->getElementByTypeAndIdOrPath($newValue);
                    if (!$element) {
                        throw new NotFoundException(
                            sprintf(
                                '%s: element with id %s or fullpath %s not found',
                                $attribute,
                                $newValue['id'] ?? '?',
                                $newValue['fullpath'] ?? '?'
                            )
                        );
                    }
                }
            }

            return $container->$setter($element);
        });
    }

    /**
     * @param Concrete|AbstractData|ObjectbrickAbstractData $container
     */
    private function getTargetClassName($container): ?string
    {
        $classDefinition = null;
        if ($container instanceof Concrete) {
            $classDefinition = $container->getClass();
        } elseif ($container instanceof ObjectbrickAbstractData) {
            $classDefinition = $container->getDefinition();
        } elseif ($container instanceof AbstractData) {
            $classDefinition = $container->getDefinition();
        }
        if (!$classDefinition) {
            return null;
        }

        $fieldName = $this->getAttribute();
        $parts = explode('~', $fieldName);
        if (count($parts) > 1) {
            $fieldName = end($parts);
        }

        $fieldDefinition = $classDefinition->getFieldDefinition($fieldName);
        if (!$fieldDefinition instanceof \OpenDxp\Model\DataObject\ClassDefinition\Data\ManyToOneRelation) {
            return null;
        }

        $classes = $fieldDefinition->getClasses();
        if (empty($classes) || !is_array($classes)) {
            return null;
        }

        $first = $classes[0];
        $className = is_array($first) ? ($first['classes'] ?? null) : $first;

        return is_string($className) ? $className : null;
    }
}
