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


use common_Logger;
use tao_models_classes_ClassService;

class ConsumerService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOOpenId.rdf#OpenIdConsumer';
    const PROPERTY_ISS = 'http://www.tao.lu/Ontologies/TAOOpenId.rdf#OpenIdIss';
    const PROPERTY_KEY = 'http://www.tao.lu/Ontologies/TAOOpenId.rdf#OpenIdClientKey';
    const PROPERTY_SECRET = 'http://www.tao.lu/Ontologies/TAOOpenId.rdf#OpenIdSecret';

    public function getRootClass()
    {
        return new \core_kernel_classes_Class(self::CLASS_URI);
    }

    /**
     * @param string $iss (Issuer Identifier for the OP that the RP is to send the Authentication Request to.)
     *  # @see http://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
     *
     * @return array - client_id and client_secret properties for the iss
     */
    public function getConfiguration($iss = '')
    {
        $config = [];
        $instances = $this->getRootClass()->searchInstances([
            self::PROPERTY_ISS => $iss
        ],[
            'recursive' => false,
            'like' => false,
        ]);

        if (count($instances)) {
            if (count($instances) > 1) {
                common_Logger::e('For the iss ' . $iss . ' exists too many configurations');
            }

            /** @var \core_kernel_classes_Resource $instance */
            $instance = current($instances);

            $res = $instance->getPropertiesValues([
                self::PROPERTY_KEY,
                self::PROPERTY_SECRET,
            ]);

            $config = [
                self::PROPERTY_KEY => $res[self::PROPERTY_KEY][0]->literal,
                self::PROPERTY_SECRET => $res[self::PROPERTY_SECRET][0]->literal,
            ];
        }

        return $config;
    }
}
