<?php
//xFacility2015
//XFInstaller
//Studio2b
//Michael Son(mson0129@gmail.com)
//25NOV2015(0.0.0) - This file is newly created.
//25NOV2015(0.1.0) - XFInstaller Class is added.
class XFInstaller {
	protected static function shell($command) {
		//var_dump($command);
		$descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
		$proc = proc_open($command, $descriptorspec, $pipes);
		$return = new stdClass;
		$return->stdout = stream_get_contents($pipes[1]);
		$return->stderr = stream_get_contents($pipes[2]);
		foreach($pipes as $pipe) {
			fclose($pipe);
		}
		$return->return = proc_close($proc);
		//var_dump($return);
		return $return;
	}
	
	public static function install() {
		return self::shell("git clone --depth=1 https://github.com/studio2b/xFacility.git xfacility");
	}
	
	public static function update() {
		//$return = self::shell("git -C 'xfacility' pull"); //above 1.8
		$return = self::shell("cd xfacility;git pull");
		if($return->return!=0)
			$return = self::reinstall();
		return $return;
	}
	
	public static function delete() {
		return self::shell("rm xfacility -rf");
	}
	
	public static function reinstall() {
		self::delete();
		return self::install();
	}
}

if(file_exists("xfacility/.git/")) {
	//New Version
	$return = XFInstaller::update();
} else if(file_exists("xfacility/")) {
	//Old Version
	XFInstaller::delete();
	$return = XFInstaller::install();
} else {
	//Not installed
	$return = XFInstaller::install();
}

if($return->return==0) {
	header("Location: /xfacility/");
} else {
	var_dump($return->stderr);
}
?>
