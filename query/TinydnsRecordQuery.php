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

final class TinydnsRecordQuery extends PhabricatorCursorPagedPolicyAwareQuery {
    private $domains;
    private $ids;

    public function getQueryApplicationClass() {
        return 'TinydnsApplication';
    }

    public function withIDs(array $ids) {
        $this->ids = $ids;
        return $this;
    }

    public function withDomains($domains) {
        assert_instances_of($domains, 'TinydnsDomain');
        $this->domains = $domains;
        return $this;
    }

    public function newResultObject() {
        return new TinydnsRecord();
    }

    protected function getPrimaryTableAlias() {
        return 'records';
    }

    public function getBuiltinOrders() {
        return array(
            'fqdn' => array(
                'vector' => array('fqdn', 'id'),
                'name' => pht('FQDN'),
            ),
        ) + parent::getBuiltinOrders();
    }

    public function getOrderableColumns() {
        $orders = parent::getOrderableColumns() + array(
            'fqdn' => array(
                'table' => $this->getPrimaryTableAlias(),
                'column' => 'fqdn',
                'reverse' => true,
                'type' => 'string',
            ),
        );
        return $orders;
    }

    protected function loadPage() {
        $dao = new TinydnsRecord();
        $data = $this->loadStandardPageRows($dao);
        $records = $dao->loadAllFromArray($data);

        $domains_by_phid = array();
        if ($this->domains) {
            $domains_by_phid = mgroup($this->domains, 'getPHID');
        } else {
            $selected_domains = mgroup($records, 'getDomainPHID');
            $domains = id(new TinydnsDomainQuery())
                ->needRecords(false)
                ->setViewer($this->getViewer())
                ->withPHIDs(array_keys($selected_domains))
                ->execute();
            $domains_by_phid = mgroup($domains, 'getPHID');
        }

        foreach($records as $record) {
            $record->attachDomain(head($domains_by_phid[$record->getDomainPHID()]));
        }

        return $records;
    }

    protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
        $where = parent::buildWhereClauseParts($conn);

        if ($this->domains !== null) {
            $where[] = qsprintf($conn, 'domains.phid IN (%Ls)', mpull($this->domains, 'getPHID'));
        }
        if ($this->ids !== null) {
            $where[] = qsprintf($conn, 'records.id IN (%Ld)', $this->ids);
        }
        return $where;
    }

    protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
        $joins = parent::buildJoinClauseParts($conn);
        if ($this->domains) {
            $joins[] = qsprintf(
                $conn,
                'JOIN %T domains ON domainPHID = domains.phid',
                id(new TinydnsDomain())->getTableName());
        }
        return $joins;
    }
}
