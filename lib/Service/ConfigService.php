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

use OCP\IConfig;

class ConfigService
{

    const SEARCH_DISPLAY_NEXTANT = 1;

    const SEARCH_DISPLAY_FILES = 2;

    const ACTION_LIVE_EXTRACT = 'index_files_live';

    const ACTION_LIVE_DOCUPDATE = 'index_files_live';

    private $defaults = [
        'configured' => '0',
        'solr_url' => 'http://127.0.0.1:8983/solr/',
        'solr_core' => 'nextant',
        'display_result' => 1,
        
        'index_delay' => 2,
        'index_locked' => 0,
        'index_files_last' => 0,
        'index_bookmarks_last' => 0,
        
        'index_files' => 1,
        'index_files_needed' => 1,
        'index_files_update_needed' => 1,
        'index_files_tree' => 0,
        'undex_files_sharelink' => 0,
        'index_files_external' => 0,
        'index_files_encrypted' => 0,
        'index_files_live' => 1,
        'index_files_max_size' => 40,
        
        'index_bookmarks' => 0,
        'index_bookmarks_needed' => 1
    ];

    private $appName;

    private $config;

    private $miscService;

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
        $this->deleteAppValue('solr_lock');
        $this->deleteAppValue('needed_index');
        $this->deleteAppValue('last_index');
        $this->deleteAppValue('live_extract');
        $this->deleteAppValue('live_docupdate');
        $this->deleteAppValue('max_size');
        $this->deleteAppValue('external_index');
        $this->deleteAppValue('index_files_live_extract');
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
        
        return ($this->getAppValue('index_' . $type . '_last') < (time() - (3600 * $delay)));
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

    /**
     * return if config allow to perform action
     *
     * @param string $action            
     * @return boolean
     */
    // public function shoudIContinue($action)
    // {
    // switch ($action) {
    // case self::ACTION_LIVE_EXTRACT:
    // return ($this->getAppValue($action) == '1');
    
    // case self::ACTION_LIVE_DOCUPDATE:
    // if ($this->getAppValue(self::ACTION_LIVE_EXTRACT) != '1')
    // return false;
    // return ($this->getAppValue($action) == '1');
    // }
    // }
    
    /**
     * generate an array to pass config to Solarium
     *
     * @return array
     */
    public function toSolarium($config = null)
    {
        if ($config == null || ! key_exists('solr_url', $config))
            $config['solr_url'] = $this->getAppValue('solr_url');
        
        if ($config == null || ! key_exists('solr_core', $config))
            $config['solr_core'] = $this->getAppValue('solr_core');
        
        $url = $config['solr_url'];
        $t = parse_url($url);
        
        if (! key_exists('host', $t) || ! key_exists('port', $t) || ! key_exists('path', $t))
            return false;
        
        return array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $t['host'],
                    'port' => $t['port'],
                    'core' => $config['solr_core'],
                    'path' => str_replace('//', '/', $t['path'])
                )
            )
        );
    }
}