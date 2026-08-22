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

use Carbon\Carbon;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FeatureDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\CsFeature;

class DateType extends ObjectType
{
    /** @var static|null */
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $fields = Helper::getCommonFields();
            $fields['date'] = [
                'type' => Type::string(),
                'args' => [
                    ['name' => 'format',
                        'type' => Type::string(),
                        'description' => 'see Carbon::format',
                    ],
                ],
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        $dateValue = $value->getValue();
                        if ($dateValue instanceof Carbon) {
                            if ($args['format']) {
                                $format = $args['format'];
                                $formattedValue = $dateValue->format($format);
                            } else {
                                $formattedValue = (string)$dateValue;
                            }

                            return $formattedValue;
                        }
                    }
                },
            ];

            $config =
                [
                    'name' => 'csFeatureDate',
                    'interfaces' => [CsFeature::getInstance()],
                    'fields' => $fields,

                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
