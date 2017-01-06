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

namespace oat\taoOpenId\controller;


use common_Logger;
use oat\taoOpenId\model\RelyingPartyService;

class Connect extends \tao_actions_CommonModule
{

    /**
     * callback uri for getting answering from the OP
     * OP responds with an ID Token and usually an Access Token.
     * RP can send a request with the Access Token to the UserInfo Endpoint.
     * UserInfo Endpoint returns Claims about the End-User.
     * @throws \common_exception_BadRequest
     * @throws \OutOfBoundsException
     * @throws \oat\taoOpenId\model\InvalidTokenException
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function callback()
    {
        /** @var  RelyingPartyService $service */
        $service = $this->getServiceManager()->get(RelyingPartyService::SERVICE_ID);
        $jwt = $this->getRequestParameter('id_token');
        // also in the request you can find scope, state and session_state
        $jwt = $service->parse($jwt);

        if ($service->validate($jwt)) {
            common_Logger::d('token validated successfully');
            $uri = $service->delegateControl($jwt);
            $this->redirect($uri);
        } else {
            http_response_code(500);
            common_Logger::d('Token validation was failed ' . $this->getRequestParameter('id_token'));
            $label = $service->getConsumerService()->getConsumerLabel($jwt, __('your system administrator'));
            $this->returnError(__('We\'ve been unable to authorize you to the Tao Platform. Please contact %s',
                $label));
        }
    }
}
