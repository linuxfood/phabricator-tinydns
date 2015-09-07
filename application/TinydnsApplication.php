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

final class TinydnsApplication extends PhabricatorApplication {

    public function getBaseURI() {
        return '/tinydns/';
    }

    public function getName() {
        return pht('TinyDNS');
    }

    public function getShortDescription() {
        return pht('Manage domain records and put them into TinyDNS format.');
    }

    public function isPrototype() {
        return true;
    }

    public function getApplicationGroup() {
        return self::GROUP_UTILITIES;
    }

    public function getRoutes() {
        $domainCapture = '(?P<domainName>[^/]+)';
        return array(
            '/tinydns/' => array(
                "(?:query/(?P<queryKey>[^/]+)/)?" => 'TinydnsController',
                "domain/$domainCapture/?" => 'TinydnsDomainViewController',
                "domain/$domainCapture/edit/" => 'TinydnsDomainEditController',
                "domain/$domainCapture/raw/" => 'TinydnsRawViewController',
                "record/create/" => 'TinydnsRecordCreateController',
                "record/(?P<recordID>[0-9]+)/delete/" => 'TinydnsRecordDeleteController',
            ),
        );
    }

    protected function getCustomCapabilities() {
        return array(
            TinydnsManageDomainsCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }

    public function getApplicationSearchDocumentTypes() {
        return array(
            TinydnsDomainPHIDType::TYPECONST,
            TinydnsRecordPHIDType::TYPECONST,
        );
    }
}
