<?php
/*
files (array, now local, remote)
add stautus (Successful, Error Detected, xxx Files Uploaded/Downloaded)
*/
class ftp_class {
	var $command;
	var $host;
	var $port=21;
	var $user;
	var $password;
	var $pasv=0;
	var $binary=1;
	var $remote_folder;
	var $local_folder;
	var $files=array();
    var $success=0;
    var $status;
    var $error=array();
    var $results=array();
    var $constant;
    var $ftpfh;
	function scs_version() {
	    $results=array();
	    $results['09/11/2020']="Initial release";
		$results['file']= __FILE__;
	    return $results;
	}
	function __construct() {
    	$this->constant=new stdClass();
        $this->constant->command=array(  "get"=>"Get File",  "put"=>"Put File", "nlist"=>"List (nlist)", "rawlist"=>"List (rawlist)", "mlsd"=>"List (mlsd)", "systype"=>"System Type" );
    }
    function execute() {
        $this->constant->cwd=getcwd();
        $this->success=$this->process();
		if ($this->local_folder) chdir($this->constant->cwd);
        if ($this->ftpfh) @ftp_close($this->ftpfh);
        switch (TRUE) {
          case ($this->status):
          	break;
		  case ($this->success):
          	$this->status="Successful";
            break;
		  default:
          	$this->status="Errors detected";
		}
        return $this->success;
    }
    private function process() {
		if ( (strlen($this->local_folder)) && (!@chdir($this->local_folder)) ) $this->error['local_folder']="Invalid folder: " . $this->local_folder;
    	switch (TRUE) {
          case (!$this->command):
          	$this->error['command']="Not selected";
            break;
          case (!in_array($this->command,array("get","put"))):
          	break;
          case (!sizeof($this->files)):
			$this->error['files']="Empty list";
            break;
          case ($this->command == "put"):
          	$files=scandir(getcwd());
          	$missing=array();
            foreach ($this->files as $file) {
            	if ( ($file == ".") || ($file == "..") || (!in_array($file, $files)) ) $missing[]=$file;
            }
            if (sizeof($missing)) $this->error['files']="Missing file(s): " . implode(", ",$missing);
			break;
		}
		if (sizeof($this->error)) return;
    	$this->ftpfh=@ftp_connect($this->host, $this->port, 10);
	    if (!$this->ftpfh) {
        	$this->error["host"]="Cannot connect to: " . $this->host . " at port: " . $this->port;
	        return;
	    }
	    if (!@ftp_login($this->ftpfh, $this->user, $this->password)) {
	        $this->error["user"]="Invalid username/password";
	        return;
	    }
        if ( ($this->pasv) && (@ftp_pasv($this->ftpfh, true)) ) {
	        $this->error["pasv"]="Not supported";
	        return;
	    }
	    if ( (strlen($this->remote_folder)) && (!@ftp_chdir($this->ftpfh, $this->remote_folder)) ) {
	        $this->error["remote_folder"]="Invalid folder: " . $this->remote_folder;
	        return;
	    }
        if (method_exists($this, $this->command)) {
        	return $this->{$this->command}();
		  } else {
          	$this->error['command']="Invalid command: " . $this->command;
		}
    }
    private function put() {
    	foreach ($this->files as $file) {
	        if (!@ftp_put($this->ftpfh, $file, $file, ($this->binary) ? FTP_BINARY : FTP_ASCII) ) {
	            $this->error['command']="Error detected";
	            return;
	        }
        }
        $this->status=number_format(sizeof($this->files)) . " file(s) downloaded";
        return TRUE;
    }
    private function get() {
		$files=@ftp_nlist($this->ftpfh,"");
        $missing=array();
        foreach ($this->files as $file) {
            if ( ($file == ".") || ($file == "..") || (!in_array($file, $files)) ) $missing[]=$file;
        }
        if (sizeof($missing)) {
        	$this->error['files']="Missing file(s): " . implode(", ",$missing);
			return;
		}
    	foreach ($this->files as $file) {
	        if (!@ftp_get($this->ftpfh, $file, $file, ($this->binary) ? FTP_BINARY : FTP_ASCII) ) {
	            $this->error['command']="Error detected";
	            return;
	        }
        }
        $this->status=number_format(sizeof($this->files)) . " file(s) uploaded";
        return TRUE;
    }
    private function nlist() {
		$text=@ftp_nlist($this->ftpfh,"");
        if (!is_array($text)) {
        	$this->error['command']="Not supported";
        	return;
		}
        sort($text);
        $this->results[]="<div>";
        $this->results[]=implode("<br>",$text);
        $this->results[]="</div>";
        return TRUE;
    }
    private function rawlist() {
		$text=ftp_rawlist($this->ftpfh,"");
	    $regex="/^([drwx+-]{10})\s+(\d+)\s+(\w+)\s+(\w+)\s+(\d+)\s+(.{12}) (.*)$/m";
		preg_match_all($regex, implode("\n",$text), $items, PREG_SET_ORDER);
        $this->results[]="<table class='standard border tablesorter'>";
        $this->results[]="<thead>";
        $this->results[]="<tr>";
        $this->results[]="<th>Permission</th>";
        $this->results[]="<th>Owner</th>";
        $this->results[]="<th>Group</th>";
        $this->results[]="<th>Size</th>";
        $this->results[]="<th>Revised</th>";
        $this->results[]="<th>File Name</th>";
        $this->results[]="</tr>";
        $this->results[]="</thead>";
        $this->results[]="<tbody>";
        foreach ($items as $item) {
        	$this->results[]="<tr>";
        	$this->results[]="<td>" . $item[1] . "</td>";
        	$this->results[]="<td>" . $item[3] . "</td>";
        	$this->results[]="<td>" . $item[4] . "</td>";
        	$this->results[]="<td class=right>" . number_format($item[5]) . "</td>";
        	$this->results[]="<td>" . $item[6] . "</td>";
        	$this->results[]="<td>" . $item[7] . "</td>";
        	$this->results[]="</tr>";
        }
        $this->results[]="</tbody>";
        $this->results[]="</table>";
        return TRUE;
    }
    private function mlsd() {
        if (PHP_VERSION_ID < 70200) {
        	$this->error['command']="Requires PHP version 7.2 or higher";
        	return;
		}
		$text=@ftp_mlsd($this->ftpfh,"");
        if (!is_array($text)) {
        	$this->error['command']="Not supported";
        	return;
		}
        $this->results[]="<div>";
        $this->results[]=implode("<br>",$text);
        $this->results[]="</div>";
        return TRUE;
    }
    private function systype() {
		$text=@ftp_systype($this->ftpfh);
		switch (TRUE) {
          case (is_array($text)):
          	$this->results=$text;
            break;
		  case (strlen($text)):
          	$this->status=$text;
            break;
          default:
          	$this->status="No response";
		}
    }
}
/*
may require sftp https://www.php.net/manual/en/function.ssh2-sftp.php

ftp_connect ( string $host [, int $port = 21 [, int $timeout = 90 ]] ) : resource
ftp_ssl_connect ( string $host [, int $port = 21 [, int $timeout = 90 ]] ) : resource

ftp_login ( resource $ftp_stream , string $username , string $password ) : bool
ftp_chdir ( resource $ftp_stream , string $directory ) : bool

ftp_get ( resource $ftp_stream , string $local_file , string $remote_file [, int $mode = FTP_BINARY [, int $resumepos = 0 ]] ) : bool
ftp_put ( resource $ftp_stream , string $remote_file , string $local_file [, int $mode = FTP_BINARY [, int $startpos = 0 ]] ) : bool
ftp_close ( resource $ftp_stream ) : bool

ftp_nlist ( resource $ftp_stream , string $directory ) : array
ftp_pasv ( resource $ftp_stream , bool $pasv ) : bool
ftp_rawlist ( resource $ftp_stream , string $directory [, bool $recursive = FALSE ] ) : array
ftp_systype ( resource $ftp_stream ) : string
*/
?>