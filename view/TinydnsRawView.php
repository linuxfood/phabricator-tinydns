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

final class TinydnsRawView {

    private $domain;
    private $viewer;

    public function setDomain(TinydnsDomain $domain) {
        $this->domain = $domain;
        return $this;
    }

    public function setViewer(PhabricatorUser $viewer) {
        $this->viewer = $viewer;
        return $this;
    }

    public function render() {
        $d = $this->domain;
        $date_str = phabricator_datetime(time(), $this->viewer);
        $out = "";
        $out .= "#\n";
        $out .= "# Domain: {$d->getDomainRoot()}\n";
        $out .= "# Generated: {$date_str}\n";
        $out .= "#\n";
        $out .= $this->renderLine('.', $d->getDomainRoot(), $d->getNs1(), 'a', 259200);
        $out .= $this->renderLine('.', $d->getDomainRoot(), $d->getNs2(), 'b', 259200);
        foreach ($d->getRecords() as $r) {
            $out .= $this->renderLine(
                $r->getRecordType(),
                $r->getFqdn(),
                $r->getData(), 
                $r->getData2(),
                $r->getTtl());
        }
        return $out;
    }

    private function renderLine($type, $fqdn, $data1, $data2, $ttl) {
        if ($data2 === null) {
            return "$type$fqdn:$data1:$ttl\n";
        } else {
            return "$type$fqdn:$data1:$data2:$ttl\n";
        }
    }
}
