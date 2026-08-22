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

namespace OpenDxp\Bundle\DataHubBundle\Configuration;

use OpenDxp;
use OpenDxp\Bundle\DataHubBundle\Configuration;
use OpenDxp\Config;
use OpenDxp\Model;
use Override;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * Class Dao
 *
 * @package OpenDxp\Bundle\DataHubBundle\Configuration
 *
 * @property Configuration $model
 */
class Dao extends Model\Dao\OpenDxpLocationAwareConfigDao
{
    public const ROOT_PATH = '/';

    /**
     * path to the configuration file
     */
    public const CONFIG_FILE = 'datahub-configurations.php';

    /**
     * @var null|array
     */
    private static $_config = null;

    /**
     * @deprecated Will be removed as soon as Pimcore 10.6 isn´t supported anymore.
     */
    public const CONFIG_PATH = OPENDXP_CONFIGURATION_DIRECTORY . '/data_hub';

    #[Override]
    public function configure(): void
    {
        $config = OpenDxp::getContainer()->getParameter('opendxp_data_hub');

        $storageConfig = $config['config_location']['data_hub'];

        parent::configure([
            'containerConfig' => $config['configurations'] ?? [],
            'settingsStoreScope' => 'opendxp_data_hub',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     * save a configuration.
     */
    public function save(): void
    {
        if (!$this->model->getName()) {
            $this->model->setName(Uid::v4());
        }

        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $data = $this->model->getObjectVars();
        $this->saveData($this->model->getName(), $data);
    }

    /**
     * delete a configuration.
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getName());
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function setVariables($data)
    {
        $this->model->setConfiguration($data);
        $this->model->setName($data['general']['name'] ?? '');
        $this->model->setType($data['general']['type'] ?? '');
        $this->model->setPath($data['general']['path'] ?? '');
        $this->model->setModificationDate($data['general']['modificationDate'] ?? null);
        $this->model->setCreationDate($data['general']['createDate'] ?? null);
        $this->model->setGroup($data['general']['group'] ?? '');
    }

    /**
     * @internal
     *
     * gets a configuration by name.
     *
     * @param string $name
     */
    public function loadByName($name)
    {
        $data = $this->getDataByName($name);

        if (!$data) {
            $data = $this->getDataByName('list');
            $data = $data[$name] ?? null;
        }
        if ($data) {
            $this->setVariables($data);
        } else {
            throw new Model\Exception\NotFoundException('Configuration with name: ' . $name . ' does not exist');
        }
    }

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * get a configuration by name.
     *
     * TODO: remove this static function and rename "loadByName" to "getByName"
     *
     * @param string $name
     */
    public static function getByName($name)
    {
        try {
            $config = new Configuration(null, null);
            $config->getDao()->loadByName($name);

            return $config;
        } catch (\OpenDxp\Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     * @return int
     *
     *@deprecated will be removed with pimcore 11
     *
     * get latest modification date of configuration file.
     */
    public static function getConfigModificationDate()
    {
        return 0;
    }

    /**
     * get the whole configuration file content.
     *
     * @return array
     */
    private function &getConfig()
    {
        if (self::$_config) {
            return self::$_config;
        }
        $config = [];

        $list = $this->loadIdList();
        foreach ($list as $name) {
            $data = $this->getDataByName($name);
            if ($this->dataSource !== Config\LocationAwareConfigRepository::LOCATION_SETTINGS_STORE
                && $this->dataSource !== Config\LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG) {
                if ($name === 'folders') {
                    unset($data[$name]);
                } else {
                    foreach ($data as $key => $legacyItem) {
                        $config[$key] = $legacyItem;
                    }
                }
            } else {
                $config[$name] = $data;
            }
        }

        self::$_config = $config;

        return self::$_config;
    }

    /**
     * get the list of configurations.
     */
    public function loadList(): array
    {
        $list = [];

        $configs = &$this->getConfig();
        foreach ($configs as $item) {
            $name = $item['general']['name'];
            $configuration = Configuration::getByName($name);
            $list[$name] = $configuration;
        }

        return $list;
    }

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * get the list of configurations.
     *
     * TODO: remove this static function and rename "loadList" to "getList"
     */
    public static function getList(): array
    {
        $configuration = new Configuration(null, null);

        return $configuration->getDao()->loadList();
    }

    /**
     * @param mixed $data
     *
     * @return array[][][]
     */
    #[Override]
    protected function prepareDataStructureForYaml(string $id, $data): mixed
    {
        return [
            'opendxp_data_hub' => [
                'configurations' => [
                    $id => $data,
                ],
            ],
        ];
    }
}
