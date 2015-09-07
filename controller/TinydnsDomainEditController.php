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

final class TinydnsDomainEditController extends TinydnsBaseController {

    public function handleRequest(AphrontRequest $request) {
        $create_domain = pht('Create Domain');
        $edit_domain = pht('Edit Domain');
        $domain_action_str = $edit_domain;

        $create = $request->getBool('create');
        $domain = null;
        if ($create) {
            $domain_action_str = $create_domain;
            $app = new TinydnsApplication();
            $domain = new TinydnsDomain();

            $domain->setViewPolicy(
                $app->getPolicy(PhabricatorPolicyCapability::CAN_VIEW));
            $domain->setEditPolicy(
                $app->getPolicy(TinydnsManageDomainsCapability::CAPABILITY));
            $domain->attachRecords(array());

        } else {
            $domain = $this->getDomain(array(PhabricatorPolicyCapability::CAN_EDIT));
        }

        $page_parts = array();
        if (!$domain) {
            return new Aphront404Response();
        }

        PhabricatorPolicyFilter::requireCapability(
            $this->getViewer(),
            $domain,
            PhabricatorPolicyCapability::CAN_EDIT);

        $crumbs = $this->buildApplicationCrumbs();
        if (!$create) {
            $crumbs->addTextCrumb(
                $domain->getDomainRoot(),
                $this->getApplicationURI('domain/'. $domain->getDomainRoot()));
            $crumbs->addTextCrumb(
                $edit_domain,
                $this->getApplicationURI('domain/'. $domain->getDomainRoot() .'/edit/'));
        } else {
            $crumbs->addTextCrumb(
                $create_domain,
                $this->getApplicationURI('domain/new/edit/?create=1'));
        }
        $page_parts[] = $crumbs;

        if ($request->isFormPost()) {
            try {
                $domain->openTransaction();
                if ($request->getArr('domainMeta')) {
                    $domain->loadFromArray($request->getArr('domainMeta'));
                    $domain->save();
                }
                if ($request->getArr('records')) {
                    $records = $domain->getRecords();
                    foreach ($request->getArr('records') as $id => $record) {
                        $records[$id]->loadFromArray($record);
                        $records[$id]->save();
                    }
                }
                $domain->saveTransaction();
                $page_parts[] = id(new PHUIObjectBoxView())
                    ->appendChild(pht('Records updated! It will take some time for it to propagate.'));
                if ($create) {
                    return id(new AphrontRedirectResponse())
                        ->setURI($this->getApplicationURI('domain/'. $domain->getDomainRoot() .'/edit/'));
                }
            } catch(Exception $ex) {
                $domain->killTransaction();
                $page_parts[] = id(new PHUIObjectBoxView())
                    ->appendChild(pht('Failed to save domain. I tried to restore what you had. Sowwy.'). $ex);
            }
        }

        $content = new PHUIObjectBoxView();
        $header = new PHUIHeaderView();
        $header->setHeader($domain_action_str);
        $header->setUser($this->getViewer());
        $header->setPolicyObject($domain);
        $content->setHeader($header);

        $ttt = array(
            'A' => TinydnsRecord::A_RECORD,
            'Alias' => TinydnsRecord::ALIAS_RECORD,
            'CNAME' => TinydnsRecord::CNAME_RECORD,
            'MX' => TinydnsRecord::MX_RECORD,
            'TXT' => TinydnsRecord::TXT_RECORD,
            'Raw' => TinydnsRecord::RAW_RECORD,
        );

        $policies = id(new PhabricatorPolicyQuery())
            ->setObject($domain)
            ->setViewer($this->getViewer())
            ->execute();

        $domainForm = id(new AphrontFormView())
            ->setAction($request->getRequestURI())
            ->setUser($this->getViewer());

        $domainForm->appendControl(
            id(new AphrontFormTextControl())
                ->setDisabled(!$create)
                ->setLabel(pht('Domain'))
                ->setName('domainMeta[domainRoot]')
                ->setValue($domain->getDomainRoot()));

        $domainForm->appendControl(
            id(new AphrontFormTextControl())
                ->setName('domainMeta[ns1]')
                ->setLabel(pht('NS1'))
                ->setValue($domain->getNs1())
                ->setDisabled(false));
        $domainForm->appendControl(
            id(new AphrontFormTextControl())
                ->setName('domainMeta[ns2]')
                ->setLabel(pht('NS2'))
                ->setValue($domain->getNs2())
                ->setDisabled(false));
        $domainForm->appendControl(
            id(new AphrontFormTextControl())
                ->setName('domainMeta[ttl]')
                ->setLabel(pht('Domain TTL'))
                ->setValue($domain->getTtl())
                ->setDisabled(false));
        $domainForm->appendControl(
            id(new AphrontFormTextControl())
                ->setName('domainMeta[defaultRecordTTL]')
                ->setLabel(pht('Default TTL'))
                ->setValue($domain->getDefaultRecordTtl())
                ->setDisabled(false));
        $domainForm->appendControl(
            id(new AphrontFormPolicyControl())
                ->setCapability(PhabricatorPolicyCapability::CAN_VIEW)
                ->setName('domainMeta[viewPolicy]')
                ->setPolicies($policies)
                ->setPolicyObject($domain));
        $domainForm->appendControl(
            id(new AphrontFormPolicyControl())
                ->setCapability(PhabricatorPolicyCapability::CAN_EDIT)
                ->setName('domainMeta[editPolicy]')
                ->setPolicies($policies)
                ->setPolicyObject($domain));
        if ($create) {
            $submit_prompt = pht('Create Domain');
        } else {
            $submit_prompt = pht('Update Domain');
        }
        $domainForm->appendControl(
            id(new AphrontFormSubmitControl())
                ->setValue($submit_prompt));

        $page_parts[] = $content;
        $records = mgroup($domain->getRecords(), 'getRecordType');
        $recordsForm = id(new AphrontFormView())
            ->setAction($request->getRequestURI())
            ->setUser($this->getViewer());
        foreach ($ttt as $name => $type) {
            $typeBox = id(new PHUIObjectBoxView())
                ->setHeader(id(new PHUIHeaderView())
                    ->setHeader(pht($name .' Records')));

            $recordsForm->appendChild($typeBox);
            $rows = array();
            $add_record_button =
                id(new PHUIButtonView())
                    ->setText(pht('Add '. $name .' Record'))
                    ->setTag('a')
                    ->setWorkflow(true)
                    ->setHref(
                        id(new PhutilURI($this->getApplicationURI('record/create/')))
                        ->setQueryParams(array(
                            'domainPHID' => $domain->getPHID(),
                            'recordType' => $type)))
                    ->setIcon(
                        id(new PHUIIconView())->setIconFont('fa-plus'));
            if (!isset($records[$type])) {
                $typeBox->appendChild($add_record_button);
                continue;
            }
            foreach ($records[$type] as $record) {
                $row = $record->getFormControls();
                foreach ($row as $control) {
                    $control->setUser($this->getViewer());
                }
                $row[] = id(new PHUIButtonView())
                    ->setText('Delete')
                    ->setColor('red')
                    ->setTag('a')
                    ->setWorkflow(true)
                    ->setHref($this->getApplicationURI("record/{$record->getID()}/delete/"))
                    ->setIcon(id(new PHUIIconView())->setIconFont('fa-trash'));
                $rows[] = $row;
            }
            $table = new AphrontTableView($rows);
            $table->setNoDataString(pht('No records of this type. Add one?'));

            $typeBox->appendChild($table);
            $typeBox->appendChild($add_record_button);
        }
        $recordsForm->appendControl(
            id(new AphrontFormSubmitControl())
            ->setValue(pht('Update Records')));

        $content->appendChild($domainForm);
        if (!$create) {
            $content->appendChild($recordsForm);
        }

        return $this->buildApplicationPage(
            $page_parts,
            array(
                'title' => $domain_action_str,
            )
        );
    }
}
