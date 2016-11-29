<?php
//xFacility
//XFInstaller
//Studio2b
//Michael Son(mson0129@gmail.com)
//25NOV2015(0.0.0) - This file is newly created.
//25NOV2015(0.1.0) - XFInstaller Class is added.
//30NOV2016(0.2.0) - XFShell and XFGit Classes are added. And XFInstaller Class is rewrited.

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
	protected static $path = "/usr/bin/git";
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
			self::$path = "git";
		} else {
			//} else if(stristr(PHP_OS, "dar") || stristr(PHP_OS, "linux")) { //OS X, Linux
			self::$path = "/usr/bin/git";
		}
		return self::$path;
	}

	public function version() {
		return XFShell::exec(sprintf("%s --version", self::$path));
	}
	
	public function help($option=NULL) {
		if(self::isOption($option)) {
			$command = sprintf("man -P cat -c 'git-%s'", $option);
		} else {
			$command ="git --help";
		}
		return XFShell::exec($command);
	}
	
	public function gitClone($from=NULL, $to=NULL) {
		if(!is_null($from) || !is_null($this->repo)) {
			$from = is_null($from) ? $this->repo : $from;
			$return = XFShell::exec(sprintf("git clone --depth=1 %s %s", $from, $to));
		} else {
			$return = false;
		}
		return $return;
	}
	
	public function pull($to=NULL) {
		$command = "";
		if(!is_null($to) && $to!="" && $to)
			$command = sprintf("cd %s;", $to);
		return XFShell::exec($command."git pull");
	}
	
	public function push($summary, $description, $from=NULL) {
		$command = "";
		$needle = "://";
		$breakPos = strpos($this->config->repo, $needle) + strlen($needle);
		if(!is_null($from) && $from!="" && $from)
			$command .= sprintf("cd %s;", $from);
		$command .= sprintf("git config --global user.name='%s';", $this->config->user->name);
		$command .= sprintf("git config --global user.email='%s';", $this->config->user->email);
		$command .= "git add *;";
		$command .= sprintf("git commit -m '%s\n\n%s' --author='%s <%s>';", $summary, $description, $this->config->user->name, $this->config->user->email);
		$command .= sprintf("git push %s%s:%s@%s master;", substr($this->config->repo, 0, $breakPos), $this->config->user->id, $this->config->user->password, substr($this->config->repo, $breakPos));
		return XFShell::exec($command);
	}
}

class XFInstaller {
	public $repo, $path;

	public function __construct($repo, $path) {
		$this->repo = $repo;
		$this->path = $path;
	}

	public static function delTree($dir) {
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

$conf = new stdClass();
$conf->repo = "https://github.com/studio2b/xFacility.git";
$conf->path = "xfacility/v1/";
$installer = new XFInstaller($conf->repo, $conf->path);

if(file_exists($conf->path.".git/")) {
	//New Version
	$return = $installer->update();
} else if(file_exists($conf->path)) {
	//Old Version
	$return = $installer->reinstall();
} else {
	//Not installed
	$return = $installer->install();
}

if($return->return==0) {
	header("Location: /".$conf->path);
} else {
	var_dump($return->stderr);
}
?>