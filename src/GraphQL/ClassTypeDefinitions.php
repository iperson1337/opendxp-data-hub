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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL;

use Exception;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\OpenDxpObjectType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\ClassDefinition;

class ClassTypeDefinitions
{
    /**
     * @var array
     */
    public static $definitions = [];

    /**
     * @param array $context
     */
    public static function build(Service $graphQlService, $context = [])
    {
        $db = Db::get();
        $listing = $db->fetchAllAssociative('SELECT id, name FROM classes');
        foreach ($listing as $class) {
            $definition = $graphQlService->buildDataObjectType($class['name'], [], $context);
            // Only add non-null definitions (skip missing classes)
            if ($definition !== null) {
                self::$definitions[$class['name']] = $definition;
            }
        }

        /**
         * @var OpenDxpObjectType $definition
         */
        foreach (self::$definitions as $definition) {
            // Skip null definitions (missing classes)
            if ($definition !== null) {
                $definition->build($context);
            }
        }
    }

    /**
     * @param string|ClassDefinition $class
     *
     * @return OpenDxpObjectType
     *
     * @throws Exception
     */
    public static function get($class)
    {
        $className = is_string($class) ? $class : $class->getName();
        $result = self::$definitions[$className] ?? null;
        if (!$result) {
            throw new ClientSafeException('type definition ' . $className . ' not found');
        }

        return $result;
    }

    /**
     * @param bool $onlyQueryTypes
     *
     * @return array
     *
     * @throws Exception
     */
    public static function getAll($onlyQueryTypes = false)
    {
        if ($onlyQueryTypes) {
            $context = RuntimeCache::get(OpenDxpDataHubBundle::RUNTIME_CONTEXT_KEY);
            /** @var Configuration $configuration */
            $configuration = $context['configuration'];
            $types = array_keys($configuration->getConfiguration()['schema']['queryEntities']);
            $result = [];
            foreach ($types as $type) {
                if (isset(self::$definitions[$type])) {
                    $result[] = self::$definitions[$type];
                }
            }

            return $result;
        }

        return self::$definitions;
    }
}
