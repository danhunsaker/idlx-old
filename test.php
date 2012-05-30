<?php

function strtohex($str) {
	$out = '';
	for ($i = 0; $i < strlen($str); $i++) {
		$out .= sprintf("%02x", ord($str{$i}));
	}
	return $out;
}

$nr = mhash_count();
$hashes = array();

for ($i = 0; $i <= $nr; $i++) {
	$hname = mhash_get_hash_name($i);
	if (empty($hname)) continue;
	$hashes[$hname]['size'] = mhash_get_block_size($i);
	$hashes[$hname]['hash'] = strtohex(mhash($i, $_SERVER['SERVER_NAME']));
	$hashes[$hname]['uuid'] = uniqid('idlx'.strtohex(mhash($i, $_SERVER['SERVER_NAME'])), true);
}

ksort($hashes);
asort($hashes);

foreach ($hashes as $hash=>$val) {
	if (empty($hash)) continue;
	echo sprintf("The blocksize of %s is %d [hash %s || uuid %s [[%d]] ]<br />\n", $hash, $val['size'], $val['hash'], $val['uuid'], strlen($val['uuid']));
	
}
?> 
