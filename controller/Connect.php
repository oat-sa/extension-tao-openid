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


use oat\taoOpenId\model\RelyingPartyService;

class Connect extends \tao_actions_CommonModule
{
    public function __construct()
    {

        parent::__construct();
        $this->service = $this->getServiceManager()->get(RelyingPartyService::SERVICE_ID);
    }

    /**
     * callback uri for getting answering from the OP
     * OP responds with an ID Token and usually an Access Token.
     * RP can send a request with the Access Token to the UserInfo Endpoint.
     * UserInfo Endpoint returns Claims about the End-User.
     */
    public function callback()
    {
        $jwt = $this->getRequestParameter('key');
        $jwt = $this->service->parse($jwt);

        if ($this->service->validate($jwt)) {
            // continue
            $this->returnJson(['success' => true]);
        }

        $this->returnJson(['success' => false]);

    }
}
