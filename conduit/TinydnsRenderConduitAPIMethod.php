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

final class TinydnsRenderConduitAPIMethod
    extends TinydnsConduitAPIMethod {

    public function getAPIMethodName() {
        return 'tinydns.render';
    }

    public function getMethodDescription() {
        return pht('Render a domain or set of domains to tinydns format.');
    }

    protected function defineParamTypes() {
        return array(
            'names' => 'optional array<string>',
            'phids' => 'optional array<phids>',
            'limit' => 'optional int',
            'offset' => 'optional int',
        );
    }

    protected function defineReturnType() {
        return 'string';
    }

    protected function execute(ConduitAPIRequest $request) {
        $user = $request->getUser();
        $names = $request->getValue('names', array());
        $phids = $request->getValue('phids', array());
        $limit = $request->getValue('limit');
        $offset = $request->getValue('offset');

        $query = id(new TinydnsDomainQuery())
            ->setViewer($user)
            ->needRecords(true)
            ->setLimit($limit)
            ->setOffset($offset);

        if ($phids) {
            $domains = $query->withPHIDs($phids)->execute();
        } else if ($names) {
            $domains = $query->withDomains($names)->execute();
        } else {
            $domains = $query->execute();
        }

        $data = array();
        foreach ($domains as $domain) {
            $data[] = id(new TinydnsRawView())
                ->setDomain($domain)
                ->setViewer($user)
                ->render();
        }
        return implode("\n", $data);
    }
}

