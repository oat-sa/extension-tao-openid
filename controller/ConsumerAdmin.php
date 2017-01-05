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


use Exception;
use oat\taoOpenId\controller\form\AddConsumer;
use oat\taoOpenId\controller\form\EditConsumer;
use oat\taoOpenId\model\ConsumerService;
use tao_actions_SaSModule;
use tao_helpers_Request;
use tao_models_classes_dataBinding_GenerisFormDataBinder;

class ConsumerAdmin extends tao_actions_SaSModule
{
    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
    }

    public function getClassService()
    {
        return ConsumerService::singleton();
    }

    public function editInstance()
    {
        $clazz = $this->getCurrentClass();
        $instance = $this->getCurrentInstance();
        $myFormContainer = new EditConsumer($clazz, $instance);

        $myForm = $myFormContainer->getForm();
        if ($myForm->isSubmited()) {
            if ($myForm->isValid()) {

                $values = $myForm->getValues();
                // save properties
                $binder = new tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
                $instance = $binder->bind($values);
                $message = __('Instance saved');

                $this->setData('message', $message);
                $this->setData('reload', true);
            }
        }

        $this->setData('formTitle', __('Edit Instance'));
        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }


    /**
     * Add an instance of the selected class
     * @return void
     * @throws Exception
     */
    public function addInstanceForm()
    {
        if (!tao_helpers_Request::isAjax()) {
            throw new Exception('wrong request mode');
        }

        $clazz = $this->getCurrentClass();
        $formContainer = new AddConsumer(array($clazz), array());
        $myForm = $formContainer->getForm();

        if ($myForm->isSubmited()) {
            if ($myForm->isValid()) {
                $properties = $myForm->getValues();
                $instance = $this->createInstance(array($clazz), $properties);

                $this->setData('message', __($instance->getLabel() . ' created'));
                $this->setData('reload', true);
            }
        }

        $this->setData('formTitle', __('Create instance of ') . $clazz->getLabel());
        $this->setData('myForm', $myForm->render());

        $this->setView('form.tpl', 'tao');
    }

}
