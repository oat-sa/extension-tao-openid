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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */


namespace oat\taoOpenId\model;

use oat\taoOpenId\model\session\Generator;
use oat\taoOpenId\model\session\OpenIdAwareSessionInterface;
use common_session_SessionManager;
use oat\oatbox\service\ConfigurableService;

class SessionService extends ConfigurableService
{
    const SERVICE_ID = 'taoOpenId/session';


    /**
     * @param string|null $name
     * @param array $params
     * @return \common_session_Session
     * @throws \common_exception_InconsistentData
     */
    public function create($name = null, array $params = [])
    {
        if ($this->hasOption($name)) {
            /** @var Generator $implementation */
            $implementation = $this->getOption($name);
        }else{
            throw new \common_exception_InconsistentData('Missing configuration option ' , $name);
        }
        $session = (new $implementation)->createFrom($params);

        common_session_SessionManager::startSession($session);


        return $session;
    }

    /***
     * @return \Lcobucci\JWT\Token|null
     * @throws \common_exception_Error
     */
    public function retrieveSessionToken()
    {
        $session = common_session_SessionManager::getSession();
        if ($session instanceof OpenIdAwareSessionInterface) {
            return $session->getToken();
        }
        return null;
    }
}