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
        if ($type === 'AAAA') {
            # :ipv6-host.example.com:28:\040\001\022\064\126\170\000\000\000\000\000\000\000\000\103\041:86400
            # ^1.2.3.4.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.8.7.6.5.4.3.2.1.1.0.0.2.ip6.arpa:ipv6-host.example.com:86400
            return ":$fqdn:28:". $this->ip6oct($data1) .":$ttl\n".
                   '^'. $this->ip6rptr($data1) .":$fqdn:$ttl\n";
        }
        if ($data2 === null) {
            return "$type$fqdn:$data1:$ttl\n";
        } else {
            return "$type$fqdn:$data1:$data2:$ttl\n";
        }
    }

    private function ip6normalize($ip) {
        $groups = explode(':', $ip);
        $i = array_search('', $groups);
        if ($i !== false) {
            array_splice($groups, $i, 1, array_pad(array(), 9 - count($groups), '0000'));
        }
        return array_map(function($x) { return sprintf('%04d', $x); }, $groups);
    }

    private function ip6oct($ip) {
        $groups = ip6normalize($ip);
        $result = array();
        foreach($groups as $group) {
            list($hbyte, $lbyte) = str_split($group, 2);
            $result[] = sprintf('\\%03d', base_convert($hbyte, 16, 8));
            $result[] = sprintf('\\%03d', base_convert($lbyte, 16, 8));
        }
        return implode($result);
    }

    private function ip6rptr($ip) {
        $nibbles = array_reverse(str_split(implode(ip6normalize($ip))));
        return implode('.', $nibbles) . '.ip6.arpa';
    }
}
