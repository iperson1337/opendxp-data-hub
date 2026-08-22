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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator;

use Closure;
use Exception;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use Override;

/**
 * Class Hotspotimage
 *
 * @package OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator
 */
class Hotspotimage extends Base
{
    public const TYPE = 'object_datatype_hotspotimage';

    /**
     * @param string $attribute
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return array
     *
     *@throws Exception
     */
    #[Override]
    public function getGraphQlFieldConfig($attribute, Data $fieldDefinition, $class = null, $container = null)
    {
        return $this->enrichConfig($fieldDefinition, $class, $attribute,
            [
                'name' => $fieldDefinition->getName(),
                'type' => $this->getFieldType($fieldDefinition, $class, $container),
                'resolve' => $this->getResolver($attribute, $fieldDefinition, $class),
            ],
            $container
        );
    }

    /**
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return mixed
     *
     * @throws Exception
     */
    #[Override]
    public function getFieldType(Data $fieldDefinition, $class = null, $container = null)
    {
        $hotspotType = $this->getGraphQlService()->getDataObjectTypeDefinition('object_datatype_hotspotimage');

        return $hotspotType;
    }

    /**
     * @param string $attribute
     * @param Data $fieldDefinition
     * @param ClassDefinition $class
     *
     * @return Closure
     */
    #[Override]
    public function getResolver($attribute, $fieldDefinition, $class)
    {

        /** @var Data\Hotspotimage $fieldDefinition */
        $resolver = new Helper\Hotspotimage($this->getGraphQlService(), $attribute, $fieldDefinition, $class);

        return $resolver->resolve(...);
    }
}
