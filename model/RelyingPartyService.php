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
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token as JWTToken;
use Lcobucci\JWT\ValidationData;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\mvc\DefaultUrlService;

class RelyingPartyService extends ConfigurableService
{
    const SERVICE_ID = 'taoOpenId/RP';
    /** @var ConsumerService */
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
     * @param JWTToken $token
     * @param null $time - Current time() (if you use TZ, set $time in current TZ)
     * # common_session_SessionManager::getSession()->getTimezone(), new \DateTimeZone($timeZone)
     * # (new \DateTime('now', new \DateTimeZone($timeZone)))->getTimestamp()
     *
     * @return ValidationData | false
     */
    public function validator(JWTToken $token, $time = null)
    {
        $validator = false;
        $token->getHeaders(); // Retrieves the token header
        $token->getClaims(); // Retrieves the token claims

        $config = $this->getConfig($token);
        if (count($config)) {

            // It will use the current time to validate (iat, nbf and exp)
            $validator = new ValidationData($time);

            $iss = $token->getClaim('iss');
            $audience = $token->getClaim('aud');
            $subject = $token->getClaim('sub');

            $id = '';
            if ($token->hasHeader('jti')) {
                $id = $token->getHeader('jti');
            } elseif ($token->hasHeader('kid')) {
                $id = $token->getHeader('kid');
            }

            $validator->setIssuer($iss);
            $validator->setAudience($audience);
            $validator->setId($id);
            $validator->setSubject($subject);
        } else {
            \common_Logger::e('OpenId consumer '.$token->getClaim('iss').' wasn\'t configured');
        }

        return $validator;
    }

    /**
     * @param JWTToken $token
     * @return bool
     */
    public function validate(JWTToken $token, ValidationData $validator = null)
    {
        if (!$validator) {
            $validator = $this->validator($token);
        }

        if ($validator == false) {
            return false;
        }

        return $this->verifySign($token) && $token->validate($validator);
    }

    private function verifySign(JWTToken $token)
    {

        $config = $this->getConfig($token);

        $verified = true;
        if (isset($config[ConsumerService::PROPERTY_ENCRYPTION])) {
            switch ($config[ConsumerService::PROPERTY_ENCRYPTION]) {
                case ConsumerService::PROPERTY_ENCRYPTION_TYPE_RSA:
                    $signer = new Sha256();
                    $verified = $token->verify(
                        $signer,
                        new Key($config[ConsumerService::PROPERTY_SECRET])
                    );
                    break;
                case ConsumerService::PROPERTY_ENCRYPTION_TYPE_NULL:
                    // without signatures
                    break;
                default:
                    throw new InvalidConsumerConfigException('Undefined type of the signature '. $config[ConsumerService::PROPERTY_ENCRYPTION]);
            }
        }

        return $verified;
    }

    public function parse($token = '')
    {
        return (new Parser())->parse((string)$token);
    }

    /**
     * @param JWTToken $token
     * @return null|\string
     * @throws \oat\oatbox\service\ServiceNotFoundException
     * @throws \common_Exception
     * @throws \OutOfBoundsException
     */
    public function delegateControl(JWTToken $token)
    {
        $uri = null;
        $config = $this->getConfig($token);
        $entryPointId = $config[ConsumerService::PROPERTY_ENTRY_POINT];
        /** @var DefaultUrlService $urlService */
        $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
        if ($entryPointId) {
            $session = $this->getServiceManager()->get(SessionService::SERVICE_ID)->create($entryPointId,
                ['token' => $token]);
            $uri = $urlService->getUrl($entryPointId);
        }
        return $uri;
    }

    private function getConfig(JWTToken $token)
    {
        $iss = $token->getClaim('iss');
        $kid = $token->hasHeader('kid') ? $token->getHeader('kid') : '';

        return $this->consumerService->getConfiguration($iss, $kid);
    }

    /**
     * @return $this|ConsumerService
     */
    public function getConsumerService()
    {
        return $this->consumerService;
    }


}
