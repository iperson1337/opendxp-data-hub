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

namespace OpenDxp\Bundle\DataHubBundle\EventListener;

use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Event\AssetEvents;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\AssetEvent;
use OpenDxp\Event\Model\DataObjectEvent;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class DataChangeListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::POST_UPDATE => 'onObjectUpdate',
            DataObjectEvents::POST_DELETE => 'onObjectDelete',
            DocumentEvents::POST_UPDATE => 'onDocumentUpdate',
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
            AssetEvents::POST_UPDATE => 'onAssetUpdate',
            AssetEvents::POST_DELETE => 'onAssetDelete',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function onObjectUpdate(DataObjectEvent $e)
    {
        if (!$e->hasArgument('oldPath')) {
            return;
        }

        $object = $e->getObject();
        $oldPath = $e->getArgument('oldPath');

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_OBJECT, WorkspaceHelper::MODIFY_TYPE_REPLACE, $oldPath, $object->getRealFullPath());
    }

    /**
     * @throws ValidationException
     */
    public function onObjectDelete(DataObjectEvent $e)
    {
        $object = $e->getObject();

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_OBJECT, WorkspaceHelper::MODIFY_TYPE_DELETE, $object->getRealFullPath(), null);
    }

    /**
     * @throws ValidationException
     */
    public function onDocumentUpdate(DocumentEvent $e)
    {
        if (!$e->hasArgument('oldPath')) {
            return;
        }

        $document = $e->getDocument();
        $oldPath = $e->getArgument('oldPath');

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_DOCUMENT, WorkspaceHelper::MODIFY_TYPE_REPLACE, $oldPath, $document->getRealFullPath());
    }

    /**
     * @throws ValidationException
     */
    public function onDocumentDelete(DocumentEvent $e)
    {
        $object = $e->getDocument();

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_DOCUMENT, WorkspaceHelper::MODIFY_TYPE_DELETE, $object->getRealFullPath(), null);
    }

    /**
     * @throws ValidationException
     */
    public function onAssetUpdate(AssetEvent $e)
    {
        if (!$e->hasArgument('oldPath')) {
            return;
        }

        $asset = $e->getAsset();
        $oldPath = $e->getArgument('oldPath');

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_ASSET, WorkspaceHelper::MODIFY_TYPE_REPLACE, $oldPath, $asset->getRealFullPath());
    }

    /**
     * @throws ValidationException
     */
    public function onAssetDelete(AssetEvent $e)
    {
        $asset = $e->getAsset();

        $this->checkConfiguration(WorkspaceHelper::MODIFY_SPACE_ASSET, WorkspaceHelper::MODIFY_TYPE_DELETE, $asset->getRealFullPath(), null);
    }

    /**
     * @param string      $dataType
     * @param string      $modificationType
     * @param string      $searchValue
     * @param string|null $replaceValue
     *
     * @throws ValidationException
     */
    protected function checkConfiguration($dataType, $modificationType, $searchValue, $replaceValue)
    {
        $configList = Configuration::getList();

        if (!is_array($configList)) {
            return;
        }

        foreach ($configList as $configurationEntity) {
            try {
                $entity = WorkspaceHelper::modifyWorkspaceRowByType($configurationEntity, $dataType, $modificationType, $searchValue, $replaceValue);
            } catch (Throwable $e) {
                throw new ValidationException(sprintf('Could not modify workspace row: %s', $e->getMessage()), 0, $e);
            }

            if (!$entity instanceof Configuration) {
                continue;
            }

            try {
                $entity->save();
            } catch (Throwable $e) {
                throw new ValidationException(sprintf('Could not save configuration: %s', $e->getMessage()), 0, $e);
            }
        }
    }
}
