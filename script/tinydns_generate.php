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
/*
 * This script is written in as vanilla PHP as can be managed to minimize
 * the dependencies. This should make it fairly possible to deploy on DNS
 * servers that want to avoid having many extra dependencies.
 * It does require libphutil <https://github.com/phacility/libphutil>.
 */

$phutil_root = dirname(__FILE__) . '/libphutil';
$uri = null;
$conduit_token = null;
$data_path = null;
$tinydns_data_prog = 'tinydns-data';

if (!isset($argv[1]) ||
    $argv[1] == '-h' ||
    $argv[1] == '--help') {
    echo "Generates data.cdb for tinydns.\n\n".
        "Usage: {$argv[0]} CONFIGFILE\n".
        "Config INI fields:\n".
        "  phutil_root    Path to libphutil, defaults to {$phutil_root}\n".
        "  uri            URI of your phabrictor install.\n".
        "  conduit_token  Conduit API token to use to get rendered domains.\n".
        "  data_path      Directory where the data file should be written.\n".
        "  tinydns_data_prog  Path to 'tinydns-data' binary.\n";
    exit(0);
}

$config = parse_ini_file($argv[1]);

if (isset($config['phutil_root'])) {
    $phutil_root = $config['phutil_root'];
}

if (isset($config['tinydns_data_prog'])) {
    $tinydns_data_prog = $config['tinydns_data_prog'];
}

if (!isset($config['uri']) ||
    !isset($config['conduit_token']) ||
    !isset($config['data_path'])) {
    echo "You need to set 'uri', 'conduit_token', and 'data_path' in the config.\n";
    exit(1);
}
$uri = $config['uri'];
$conduit_token = $config['conduit_token'];
$data_path = $config['data_path'] . '/data';

require_once($phutil_root.'/src/__phutil_library_init__.php');

$client = new ConduitClient($uri);
$client->setConduitToken($conduit_token);
$result = $client->callMethodSynchronous('tinydns.render', array());

Filesystem::writeFileIfChanged($data_path, $result);
chdir(dirname($data_path));
execx($tinydns_data_prog);
