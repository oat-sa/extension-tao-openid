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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *               
 * 
 */
use oat\taoOpenId\scripts\update\Updater;

/**
 * Generated using taoDevTools 2.17.0
 */
return array(
    'name' => 'taoOpenId',
    'label' => 'Open ID library',
    'description' => 'TAO Open ID library and helpers',
    'license' => 'GPL-2.0',
    'version' => '1.2.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'generis' => '>=5.9.0',
        'tao' => '>=10.4.0'
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoOpenIdManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoOpenIdManager', array('ext'=>'taoOpenId')),
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole', array('ext'=>'taoOpenId','mod' => 'Connect', 'act' => 'callback')),
    ),
    'install' => array(
        'php' => [
            \oat\taoOpenId\scripts\install\RelyingPartyServiceRegister::class,
            \oat\taoOpenId\scripts\install\SetUpEntryOpenIdEntryPoint::class,
        ],
        'rdf' => [
            __DIR__ . '/model/ontology/openid.rdf'
        ]
    ),
    'uninstall' => array(
    ),
    'routes' => array(
        'taoOpenId' => 'oat\\taoOpenId\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => dirname(__FILE__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,
        
        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'taoOpenId/',
    ),
    'update'    => Updater::class,
    'extra' => array(
        'structures' => dirname(__FILE__).DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    )
);
