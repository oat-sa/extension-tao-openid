<?php
/**
 * This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2017 (original work) Open Assessment Technologies SA
 */
namespace oat\taoOpenId\model;

use Lcobucci\JWT\Token as RawToken;

class Token
{
    /**
     * @var RawToken $token
     */
    protected $rawToken;

    public function __construct(RawToken $token)
    {
        $this->ingest($token);
    }

    /**
     * @param RawToken $token
     */
    public function ingest(RawToken $token)
    {
        $this->rawToken = $token;
    }

    public function getId()
    {
        return $this->rawToken->getClaim('sub');
    }


    /**
     * @return string
     * @throws \OutOfBoundsException
     */
    public function getFullName()
    {
        return $this->rawToken->getClaim('given_name') . ' ' . $this->rawToken->getClaim('family_name');
    }
}