<?php

namespace oat\taoOpenId\controller\form;

use oat\taoOpenId\model\ConsumerService;
use tao_actions_form_Instance;
use tao_helpers_Uri;

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
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */
class EditConsumer extends tao_actions_form_Instance
{
    protected function initElements()
    {
        parent::initElements();

        $entryPointElementUri = tao_helpers_Uri::encode(ConsumerService::PROPERTY_ENTRY_POINT);
        $entryPointElement = $this->form->getElement($entryPointElementUri);
        $entryPointElement->setEmptyOption(false);
        if (null !== $entryPointElement) {
            $entryPointElement->setOptions($this->getOpenIdEntryPointsHandler());
        }
    }

    /**
     * @return array
     */
    private function getOpenIdEntryPointsHandler()
    {
        $controllers = ConsumerService::singleton()->getAvailableEntryPoints();
        return array_combine($controllers, $controllers);
    }


}