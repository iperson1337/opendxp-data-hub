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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType;

use Closure;
use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\Document\Editable\Renderlet;
use OpenDxp\Model\Element;

class RenderletType extends ObjectType
{
    protected static $instance;

    /**
     * @return RenderletType
     *
     * @throws Exception
     */
    public static function getInstance(Service $graphQlService)
    {
        if (!self::$instance) {
            $anyTargetType = $graphQlService->buildGeneralType('anytarget');

            $config = [
                'name' => 'document_editableRenderlet',
                'fields' => [
                    '_editableType' => [
                        'type' => Type::string(),
                        'resolve' => self::resolveRenderlet(static fn (Renderlet $r) => $r->getType()),
                    ],
                    '_editableName' => [
                        'type' => Type::string(),
                        'resolve' => self::resolveRenderlet(static fn (Renderlet $r) => $r->getName()),
                    ],
                    'id' => [
                        'type' => Type::int(),
                        'resolve' => self::resolveRenderlet(static fn (Renderlet $r) => $r->getId()),
                    ],
                    'type' => [
                        'type' => Type::string(),
                        'resolve' => self::resolveRenderlet(
                            static fn (Renderlet $r) => $r->getData()['type'] ?? null,
                        ),
                    ],
                    'subtype' => [
                        'type' => Type::string(),
                        'resolve' => self::resolveRenderlet(static fn (Renderlet $r) => $r->getSubtype()),
                    ],
                    'relation' => [
                        'type' => $anyTargetType,
                        'resolve' => static function (
                            $value = null,
                            $args = [],
                            $context = [],
                            ?ResolveInfo $resolveInfo = null,
                        ) use ($graphQlService) {
                            if (!$value instanceof Renderlet) {
                                return null;
                            }

                            // getO() does not lazy-load and $o is stripped when the
                            // document is serialized into the core cache, so resolve
                            // the target explicitly (like Renderlet::frontend() does).
                            $value->load();

                            $target = $value->getO();
                            if (!$target instanceof Element\ElementInterface) {
                                return null;
                            }

                            // don't leak unpublished elements (Relation::getElement() filters these too)
                            if (
                                Element\Service::doHideUnpublished($target)
                                && !Element\Service::isPublished($target)
                            ) {
                                return null;
                            }

                            $desc = new ElementDescriptor($target);
                            $graphQlService->extractData($desc, $target, $args, $context, $resolveInfo);

                            return $desc;
                        },
                    ],
                ],
            ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }

    /**
     * Wraps a field resolver so it only runs for a Renderlet editable.
     *
     * @param callable(Renderlet): mixed $resolver
     */
    private static function resolveRenderlet(callable $resolver): Closure
    {
        return static function ($value = null) use ($resolver) {
            if ($value instanceof Renderlet) {
                return $resolver($value);
            }

            return null;
        };
    }
}
