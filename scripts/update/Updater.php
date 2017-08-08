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

namespace oat\taoOpenId\scripts\update;


use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\mvc\DefaultUrlService;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoOpenId\model\ConsumerService;
use oat\taoOpenId\model\RelyingPartyService;
use oat\taoOpenId\model\session\Generator;
use oat\taoOpenId\model\SessionService;

class Updater extends \common_ext_ExtensionUpdater
{
    public function update($initialVersion)
    {
        if ($this->isVersion('0.0.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.0.2');
        }

        if ($this->isVersion('0.0.2')) {
            AclProxy::applyRule(new AccessRule('grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole', ['ext'=>'taoOpenId','mod' => 'Connect', 'act' => 'callback']));
            $this->setVersion('0.0.3');
        }
        
        if ($this->isVersion('0.0.3')) {
            OntologyUpdater::syncModels();

            $this->getServiceManager()->register(RelyingPartyService::SERVICE_ID, new RelyingPartyService([]));
            $this->getServiceManager()->register(SessionService::SERVICE_ID, new SessionService([
                Generator::entryPointId =>  Generator::class
            ]));

            $UrlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);

            $UrlService->setRoute(Generator::entryPointId,
                [
                    'ext' => 'tao',
                    'controller' => 'Main',
                    'action' => 'entry',
                    'context' => ConsumerService::urlContext
                ]
            );
            $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $UrlService);

            $this->setVersion('0.2.0');
        }
        $this->skip('0.2.0', '0.2.1');

        if ($this->isVersion('0.2.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.2.2');
        }

        if ($this->isVersion('0.2.2')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.3.0');
        }

        if ($this->isVersion('0.3.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.3.1');
        }

        $this->skip('0.3.1', '1.1.1');
    }
}
