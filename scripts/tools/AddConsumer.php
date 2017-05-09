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
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoOpenId\scripts\tools;

use common_report_Report;
use oat\oatbox\action\Action;
use oat\taoNccer\model\session\Generator;
use oat\taoOpenId\model\ConsumerService;

/**
 * Class AddConsumer
 * Usage:
 * php index.php 'oat\taoOpenId\scripts\tools\AddConsumer' LABEL ISS KEY /path/to/secret
 * Example:
 * php index.php 'oat\taoOpenId\scripts\tools\AddConsumer' NCCER2 https://is3dev.nccer.org/identity a3rMUgMFv9tPclLa6yF3zAkfquE secret.sc
 * @package oat\taoOpenId\scripts\tools
 */
class AddConsumer implements Action
{
    /**
     * @return ConsumerService
     */
    protected function getConsumerService()
    {
        return ConsumerService::singleton();
    }

    public function __invoke($params)
    {
        if (count($params) < 4) {
            return new common_report_Report(common_report_Report::TYPE_ERROR,
                "USAGE: php index.php 'oat\\taoOpenId\\scripts\\tools\\AddConsumer' LABEL ISS KEY /path/to/secret"
            );
        }

        if (!is_file($params[3]) || !is_readable($params[3])) {
            return new common_report_Report(common_report_Report::TYPE_ERROR,
                "USAGE: php index.php 'oat\\taoOpenId\\scripts\\tools\\AddConsumer' LABEL ISS KEY /path/to/secret"
            );
        }

        $this->getConsumerService()->getRootClass()->createInstanceWithProperties([
            RDFS_LABEL => 'NCCER 2',
            ConsumerService::PROPERTY_ENTRY_POINT => Generator::entryPointId,
            ConsumerService::PROPERTY_ENCRYPTION => ConsumerService::PROPERTY_ENCRYPTION_TYPE_RSA,
            ConsumerService::PROPERTY_ISS => $params[1],
            ConsumerService::PROPERTY_KEY => $params[2],
            ConsumerService::PROPERTY_SECRET => file_get_contents($params[3])
        ]);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS,
            "Create new consumer with label: " . $params[0]
        );
    }
}
