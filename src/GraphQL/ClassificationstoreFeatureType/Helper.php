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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\ClassificationstoreFeatureType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FeatureDescriptor;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;

class Helper extends ObjectType
{
    /**
     * @return array
     */
    public static function getCommonFields()
    {
        $fields = [
            'id' => [
                'type' => Type::int(),
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        return $value->getId();
                    }
                },
            ],
            'name' => [
                'type' => Type::string(),
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        $keyConfig = KeyConfig::getById($value->getId());
                        if ($keyConfig) {
                            return $keyConfig->getName();
                        }
                    }
                },
            ],
            'title' => [
                'type' => Type::string(),
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        $keyConfig = KeyConfig::getById($value->getId());
                        if ($keyConfig) {
                            return $keyConfig->getTitle();
                        }
                    }

                    return null;
                },
            ],
            'description' => [
                'type' => Type::string(),
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        $keyConfig = KeyConfig::getById($value->getId());
                        if ($keyConfig) {
                            return $keyConfig->getDescription();
                        }
                    }
                },
            ],
            'type' => [
                'type' => Type::string(),
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        return $value->getType();
                    }
                },
            ],
        ];

        return $fields;
    }
}
