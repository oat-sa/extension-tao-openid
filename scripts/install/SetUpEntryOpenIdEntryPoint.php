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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */


namespace oat\taoOpenId\scripts\install;


use oat\tao\model\mvc\DefaultUrlService;
use oat\taoOpenId\model\ConsumerService;
use oat\taoOpenId\model\session\Generator;
use oat\taoOpenId\model\SessionService;

class SetUpEntryOpenIdEntryPoint extends \common_ext_action_InstallAction
{

    public function __invoke($params)
    {
        $this->getServiceManager()->register(SessionService::SERVICE_ID, new SessionService([
            Generator::entryPointId => Generator::class
        ]));

        $UrlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);

        $UrlService->setOption(Generator::entryPointId,
            [
                'ext' => 'tao',
                'controller' => 'Main',
                'action' => 'entry',
                'context' => ConsumerService::urlContext
            ]
        );
        $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $UrlService);
    }

}
