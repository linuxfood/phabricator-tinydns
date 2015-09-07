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

class TinydnsController extends PhabricatorController {

    private $queryKey;

    public function buildSideNavView() {
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        id(new TinydnsSearchEngine())
            ->setViewer($this->getRequest()->getUser())
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    public function willProcessRequest(array $data) {
        $this->queryKey = idx($data, 'queryKey');
    }

    public function processRequest() {
        $controller = id(new PhabricatorApplicationSearchController())
            ->setQueryKey($this->queryKey)
            ->setSearchEngine(new TinydnsSearchEngine())
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToController($controller);
    }

    public function buildApplicationMenu() {
        return $this->buildSideNavView(true)->getMenu();
    }

    protected function buildApplicationCrumbs() {
        $crumbs = parent::buildApplicationCrumbs();

        $can_create = $this->hasApplicationCapability(
            TinydnsManageDomainsCapability::CAPABILITY);

        $crumbs->addAction(
            id(new PHUIListItemView())
            ->setName(pht('Create Domain'))
            ->setHref($this->getApplicationURI('domain/new/edit/?create=1'))
            ->setIcon('fa-plus-square')
            ->setWorkflow(!$can_create)
            ->setDisabled(!$can_create));

        return $crumbs;
    }
}
