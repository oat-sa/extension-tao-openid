<?php
/**
 * This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2017 (original work) Open Assessment Technologies SA
 */
namespace oat\taoOpenId\model;

use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\user\User as UserInterface;
use oat\tao\model\user\TaoRoles;

class User implements UserInterface
{
    use OntologyAwareTrait;

    /** @var array */
    protected $roles = [];

    /** @var Token */
    protected $token;

    const ROLE_INCOMER = TaoRoles::ANONYMOUS;

    /** @var  string */
    protected $id;

    public function __construct(Token $token)
    {
        $this->setToken($token);
        $this->id = $this->getToken()->getId();
    }

    /**
     * Returns internal identifier of the user
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * Extends the users explizit roles with the implizit rules
     * of the local system
     *
     * @return array the identifiers of the roles:
     */
    public function getRoles()
    {
        $roles = [static::ROLE_INCOMER];

        if (!$this->roles) {
            $this->roles = $this->flattenRoles($roles);
        }

        return $this->roles;
    }

    /**
     * Retrieve custom attributes of a user
     *
     * @param $property
     * @return array an array of strings
     */
    public function getPropertyValues($property)
    {
        $returnValue = null;
        switch ($property) {
            case GenerisRdf::PROPERTY_USER_DEFLG :
            case GenerisRdf::PROPERTY_USER_UILG :
                $returnValue = [$this->getLanguage()];
                break;
            case GenerisRdf::PROPERTY_USER_ROLES :
                $returnValue = $this->getRoles();
                break;
            case GenerisRdf::PROPERTY_USER_FIRSTNAME :
                $returnValue = [$this->getToken()->getFirstName()];
                break;
            case GenerisRdf::PROPERTY_USER_LASTNAME :
                $returnValue = [$this->getToken()->getLastName()];
                break;
            default:
                \common_Logger::d('Unknown property ' . $property . ' requested from ' . __CLASS__);
                $returnValue = [];
        }
        return $returnValue;
    }

    /**
     * @return Token
     */
    protected function getToken()
    {
        return $this->token;
    }

    /**
     * @param Token $token
     */
    protected function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param $roles
     * @return array
     * @throws \core_kernel_users_Exception
     * @throws \core_kernel_users_CacheException
     */
    protected function flattenRoles(array $roles)
    {
        $returnValue = [];
        foreach ($roles as $roleUri) {
            $returnValue[] = $roleUri;
            foreach (\core_kernel_users_Service::singleton()->getIncludedRoles($this->getResource($roleUri)) as $role) {
                $returnValue[] = $role->getUri();
            }
        }
        return array_unique($returnValue);
    }

    protected function getLanguage()
    {
        return DEFAULT_LANG;
    }


}