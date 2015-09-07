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

final class TinydnsDomainQuery extends PhabricatorCursorPagedPolicyAwareQuery {

    private $ids;
    private $domainPHIDs;
    private $domains;

    private $needRecords;

    public function getQueryApplicationClass() {
        return 'TinydnsApplication';
    }

    public function withIDs(array $ids) {
        $this->ids = $ids;
        return $this;
    }

    public function withPHIDs(array $phids) {
        $this->domainPHIDs = $phids;
        return $this;
    }

    public function withDomains(array $domains) {
        $this->domains = $domains;
        return $this;
    }

    public function needRecords($need_records) {
        $this->needRecords = $need_records;
        return $this;
    }

    public function newResultObject() {
        return new TinydnsDomain();
    }

    public function getDefaultOrderVector() {
        return array('domainRoot');
    }

    public function getBuiltinOrders() {
        return array(
            'domainRoot' => array(
                'vector' => array('domainRoot'),
                'name' => pht('Domain'),
            ),
        ) + parent::getBuiltinOrders();
    }

    public function getOrderableColumns() {
        return parent::getOrderableColumns() + array(
            'domainRoot' => array(
                'table' => $this->getPrimaryTableAlias(),
                'column' => 'domainRoot',
                'reverse' => true,
                'type' => 'string',
                'unique' => true,
            ),
        );
    }

    protected function loadPage() {
        $domain_dao = new TinydnsDomain();
        $data = $this->loadStandardPageRows($domain_dao);
        $domains = $domain_dao->loadAllFromArray($data);

        if ($this->needRecords) {
            $records = id(new TinydnsRecord())
                ->loadAllWhere('domainPHID IN (%Ls)', mpull($domains, 'getPHID'));

            $records = mgroup($records, 'getDomainPHID');
            foreach ($domains as $domain) {
                $domain_records = idx($records, $domain->getPHID(), array());
                $domain->attachRecords($domain_records);
            }
        }
        return $domains;
    }

    protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
        $where = parent::buildWhereClauseParts($conn);

        if ($this->ids !== null) {
            $where[] = qsprintf($conn, 'id IN (%Ld)', $this->ids);
        }

        if ($this->domainPHIDs !== null) {
            $where[] = qsprintf($conn, 'phid IN (%Ls)', $this->domainPHIDs);
        }

        if ($this->domains !== null) {
            $where[] = qsprintf($conn, 'domainRoot IN (%Ls)', $this->domains);
        }
        return $where;
    }

}
