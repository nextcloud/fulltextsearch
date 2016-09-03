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
namespace OCA\Nextant\Hooks;

/**
 * init Files' Events
 */
class FilesHooks
{

    /**
     * retreive the FilesEvents' Controller
     *
     * @return FilesHooks
     */
    static protected function getController()
    {
        $app = new \OCA\Nextant\AppInfo\Application();
        return $app->getContainer()->query('FilesEvents');
    }

    /**
     * hook events: file is created
     *
     * @param array $params
     *            The hook params
     */
    public static function fileCreated($params)
    {
        self::getController()->onFileCreate($params['path']);
    }

    /**
     * hook events: file is updated
     *
     * @param array $params            
     */
    public static function fileUpdated($params)
    {
        self::getController()->onFileUpdate($params['path']);
    }

    /**
     * hook events: file is renamed
     *
     * @param array $params            
     */
    public static function fileRenamed($params)
    {
        self::getController()->onFileRename($params['oldpath'], $params['newpath']);
    }

    /**
     * hook event: file is deleted
     *
     * @param array $params            
     */
    public static function fileDeleted($params)
    {
        self::getController()->onFileDelete($params['path']);
    }

    /**
     * hook event: file is restored
     *
     * @param array $params            
     */
    public static function fileRestored($params)
    {
        self::getController()->onFileRestore($params['filePath']);
    }

    /**
     * hook event: file is shared
     *
     * @param array $params            
     */
    public static function fileShared($params)
    {
        self::getController()->onFileShare($params['fileSource'], $params['shareWith'], ($params['shareType'] == 1) ? true : false);
    }

    /**
     * hook event: file is unshared
     *
     * @param array $params            
     */
    public static function fileUnshared($params)
    {
        if (key_exists('fileSource', $params))
            self::getController()->onFileUnshare($params['fileSource'], $params['shareWith'], ($params['shareType'] == 1) ? true : false);
    }

    /**
     * hook event: file is scanned
     */
    public static function fileScanned($params)
    {
        self::getController()->onFileScan($params['path']);
    }
}

