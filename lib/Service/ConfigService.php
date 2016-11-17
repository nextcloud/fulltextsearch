<?php

/**
 * Nextcloud - nextant
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright Maxence Lange 2016
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Nextant\Service;

use \OCA\Nextant\Controller\SettingsController;
use OCP\IConfig;

class ConfigService
{

    const SEARCH_DISPLAY_NEXTANT = 1;

    const SEARCH_DISPLAY_FILES = 2;

    private $defaults = [
        'configured' => '0',
        'solr_url' => 'http://127.0.0.1:8983/solr/',
        'solr_core' => 'nextant',
        'solr_timeout' => 30,
        
        'index_live' => 1,
        'index_live_queuekey' => 19375,
        'index_delay' => 2,
        'index_locked' => 0,
        'index_files_last' => 0,
        'index_bookmarks_last' => 0,
        
        'index_files' => 1,
        'index_files_needed' => 1,
        'index_files_update_needed' => 1,
        'index_files_tree' => 0,
        'index_files_nextant_only' => 0,
        'undex_files_sharelink' => 0,
        'index_files_external' => 0,
        'index_files_encrypted' => 0,
        'index_files_max_size' => 40,
        'index_files_filters_text' => 1,
        'index_files_filters_pdf' => 1,
        'index_files_filters_office' => 1,
        'index_files_filters_image' => 0,
        'index_files_filters_audio' => 0,
        'index_files_filters_extensions' => '',
        
        'index_bookmarks' => 0,
        'index_bookmarks_needed' => 1
    ];

    private $appName;

    private $config;

    private $miscService;

    private $fileFilters = null;

    public function __construct($appName, IConfig $config, $miscService)
    {
        $this->appName = $appName;
        $this->config = $config;
        $this->miscService = $miscService;
    }

    public function reset()
    {
        foreach ($this->defaults as $k => $v) {
            $this->setAppValue($k, $v);
        }
    }

    public function removeOldConfig()
    {
        $this->deleteAppValue('display_result');
        $this->deleteAppValue('solr_lock');
        $this->deleteAppValue('needed_index');
        $this->deleteAppValue('last_index');
        $this->deleteAppValue('live_extract');
        $this->deleteAppValue('live_docupdate');
        $this->deleteAppValue('max_size');
        $this->deleteAppValue('external_index');
        $this->deleteAppValue('index_files_live_extract');
        $this->deleteAppValue('index_files_live');
        $this->deleteAppValue('index_files_live_update');
    }

    public function needIndexFiles($need)
    {
        if ($need)
            $this->setAppValue('index_files_needed', '1');
        else
            $this->setAppValue('index_files_needed', '0');
    }

    public function neededIndexFiles()
    {
        return ($this->getAppValue('index_files_needed') == '1');
    }

    public function needIndexBookmarks($need)
    {
        if ($need)
            $this->setAppValue('index_bookmarks_needed', '1');
        else
            $this->setAppValue('index_bookmarks_needed', '0');
    }

    public function neededIndexBookmarks()
    {
        return ($this->getAppValue('index_bookmarks_needed') == '1');
    }

    public function stopIndex()
    {
        $this->setAppValue('configured', '2');
    }

    public function timeIndex($type)
    {
        $this->setAppValue('index_' . $type . '_last', time());
    }

    public function timeIndexDelay($type, $delay = 0)
    {
        if ($delay == 0)
            $delay = $this->getAppValue('index_delay');
            
            // Uncomment this line to force index each tick of backgroundjob
            // $delay = 0;
        return ($this->getAppValue('index_' . $type . '_last') < (time() - (3600 * $delay)));
    }

    public function lockIndex($lock)
    {
        if ($lock)
            $this->setAppValue('index_locked', time());
        else
            $this->setAppValue('index_locked', '0');
    }

    /**
     * returns false if index is not locked or number of seconds since last tick
     * after 10 minutes, lock is reseted
     *
     * @return boolean|number
     */
    public function isLockedIndex(&$delay = 0)
    {
        $lock = $this->getAppValue('index_locked');
        if ($lock === '0')
            return false;
        
        $delay = time() - $lock;
        if ($delay > 600)
            $this->lockIndex(false);
        else
            return true;
        
        return false;
    }

    /**
     * Get a value by key
     *
     * @param string $key            
     * @return string
     */
    public function getAppValue($key)
    {
        $defaultValue = null;
        if (array_key_exists($key, $this->defaults))
            $defaultValue = $this->defaults[$key];
        return $this->config->getAppValue($this->appName, $key, $defaultValue);
    }

    /**
     * Set a value by key
     *
     * @param string $key            
     * @param string $value            
     * @return string
     */
    public function setAppValue($key, $value)
    {
        return $this->config->setAppValue($this->appName, $key, $value);
    }

    /**
     * remove a key
     *
     * @param string $key            
     * @return string
     */
    public function deleteAppValue($key)
    {
        return $this->config->deleteAppValue($this->appName, $key);
    }

    public function getFileFilters()
    {
        if ($this->fileFilters == null)
            $this->fileFilters = array(
                'text' => $this->getAppValue('index_files_filters_text'),
                'pdf' => $this->getAppValue('index_files_filters_pdf'),
                'office' => $this->getAppValue('index_files_filters_office'),
                'image' => $this->getAppValue('index_files_filters_image'),
                'audio' => $this->getAppValue('index_files_filters_audio'),
                'extensions' => SettingsController::FileFiltersExtensionsAsArray($this->getAppValue('index_files_filters_extensions'))
            );
        
        return $this->fileFilters;
    }

    /**
     * generate an array to pass config to Solarium
     *
     * @return array
     */
    public function toSolarium($config = null)
    {
        if ($config == null)
            $config = array();
        
        if (! key_exists('solr_url', $config))
            $config['solr_url'] = $this->getAppValue('solr_url');
        
        if (! key_exists('solr_core', $config))
            $config['solr_core'] = $this->getAppValue('solr_core');
        
        if (! key_exists('timeout', $config))
            $config['timeout'] = $this->getAppValue('solr_timeout');
        
        $url = $config['solr_url'];
        $t = parse_url($url);
        
        if (! key_exists('host', $t) || ! key_exists('port', $t) || ! key_exists('path', $t))
            return false;
        
        return array(
            'endpoint' => array(
                'localhost' => array(
                    'timeout' => ($config['timeout'] < 5) ? 5 : $config['timeout'],
                    'host' => $t['host'],
                    'port' => $t['port'],
                    'core' => $config['solr_core'],
                    'path' => str_replace('//', '/', $t['path'])
                )
            )
        );
    }
}