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

final class TinydnsRecordDeleteController extends TinydnsBaseController {
    public function handleRequest(AphrontRequest $request) {
        $rid = $request->getURIData('recordID');
        $uri = $this->getApplicationURI("record/$rid/delete/");
        $dialog = $this->newDialog();

        if (!$rid) {
            $dialog->setTitle(pht('Failure.'));
            $dialog->appendParagraph(pht('Invalid request parameters.'));
            $dialog->appendChild(
                id(new PHUIObjectBoxView())
                ->appendChild(phutil_tag('pre', array(), print_r($request->getRequestData(), true))));
            $dialog->addCancelButton($uri, pht('Derp.'));
            return $dialog;
        }

        $record = null;
        try {
            $record = id(new TinydnsRecordQuery())
                ->withIDs(array($rid))
                ->setViewer($this->getViewer())
                ->execute();
            $record = head($record);
        } catch (Exception $ex) {
            $dialog->setTitle(pht('Failure.'));
            $dialog->appendParagraph(pht('An issue has occured looking up this record.'). $ex);
            $dialog->addCancelButton($uri, pht('OK'));
            return $dialog;
        }

        PhabricatorPolicyFilter::requireCapability(
            $this->getViewer(),
            $record,
            PhabricatorPolicyCapability::CAN_EDIT);

        if ($request->isFormPost()) {
            try {
                $record->delete();
                $dialog->setTitle(pht('Destruction complete.'));
                $dialog->appendParagraph(pht('Record deleted. Hope you meant to do that..'));
                $dialog->addCancelButton($uri, pht('OK'));
                return $dialog;
            } catch (Exception $ex) {
                $dialog->setTitle(pht('Failure.'));
                $dialog->appendParagraph(pht('Failed to delete record.'). $ex);
                $dialog->addCancelButton($uri, pht('OK'));
                return $dialog;
            }
        }

        $form = id(new AphrontFormView())
            ->setUser($this->getViewer());
        $dialog->setTitle(pht('Really?'));
        $dialog->appendParagraph(pht('Are you certain you want to delete this record?'));
        $typeName = TinydnsRecord::recordTypeName($record->getRecordType());
        $dialog->appendParagraph(pht("This fine {$typeName} record about {$record->getFqdn()} and {$record->getData()}? It looks so happy.."));
        $dialog->addSubmitButton(pht('Delete'));
        $dialog->addCancelButton($uri);

        return $dialog;
    }
}
