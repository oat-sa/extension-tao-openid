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

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\mvc\DefaultUrlService;

class RelyingPartyService extends ConfigurableService
{
    const SERVICE_ID = 'taoOpenId/RP';
    private $consumerService;

    public function __construct(array $options = [])
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
    public function validator(Token $token, $time = null)
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
            $subject = $token->getClaim('sub');

            $id = '';
            if ($token->hasHeader('jti')) {
                $id = $token->getHeader('jti');
            } elseif ($token->hasHeader('kid')) {
                $id = $token->getHeader('kid');
            }

            // todo I think that should be configurable fields (but I need an approve)
            $validator->setIssuer($iss);
            $validator->setAudience($audience);
            $validator->setId($id);
            $validator->setSubject($subject);
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

    public function parse($token = '')
    {
        return (new Parser())->parse((string)$token);
    }

    /**
     * @param Token $token
     * @return null|\string
     * @throws \oat\oatbox\service\ServiceNotFoundException
     * @throws \common_Exception
     * @throws \OutOfBoundsException
     */
    public function delegateControl(Token $token)
    {
        $uri = null;
        $iss = $token->getClaim('iss');
        $config = $this->consumerService->getConfiguration($iss);
        $entryPointId = $config[ConsumerService::PROPERTY_ENTRY_POINT];
        /** @var DefaultUrlService $urlService */
        $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
        if ($entryPointId) {
            $session = $this->getServiceManager()->get(SessionService::SERVICE_ID)->create($entryPointId, ['token'=>$token]);
            $uri = $urlService->getUrl($entryPointId);
        }
        return $uri;
    }

}
