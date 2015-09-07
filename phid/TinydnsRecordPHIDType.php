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

final class TinydnsRecordPHIDType extends PhabricatorPHIDType {
    const TYPECONST = 'ZREC';

    public function getTypeName() {
        return pht('TinyDNS Record');
    }

    public function getPHIDTypeApplicationClass() {
        return 'TinydnsApplication';
    }

    public function getTypeIcon() {
        return 'fa-globe bluegrey';
    }

    public function newObject() {
        return new TinydnsRecord();
    }

    public function buildQueryForObjects(PhabricatorObjectQuery $query, array $phids) {
        return id(new TinydnsRecordQuery())
            ->withPHIDs($phids);

    }

    public function loadHandles(
        PhabricatorHandleQuery $query,
        array $handles,
        array $objects) {
        
        foreach ($handles as $phid => $handle) {
            $record = $objects[$phid];
            $handle->setName($record->getName());
            $handle->setURI($record->getDomain()->getURI());
        }
    }

    public function canLoadNamedObject($name) {
        return false;
    }
}
