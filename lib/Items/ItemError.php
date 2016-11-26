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
namespace OCA\Nextant\Items;

class ItemError
{

    const EXCEPTION_DECRYPTION_FAILED = 1046;

    const LINK_EXCEPTION_DECRYPTION_FAILED = 'https://help.nextcloud.com/t/nextant-regarding-encrypted-files/5422';

    const EXCEPTION_DECRYPT_PRIVATEKEY_MISSING = 1050;

    const LINK_EXCEPTION_DECRYPT_PRIVATEKEY_MISSING = 'https://help.nextcloud.com/t/nextant-regarding-encrypted-files/5422';

    const EXCEPTION_INDEXDOCUMENT_WITHOUT_ABSOLUTEPATH = 2234;

    const ERROR_MANAGING_FAILURE = 1129;

    const ERROR_SOLR_NOT_FULLY_CONFIGURED = 9938;

    const ERROR_GET_SOLARIUM_CLIENT = 9939;

    private $errorcode;

    private $message;

    private $link = '';

    public function __construct($code = 0, $msg = '')
    {
        $this->errorcode = $code;
        $this->message = $msg;
    }

    public function getCode()
    {
        return $this->errorcode;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function link($link)
    {
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }
}