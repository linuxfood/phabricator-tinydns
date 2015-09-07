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

final class TinydnsPatchList extends PhabricatorSQLPatchList {
    public function getNamespace() {
        // This is 'phabricator' so that the database is created
        // with the right name: `phabricator_tinydns` instead of 
        // something else. In theory, this should be 'lfn' or something 
        // similar, but, then I'd have to jump through weird hoops to 
        // make the DAO framework do what I want.
        return 'phabricator';
    }

    public function getPatches() {
        $patches = array();

        $root = dirname(phutil_get_library_root('phabricator'));
        $auto_root = $root.'/src/applications/tinydns/resources/sql/patches/';
        $patches += $this->buildPatchesFromDirectory($auto_root);

        // This makes it so that the first patch depends on nothing,
        // and makes phabricator happy to assume that everything else
        // just depends on the thing that came before it.
        $patches[head(array_keys($patches))]['after'] = array();

        return $patches;
    }
}
