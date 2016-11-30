<?php
//xFacility2015
//XFInstaller
//Studio2b
//Michael Son(mson0129@gmail.com)
//25NOV2015(0.0.0) - This file is newly created.
//25NOV2015(0.1.0) - XFInstaller Class is added.
//30NOV2016(0.2.0) - XFShell and XFGit Classes are added. And XFInstaller Class is rewrited.
//30NOV2016(0.2.1) - NULL Value is acceptable in the $description argument of XFGit::push().
class XFShell {
	protected static $debug = false;
	
	public static function exec($command) {
		if(self::$debug)
			var_dump($command);
		$descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
		$proc = proc_open($command, $descriptorspec, $pipes);
		$return = new stdClass;
		$return->stdout = stream_get_contents($pipes[1]);
		$return->stderr = stream_get_contents($pipes[2]);
		foreach($pipes as $pipe) {
			fclose($pipe);
		}
		$return->return = proc_close($proc);
		if(self::$debug)
			var_dump($return);
		return $return;
	}
}

class XFGit {
	//Studio2b
	//Michael Son(mson0129@gmail.com)
	//30NOV(1.1.0) - Push method is updated. Now it supports version tagging. And Commiting Error when removing some files without adding or editing a file is fixed. 
	
	protected static $bin = "/usr/bin/git";
	public $config;
	
	public function __construct($config) {
		$this->config = new stdClass();
		if(!is_null($config->repo))
			$this->config->repo = $config->repo;
		if(!is_null($config->user->id))
			$this->config->user->id = $config->user->id;
		if(!is_null($config->user->password))
			$this->config->user->password = $config->user->password;
		if(!is_null($config->user->name))
			$this->config->user->name = $config->user->name;
		if(!is_null($config->user->email))
			$this->config->user->email = $config->user->email;
	}
	
	protected static function isOption($option) {
		switch($option) {
			case "add":
			case "bisect":
			case "branch":
			case "checkout":
			case "clone":
			case "commit":
			case "diff":
			case "fetch":
			case "grep":
			case "init":
			case "log":
			case "merge":
			case "mv":
			case "pull":
			case "push":
			case "rebase":
			case "reset":
			case "rm":
			case "show":
			case "status":
			case "tag":
				$return = true;
				break;
			default:
				$return = false;
		}
		return $return;
	}

	public function __call($name, $args) {
		return self::call($name, $args);
	}

	public static function __callStatic($name, $args) {
		return self::call($name, $args);
	}

	protected function call($name, $args) {
		if(self::isOption($name)) {
			return $name;
		} else {
			return false;
		}
	}

	protected static function setPath() {
		if(stristr(PHP_OS, "win")) {
			self::$bin = "git";
		} else {
			//} else if(stristr(PHP_OS, "dar") || stristr(PHP_OS, "linux")) { //OS X, Linux
			self::$bin = "/usr/bin/git";
		}
		return self::$bin;
	}

	public function version() {
		return XFShell::exec(sprintf("%s --version", self::$bin));
	}
	
	public function help($option=NULL) {
		if(self::isOption($option)) {
			$command = sprintf("man -P cat -c 'git-%s'", $option);
		} else {
			$command = sprintf("%s --help", self::$bin);
		}
		return XFShell::exec($command);
	}
	
	public function gitClone($from=NULL, $to=NULL) {
		if(!is_null($from) || !is_null($this->repo)) {
			$from = is_null($from) ? $this->repo : $from;
			$return = XFShell::exec(sprintf("%s clone --depth=1 %s %s", self::$bin, $from, $to));
		} else {
			$return = false;
		}
		return $return;
	}
	
	public function pull($to=NULL) {
		$command = "";
		if(!is_null($to) && $to!="" && $to)
			$command = sprintf("cd %s;", $to);
		return XFShell::exec($command.sprintf("%s pull", self::$bin));
	}
	
	public function push($version, $summary, $description=NULL, $from=NULL) {
		if(!is_null($description) && $description!="" && $description)
			$description = "\n\n".$description;
		$command = "";
		$needle = "://";
		$breakPos = strpos($this->config->repo, $needle) + strlen($needle);
		if(!is_null($from) && $from!="" && $from)
			$command .= sprintf("cd %s;", $from);
		$command .= sprintf("%s config --global user.name='%s';", self::$bin, $this->config->user->name);
		$command .= sprintf("%s config --global user.email='%s';", self::$bin, $this->config->user->email);
		$command .= sprintf("%s commit -a -m '%s%s' --author='%s <%s>';", self::$bin, $summary, $description, $this->config->user->name, $this->config->user->email);
		$command .= sprintf("%s tag -a '%s' -m '%s%s';", self::$bin, $version, $summary, $description);
		$command .= sprintf("%s push %s%s:%s@%s master;", self::$bin, substr($this->config->repo, 0, $breakPos), $this->config->user->id, $this->config->user->password, substr($this->config->repo, $breakPos));
		return XFShell::exec($command);
	}
}

class XFInstaller {
	public $repo, $path;

	public function __construct($repo, $path) {
		$this->repo = $repo;
		$this->path = $path;
	}

	protected static function delTree($dir) {
		//nbari@dalmp.com
		//http://php.net/manual/en/function.rmdir.php
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}
	
	public function install() {
		$return = XFGit::gitClone($this->repo, $this->path);
		return $return;
	}

	public function update() {
		return XFGit::pull($this->path);
	}

	public function delete() {
		//return XFShell::exec(sprintf("rm -rf %s", $this->path));
		return self::delTree($this->path);
	}

	public function reinstall() {
		self::delete();
		return self::install();
	}
}

$installer = new XFInstaller("https://github.com/studio2b/xFacility.git", "xfacility/v1/");

if(file_exists("xfacility/v1/.git/")) {
	//New Version
	$return = $installer->update();
} else if(file_exists("xfacility/v1/")) {
	//Old Version
	$return = $installer->reinstall();
} else {
	//Not installed
	$return = $installer->install();
}

if($return->return==0) {
	header("Location: /xfacility/v1/");
} else {
	var_dump($return->stderr);
}
?>