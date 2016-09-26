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

    const ACTION_LIVE_EXTRACT = 'live_extract';

    const ACTION_LIVE_DOCUPDATE = 'live_docupdate';

    private $defaults = [
        'configured' => '0',
        'needed_index' => '2',
        'solr_url' => 'http://127.0.0.1:8983/solr/',
        'solr_core' => 'nextant',
        'live_extract' => '1',
        'live_docupdate' => '0',
        'solr_lock' => 0
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

    public function needIndex($need, $force = false)
    {
        if (! $need)
            $this->setAppValue('needed_index', '0');
        else 
            if ($force || $this->getAppValue('needed_index') == '0')
                $this->setAppValue('needed_index', '1');
    }

    public function neededIndex()
    {
        return ($this->getAppValue('needed_index') == '1');
    }

    public function stopIndex()
    {
        $this->setAppValue('needed_index', '2');
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
     * return if config allow to perform action
     *
     * @param string $action            
     * @return boolean
     */
    public function shoudIContinue($action)
    {
        switch ($action) {
            case self::ACTION_LIVE_EXTRACT:
                return ($this->getAppValue($action) == '1');
            
            case self::ACTION_LIVE_DOCUPDATE:
                if ($this->getAppValue(self::ACTION_LIVE_EXTRACT) != '1')
                    return false;
                return ($this->getAppValue($action) == '1');
        }
    }

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