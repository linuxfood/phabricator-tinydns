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

final class TinydnsQueryDomainConduitAPIMethod
    extends TinydnsConduitAPIMethod {

    public function getAPIMethodName() {
        return 'tinydns.querydomain';
    }

    public function getMethodDescription() {
        return pht(
            'Query for domains the current user can see and optionally edit. '.
            'by domain names, PHIDs, or with a limit and offset for all '.
            'domains the current user can see and/or edit.');
    }

    protected function defineParamTypes() {
        return array(
            'names' => 'optional array<string>',
            'phids' => 'optional array<phids>',
            'requireEdit' => 'optional bool',
            'needRecords' => 'optional bool',
            'limit' => 'optional int',
            'offset' => 'optional int',
        );
    }

    protected function defineReturnType() {
        return 'nonempty dict';
    }

    protected function execute(ConduitAPIRequest $request) {
        $user = $request->getUser();
        $names = $request->getValue('names', array());
        $phids = $request->getValue('phids', array());
        $requireEdit = $request->getValue('requireEdit', false);
        $needRecords = $request->getValue('needRecords', false);
        $limit = $request->getValue('limit');
        $offset = $request->getValue('offset');

        $caps = array(PhabricatorPolicyCapability::CAN_VIEW);
        $domains = array();

        if ($requireEdit) {
            $caps[] = PhabricatorPolicyCapability::CAN_EDIT;
        }

        $query = id(new TinydnsDomainQuery())
            ->setViewer($user)
            ->needRecords($needRecords)
            ->requireCapabilities($caps)
            ->setLimit($limit)
            ->setOffset($offset);

        if ($names) {
            $domains = $query->withDomains($names)->execute();
        } else if ($phids) {
            $domains = $query->withPHIDs($phids)->execute();
        } else {
            $domains = $query->execute();
        }

        $data = array();
        foreach ($domains as $domain) {
            $data[$domain->getPHID()] = array(
                'domainURI' => $domain->getURI(),
                'domainRoot' => $domain->getDomainRoot(),
                'domainTTL' => $domain->getTtl(),
            );
            if ($needRecords) {
                $records = array();
                foreach ($domain->getRecords() as $record) {
                    $records[] = array(
                        'fqdn' => $record->getFqdn(),
                        'ttl' => $record->getTtl(),
                        'type' => $record->getRecordType(),
                        'data' => $record->getData(),
                        'data2' => $record->getData2()
                    );
                }
                $data[$domain->getPHID()]['domainRecords'] = $records;
            }
        }
        return $data;
    }
}
