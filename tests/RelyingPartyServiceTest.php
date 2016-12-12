<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoOpenId\tests;


use Lcobucci\JWT\Builder;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoOpenId\model\RelyingPartyService;


class RelyingPartyServiceTest extends TaoPhpUnitTestRunner
{
    /**
     * @var RelyingPartyService
     */
    private $service;

    public function setUp()
    {
        parent::setUp();
        $this->service = new RelyingPartyService([]);
    }

    public function testValidator()
    {
        $token = (new Builder())->issuedBy('http://example.com')// Configures the issuer (iss claim)
            ->canOnlyBeUsedBy('http://example.org')// Configures the audience (aud claim)
            ->identifiedBy('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->issuedAt(time())// Configures the time that the token was issue (iat claim)
            ->canOnlyBeUsedAfter(time() + 60)// Configures the time that the token can be used (nbf claim)
            ->expiresAt(time() + 3600)// Configures the expiration time of the token (nbf claim)
            ->with('uid', 1)// Configures a new claim, called "uid"
            ->getToken(); // Retrieves the generated token

        $this->assertFalse($this->service->validate($token));
    }
}
