<?php
/*
   Copyright 2015 Brian Smith <brian@linuxfood.net>

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

final class TinydnsDomainPHIDType extends PhabricatorPHIDType {
    const TYPECONST = 'ZONE';

    public function getTypeName() {
        return pht('TinyDNS Domain');
    }

    public function getPHIDTypeApplicationClass() {
        return 'TinydnsApplication';
    }

    public function getTypeIcon() {
        return 'fa-sitemap bluegrey';
    }

    public function newObject() {
        return new TinydnsDomain();
    }

    public function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids) {
        return id(new TinydnsDomainQuery())
            ->withPHIDs($phids);
    }

    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects) {
        
        foreach ($handles as $phid => $handle) {
            $domain = $objects[$phid];
            $handle->setName($domain->getDomainRoot());
            $handle->setURI($domain->getURI());
        }
    }

    public function canLoadNamedObject($name) {
        return preg_match('/^\*[^\s,*]+$/i', $name);
    }

    public function loadNamedObjects(
        PhabricatorObjectQuery $query,
        array $names) {

        $d = array();
        foreach ($names as $name) {
            $d[substr($name, 1)] = 1;
        }

        $domains = id(new TinydnsDomainQuery())
            ->setViewer($query->getViewer())
            ->withDomains(array_keys($d))
            ->execute();

        $result = array();
        foreach ($domains as $domain) {
            $result[$domain->getDomainRoot()] = $domain;
        }
        return $result;
    }
}
