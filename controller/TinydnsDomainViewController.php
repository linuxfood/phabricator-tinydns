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

final class TinydnsDomainViewController extends TinydnsBaseController {

    public function handleRequest(AphrontRequest $request) {
        $domain = $this->getDomain();
        if (!$domain) {
            return new Aphront404Response();
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            $domain->getDomainRoot(),
            $this->getApplicationURI('domain/'. $domain->getDomainRoot()));

        $boxes = array();
        $content = new PHUIObjectBoxView();
        $header = new PHUIHeaderView();
        $header->setHeader($domain->getDomainRoot());
        $header->setUser($request->getViewer());
        $header->setPolicyObject($domain);
        $boxes[] = $crumbs;
        $boxes[] = $content;

        $actions = id(new PhabricatorActionListView())
            ->setObject($domain)
            ->setObjectURI($request->getRequestURI())
            ->setUser($request->getViewer());

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $request->getViewer(),
            $domain,
            PhabricatorPolicyCapability::CAN_EDIT);

        $actions->addAction(
            id(new PhabricatorActionView())
                ->setIcon('fa-pencil')
                ->setName(pht('Edit Domain'))
                ->setHref($this->getApplicationURI('domain/'. $domain->getDomainRoot() .'/edit/'))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));
        $actions->addAction(
            id(new PhabricatorActionView())
                ->setIcon('fa-eye')
                ->setName(pht('View Raw'))
                ->setHref($this->getApplicationURI('domain/'. $domain->getDomainRoot() .'/raw/')));

        $records = mgroup($domain->getRecords(), 'getRecordType');
        foreach ($records as $recordType => $recordSet) {
            $type = TinydnsRecord::recordTypeName($recordType);
            $recordTbl = array();
            foreach ($recordSet as $record) {
                $recordTbl[] = array(
                    'FQDN' => $record->getFqdn(),
                    'IP' => $record->getData(),
                    'TTL' => $record->getTtl()
                );
            }

            $tbl = id(new AphrontTableView($recordTbl))
                ->setHeaders(array(pht('FQDN'), pht('IP'), pht('TTL')));
            $recordList = id(new PHUIObjectBoxView())
                ->setHeader(
                    id(new PHUIHeaderView())
                        ->setHeader(pht($type .' Records')))
                ->setTable($tbl)
                ->setUser($request->getViewer());
            $boxes[] = $recordList;
        }


        $content->setHeader($header);
        $content->addPropertyList($this->buildPropertyView($domain, $actions));
        #$content->appendChild($recordList);

        return $this->buildApplicationPage(
            $boxes,
            array(
                'title' => pht('Tinydns Edit'),
            )
        );
    }

    private function buildPropertyView(
        TinydnsDomain $domain,
        PhabricatorActionListView $actions) {

        $view = id(new PHUIPropertyListView())
                ->setUser($this->getViewer())
                ->setObject($domain)
                ->setActionList($actions);

        $policy_desc = PhabricatorPolicyQuery::renderPolicyDescriptions(
            $this->getViewer(),
            $domain);

        $view->addProperty(pht('Domain'), $domain->getDomainRoot());
        $view->addProperty(pht('NS1'), $domain->getNs1());
        $view->addProperty(pht('NS2'), $domain->getNs1());
        $view->addProperty(pht('Domain TTL'), $domain->getTtl());
        $view->addProperty(pht('Default TTL'), $domain->getDefaultRecordTTL());
        $view->addProperty(pht('Can Edit'), $policy_desc[PhabricatorPolicyCapability::CAN_EDIT]);

        return $view;
    }
}
