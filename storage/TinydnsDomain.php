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

final class TinydnsDomain extends TinydnsDAO
    implements PhabricatorPolicyInterface {

    protected $domainRoot;
    protected $ttl;
    protected $defaultRecordTTL;
    protected $ns1;
    protected $ns2;

    protected $viewPolicy;
    protected $editPolicy;

    private $records = self::ATTACHABLE;

    static public function loadOneDomain($domain, $withRecords = true) {
        return id(new TinydnsDomainQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->needRecords($withRecords)
            ->withDomains(array($domain))
            ->execute();
    }

    public function getCapabilities() {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    public function getPolicy($capability) {
        switch ($capability) {
        case PhabricatorPolicyCapability::CAN_VIEW:
            return $this->getViewPolicy();
        case PhabricatorPolicyCapability::CAN_EDIT:
            return $this->getEditPolicy();
        }
    }

    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return false;
    }

    public function describeAutomaticCapability($capability) {
        return null;
    }

    protected function getConfiguration() {
        return array(
            self::CONFIG_AUX_PHID => true,
            self::CONFIG_COLUMN_SCHEMA => array(
                'domainRoot' => 'sort128',
                'ttl' => 'uint32',
                'defaultRecordTTL' => 'uint32',
                'ns1' => 'text128',
                'ns2' => 'text128',
                'editPolicy' => 'policy',
                'viewPolicy' => 'policy',
            ),
            self::CONFIG_KEY_SCHEMA => array(
                'domainRoot' => array(
                    'columns' => array('domainRoot'),
                    'unique' => true,
                ),
            ),
        ) + parent::getConfiguration();
    }

    public function generatePHID() {
        return PhabricatorPHID::generateNewPHID(TinydnsDomainPHIDType::TYPECONST);
    }

    public function attachRecords(array $records) {
        $this->records = $records;
        foreach($this->records as $record) {
            $record->attachDomain($this);
        }
        return $this;
    }

    public function getRecords() {
        return $this->assertAttached($this->records);
    }

    public function getURI() {
        return '/tinydns/domain/'. $this->domainRoot .'/';
    }
}
