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

final class TinydnsRecordCreateController extends TinydnsBaseController {
    public function handleRequest(AphrontRequest $request) {
        $domainPHID = $request->getStr('domainPHID');
        $recordType = $request->getStr('recordType');
        $uri = $this->getApplicationURI('record/create/');
        $dialog = $this->newDialog();

        if(!$domainPHID || !$recordType) {
            $dialog->setTitle(pht('Failure.'));
            $dialog->appendParagraph(pht('Invalid request parameters.'));
            $dialog->addCancelButton($uri, pht('Derp.'));
            return $dialog;
        }

        $domain = id(new TinydnsDomainQuery())
            ->needRecords(false)
            ->setViewer($this->getViewer())
            ->withPHIDs(array($domainPHID))
            ->execute();
        $domain = head($domain);

        PhabricatorPolicyFilter::requireCapability(
            $this->getViewer(),
            $domain,
            PhabricatorPolicyCapability::CAN_EDIT);

        $record = new TinydnsRecord();
        $record->setRecordType($recordType);

        $form = id(new AphrontFormView())
            ->setUser($this->getViewer());

        if ($request->isFormPost()) {
            $new_rec = array_merge(
                array(
                    'domainPHID' => $request->getStr('domainPHID'),
                    'recordType' => $request->getStr('recordType')
                ),
                idx($request->getArr('records'), 'new', array()));
            $record->loadFromArray($new_rec);
            try {
                $record->save();
            } catch (Exception $ex) {
                $dialog->setTitle(pht('Failure.'));
                $dialog->appendParagraph(
                    pht('There was some issue creating this record.'). $ex);
                $dialog->addCancelButton($uri, pht('Alright :/'));
                return $dialog;
            }

            $dialog->setTitle(pht('Created.'));
            $dialog->appendParagraph(
                pht('Record created. You need to refresh the domain edit page.'));
            $dialog->addCancelButton($uri, pht('OK'));
            return $dialog;
        }

        $form->appendControl(
            id(new AphrontFormTextControl())
            ->setHidden(true)
            ->setName('domainPHID')
            ->setValue($domainPHID));
        $form->appendControl(
            id(new AphrontFormTextControl())
            ->setHidden(true)
            ->setName('recordType')
            ->setValue($recordType));

        foreach ($record->getFormControls() as $control) {
            $form->appendControl($control);
        }

        $dialog->addSubmitButton(pht('Create'));
        $dialog->addCancelButton($uri);

        $dialog->appendForm($form);
        $dialog->setTitle(pht('Create '. TinydnsRecord::recordTypeName($recordType) .' Record'));

        return $dialog;
    }
}
