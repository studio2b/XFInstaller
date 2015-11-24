<?php
$descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
$proc = proc_open("git clone --depth=1 https://github.com/studio2b/xFacility.git xfacility", $descriptorspec, $pipes);
$return = new stdClass;
$return->stdout = stream_get_contents($pipes[1]);
$return->stderr = stream_get_contents($pipes[2]);
foreach($pipes as $pipe) {
	fclose($pipe);
}
$return->return = proc_close($proc);

if($return->return==0) {
	header("Location: /xfacility/");
} else {
	echo "failed";
}
?>
