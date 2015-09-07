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

final class TinydnsRecord extends TinydnsDAO
    implements PhabricatorPolicyInterface {

    // These are from http://cr.yp.to/djbdns/tinydns-data.html
    const ALIAS_RECORD = '+';
    const A_RECORD = '=';
    const CNAME_RECORD = 'C';
    const MX_RECORD = '@';
    const NS_RECORD = '&';
    const PTR_RECORD = '^';
    const RAW_RECORD = ':';
    const TXT_RECORD = "'";

    protected $domainPHID;
    protected $recordType;
    protected $ttl;
    protected $fqdn;
    protected $data;
    protected $data2;

    private $domain = self::ATTACHABLE;

    public function getConfiguration() {
        return array(
            self::CONFIG_AUX_PHID => true,
            self::CONFIG_COLUMN_SCHEMA => array(
                'recordType' => 'text4',
                'ttl' => 'int32',
                'fqdn' => 'sort255',
                'data' => 'text',
                'data2' => 'text?',
            ),
            self::CONFIG_KEY_SCHEMA => array(
                'fqdn' => array(
                    'columns' => array('fqdn'),
                ),
                'domainPHID' => array(
                    'columns' => array('domainPHID'),
                ),
            ),
        ) + parent::getConfiguration();
    }

    public function generatePHID() {
        return PhabricatorPHID::generateNewPHID(TinydnsRecordPHIDType::TYPECONST);
    }

    public function attachDomain(TinydnsDomain $domain) {
        assert($domain->getPHID() === $this->domainPHID,
            "Attaching domain that doesn't match the domain of this record.");
        $this->domain = $domain;
        return $this;
    }

    public function getDomain() {
        return $this->assertAttached($this->domain);
    }

    public function getCapabilities() {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    public function getPolicy($capability) {
        return $this->getDomain()->getPolicy($capability);
    }

    public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
        return $this->getDomain()->hasAutomaticCapability(
            $capability,
            $viewer);
    }

    public function describeAutomaticCapability($capability) {
        return pht('Users must be able to see a domain to see its records.');
    }

    static public function recordTypeName($recordType) {
        switch ($recordType) {
        case TinydnsRecord::A_RECORD:
            $type = 'A';
            break;
        case TinydnsRecord::ALIAS_RECORD:
            $type = 'Alias';
            break;
        case TinydnsRecord::CNAME_RECORD:
            $type = 'CNAME';
            break;
        case TinydnsRecord::MX_RECORD:
            $type = 'Mail';
            break;
        case TinydnsRecord::NS_RECORD:
            $type = 'NS';
            break;
        case TinydnsRecord::PTR_RECORD:
            $type = 'PTR';
            break;
        case TinydnsRecord::RAW_RECORD:
            $type = 'Raw';
            break;
        case TinydnsRecord::TXT_RECORD:
            $type = 'TXT';
            break;
        }
        return $type;
    }

    public function getFormControls() {
        $columns = array();
        $rid = 'records[new]';
        if ($this->getID()) {
            $rid = "records[{$this->getID()}]";
        }

        $columns['FQDN'] = id(new AphrontFormTextControl())
            ->setLabel('FQDN')
            ->setName("$rid". "[fqdn]")
            ->setValue($this->getFqdn());

        switch ($this->getRecordType()) {
        case TinydnsRecord::A_RECORD:
        case TinydnsRecord::ALIAS_RECORD:
            $columns['IP'] = id(new AphrontFormTextControl())
                ->setLabel('IP')
                ->setName("$rid". "[data]")
                ->setValue($this->getData());
            break;
        case TinydnsRecord::CNAME_RECORD:
            $columns['Target'] = id(new AphrontFormTextControl())
                ->setLabel('Target')
                ->setName("$rid". "[data]")
                ->setValue($this->getData());
            break;
        case TinydnsRecord::MX_RECORD:
            $columns['MX'] = id(new AphrontFormTextControl())
                ->setLabel('MX')
                ->setName("$rid". "[data]")
                ->setValue($this->getData());
            $columns['Distance'] = id(new AphrontFormTextControl())
                ->setLabel('Distance')
                ->setName("$rid". "[data2]")
                ->setValue($this->getData2());
            break;
        case TinydnsRecord::RAW_RECORD:
            $columns['DataType'] = id(new AphrontFormTextControl())
                ->setLabel('Raw Type')
                ->setName("$rid". "[data]")
                ->setValue($this->getData());
            $columns['EncValue'] = id(new AphrontFormTextControl())
                ->setLabel('Encoded Value')
                ->setName("$rid" . "[data2]")
                ->setValue($this->getData2());
            break;
        }
        $columns['TTL'] = id(new AphrontFormTextControl())
            ->setLabel('TTL')
            ->setName("$rid". "[ttl]")
            ->setValue($this->getTtl());

        return $columns;
    }

}
