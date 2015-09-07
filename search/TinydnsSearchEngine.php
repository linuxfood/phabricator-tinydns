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

final class TinydnsSearchEngine extends PhabricatorApplicationSearchEngine {

    public function getApplicationClassName() {
        return 'TinydnsApplication';
    }

    protected function getURI($path) {
        return '/tinydns/'. $path . '/';
    }

    public function getResultTypeDescription() {
        return pht('Domains');
    }


    protected function renderResultList(
        array $domains,
        PhabricatorSavedQuery $query,
        array $handles) {

        $content = id(new PHUIObjectItemListView());
        foreach ($domains as $domain) {
            $i = id(new PHUIObjectItemView())
                ->setHeader($domain->getDomainRoot())
                ->setHref($domain->getURI());
            $content->addItem($i);
        }
        
        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($content);
        return $result;
    }

    protected function buildCustomSearchFields() {
        return array(
            id(new PhabricatorOwnersSearchField())
                ->setLabel(pht('Maintainer'))
                ->setKey('maintainerPHIDs')
                ->setAliases(array('your')),
        );
    }

    protected function buildQueryFromParameters(array $map) {
        return id(new TinydnsDomainQuery());
    }

    protected function getBuiltinQueryNames() {
        $names = array();
        if ($this->requireViewer()->isLoggedIn()) {
            $names['your'] = pht('Your Domains');
        }
        $names['all'] = pht('All Domains');
        return $names;
    }

    public function buildSavedQueryFromBuiltin($query_key) {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        $viewer_phid = $this->requireViewer()->getPHID();

        switch ($query_key) {
        case 'your':
            return $query
                ->setParameter('maintainerPHIDs', array($viewer_phid));
        case 'all':
            return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }
}
