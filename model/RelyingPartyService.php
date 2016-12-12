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

namespace oat\taoOpenId\model;


use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use oat\oatbox\service\ConfigurableService;

class RelyingPartyService extends ConfigurableService
{
    const SERVICE_ID = 'taoOpenId/RP';

    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function validator()
    {}

    /**
     * @param Token $token
     * @return bool
     */
    public function validate(Token $token)
    {
        $token->getHeaders(); // Retrieves the token header
        $token->getClaims(); // Retrieves the token claims
        $iss = $token->getClaim('iss');

        // todo get configuration for this $iss
        //getProperty();

        // todo set current time (in the current TZ?)
        $validator = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
        // todo set properties from the saved data
        $validator->setIssuer($iss);
        $validator->setAudience('http://example.org');
        $validator->setId('4f1g23a12aa');

        return $token->validate($validator);
    }
}
