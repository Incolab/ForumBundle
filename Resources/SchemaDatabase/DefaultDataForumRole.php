<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\ForumBundle\Resources\SchemaDatabase;

use Incolab\DBALBundle\Manager\DefaultDataManager;
use Incolab\ForumBundle\Entity\ForumRole;

/**
 * Description of CreateShema
 *
 * @author david
 */
class DefaultDataForumRole extends DefaultDataManager {

    private function nameToForumRole(string $roleName) {
        $role = new ForumRole();
        $role->setName($roleName);
        return $role;
    }

    public function addDefaultData() {
        $this->manager->persist($this->nameToForumRole("ROLE_PUBLIC"));
        $this->manager->persist($this->nameToForumRole("ROLE_USER"));
    }
}
