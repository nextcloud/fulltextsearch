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
namespace OCA\Nextant\Db;

use OCA\Nextant\Db\LiveQueue;
use \OCA\Nextant\Items\ItemQueue;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class LiveQueueMapper extends Mapper
{

    const TABLENAME = 'nextant_live_queue';

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLENAME, 'OCA\Nextant\Db\LiveQueue');
    }

    public function find($id)
    {
        $sql = 'SELECT * FROM *PREFIX*' . self::TABLENAME . ' WHERE id = ?';
        return $this->findEntity($sql, [
            $id
        ]);
    }

    public function next($keepit = false)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*' . self::TABLENAME . ' ORDER BY id ASC LIMIT 0, 1';
            $result = $this->findEntity($sql, []);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $dnee) {
            return false;
        }
        
        if (! $keepit) {
            $this->delete($result);
        }
        
        return $result;
    }

    public function clear()
    {
        $sql = 'TRUNCATE *PREFIX*' . self::TABLENAME;
        return $this->execute($sql);
    }
}

