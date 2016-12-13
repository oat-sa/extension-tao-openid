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

    private $consumerService;

    public function __construct(array $options)
    {
        parent::__construct($options);

        if (isset($options['consumerService']) && $options['consumerService'] instanceof ConsumerService) {
            $this->consumerService = $options['consumerService'];
        } else {
            $this->consumerService = ConsumerService::singleton();
        }
    }

    /**
     * @param Token $token
     * @param null $time - Current time() (if you use TZ, set $time in current TZ)
     * # common_session_SessionManager::getSession()->getTimezone(), new \DateTimeZone($timeZone)
     * # (new \DateTime('now', new \DateTimeZone($timeZone)))->getTimestamp()
     *
     * @return ValidationData | false
     */
    public function validator(Token $token, $time=null)
    {
        $validator = false;
        $token->getHeaders(); // Retrieves the token header
        $token->getClaims(); // Retrieves the token claims

        $iss = $token->getClaim('iss');

        $config = $this->consumerService->getConfiguration($iss);
        if (count($config)) {

            // It will use the current time to validate (iat, nbf and exp)
            $validator = new ValidationData($time);

            $audience = $token->getClaim('aud');
            $id = $token->getHeader('jti');

            $validator->setIssuer($iss);
            $validator->setAudience($audience);
            $validator->setId($id);
        }
        return $validator;
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function validate(Token $token, ValidationData $validator = null)
    {
        if (!$validator) {
            $validator = $this->validator($token);
        }

        if ($validator == false) {
            return false;
        }

        return $token->validate($validator);
    }
}
