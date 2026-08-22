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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\Traits;

use OpenDxp\Model\Element\Tag;

trait ElementTagTrait
{
    /**
     * @return array
     */
    protected function getTags(string $element_type, int $id)
    {
        $tag = new Tag();
        $tags = $tag->getDao()->getTagsForElement($element_type, $id);
        $result = [];
        if ($tags) {
            foreach ($tags as $tag) {
                $result[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'path' => $tag->getNamePath(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    protected function setTags(string $element_type, int $id, $tags)
    {
        $tag = new Tag;
        $tag->getDao()->setTagsForElement($element_type, $id, $tags);

        return true;
    }

    /**
     * @return array|bool
     */
    protected function getTagsFromInput(array $input)
    {
        $tags = [];
        foreach ($input as $tag_input) {
            if (isset($tag_input['id']) && $tag_input['id']) {
                $tag = Tag::getById((int)$tag_input['id']);
            } elseif (isset($tag_input['path']) && $tag_input['path']) {
                $tag = Tag::getByPath($tag_input['path']);
            } else {
                return false;
            }
            if (!$tag) {
                return false;
            }
            $tags[] = $tag;
        }

        return $tags;
    }
}
