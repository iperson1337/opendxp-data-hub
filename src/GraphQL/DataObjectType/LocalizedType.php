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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use OpenDxp\Tool;
use Throwable;

class LocalizedType extends InputObjectType
{
    /**
     * @var array<string, LocalizedType>
     */
    protected static $instances;

    /**
     * @param mixed $determinedType
     *
     * @return mixed
     */
    public static function getInstance($determinedType)
    {
        try {
            $determinedTypeName = $determinedType->toString();

            if ($determinedType instanceof ListOfType) {
                $determinedTypeName = $determinedType->getWrappedType()->toString() . 'List';
            }
        } catch (Throwable) {
            return $determinedType;
        }

        if (!isset(self::$instances[$determinedTypeName])) {
            $config = ['name' => 'Localized' . $determinedTypeName];

            foreach (Tool::getValidLanguages() as $language) {
                $config['fields'][$language] = [
                    'type' => $determinedType,
                ];
            }

            self::$instances[$determinedTypeName] = new static($config);
        }

        return self::$instances[$determinedTypeName];
    }
}
