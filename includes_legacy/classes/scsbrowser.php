<?php
$database->scsbrowser = new scsbrowser_class();
class scsbrowser_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['04/23/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new scsbrowser_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new scsbrowser_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new scsbrowser_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->pattern=$this->fetch['pattern'];
	    $this->data->mysql_pattern=$this->fetch['mysql_pattern'];
	    $this->data->parent=$this->fetch['parent'];
	    $this->data->comment=$this->fetch['comment'];
	    $this->data->browser=$this->fetch['browser'];
	    $this->data->version=$this->fetch['version'];
	    $this->data->majorver=$this->fetch['majorver'];
	    $this->data->minorver=$this->fetch['minorver'];
	    $this->data->platform=$this->fetch['platform'];
	    $this->data->platform_version=$this->fetch['platform_version'];
	    $this->data->alpha=$this->fetch['alpha'];
	    $this->data->beta=$this->fetch['beta'];
	    $this->data->win16=$this->fetch['win16'];
	    $this->data->win32=$this->fetch['win32'];
	    $this->data->win64=$this->fetch['win64'];
	    $this->data->frames=$this->fetch['frames'];
	    $this->data->iframes=$this->fetch['iframes'];
	    $this->data->tables=$this->fetch['tables'];
	    $this->data->cookies=$this->fetch['cookies'];
	    $this->data->backgroundsounds=$this->fetch['backgroundsounds'];
	    $this->data->javascript=$this->fetch['javascript'];
	    $this->data->vbscript=$this->fetch['vbscript'];
	    $this->data->javaapplets=$this->fetch['javaapplets'];
	    $this->data->activexcontrols=$this->fetch['activexcontrols'];
	    $this->data->ismobiledevice=$this->fetch['ismobiledevice'];
	    $this->data->issyndicationreader=$this->fetch['issyndicationreader'];
	    $this->data->crawler=$this->fetch['crawler'];
	    $this->data->cssversion=$this->fetch['cssversion'];
	    $this->data->aolversion=$this->fetch['aolversion'];
        $this->options();
	}
    function options() {
    	$this->data->options=array();
        if ($this->data->frames) $this->data->_options[]="frames";
        if ($this->data->iframes) $this->data->_options[]="iframes";
        if ($this->data->tables) $this->data->_options[]="tables";
        if ($this->data->cookies) $this->data->_options[]="cookies";
        if ($this->data->backgroundsounds) $this->data->_options[]="background sounds";
        if ($this->data->javascript) $this->data->_options[]="javascript";
        if ($this->data->javaapplets) $this->data->_options[]="javaapplets";
        if ($this->data->vbscript) $this->data->_options[]="vbscript";
        if ($this->data->activexcontrols) $this->data->_options[]="activex controls";
    }
    function read($pattern) {
		$query="select * from scsbrowser where pattern=" . fn_escape($pattern);
        $this->query($query);
        if ($this->meta->rows) {
			$this->fetch(TRUE);
            $this->free_result();
		  } else {
			$this->data=new scsbrowser_data_class();
        }
    }
    function update() {
		if ($this->data->parent != "DefaultProperties") {
	        $pattern_input=array("*","[","]","(",")",".","?");
	        $pattern_intermediate=array("SCS-ASTRISK","SCS-LEFT-BRACE","SCS-RIGHT-BRACE","SCS-LEFT-PAREN","SCS-RIGHT-PAREN","SCS-PERIOD","SCS-QUESTION");
	        $pattern_final=array(".*","\\[","\\]","\\(","\\)","\\.","\\?");
	        $intermediate=str_replace($pattern_input,$pattern_intermediate,strtolower($this->data->pattern));
	        $this->data->mysql_pattern="^" . str_replace($pattern_intermediate,$pattern_final,$intermediate) . "$";
		}
		$query=
        	"insert into scsbrowser set \n" .
	        "id=" . fn_escape($this->constant->id) . ",\n" .
	        "pattern=" . fn_escape($this->data->pattern) . ",\n" .
	        "mysql_pattern=" . fn_escape($this->data->mysql_pattern) . ",\n" .
	        "parent=" . fn_escape($this->data->parent) . ",\n" .
	        "comment=" . fn_escape($this->data->comment) . ",\n" .
	        "browser=" . fn_escape($this->data->browser) . ",\n" .
	        "version=" . fn_escape($this->data->version) . ",\n" .
	        "majorver=" . fn_escape($this->data->majorver) . ",\n" .
	        "minorver=" . fn_escape($this->data->minorver) . ",\n" .
	        "platform=" . fn_escape($this->data->platform) . ",\n" .
	        "platform_version=" . fn_escape($this->data->platform_version) . ",\n" .
	        "alpha=" . fn_escape($this->data->alpha,FALSE) . ",\n" .
	        "beta=" . fn_escape($this->data->beta,FALSE) . ",\n" .
	        "win16=" . fn_escape($this->data->win16,FALSE) . ",\n" .
	        "win32=" . fn_escape($this->data->win32,FALSE) . ",\n" .
	        "win64=" . fn_escape($this->data->win64,FALSE) . ",\n" .
	        "frames=" . fn_escape($this->data->frames,FALSE) . ",\n" .
	        "iframes=" . fn_escape($this->data->iframes,FALSE) . ",\n" .
	        "tables=" . fn_escape($this->data->tables,FALSE) . ",\n" .
	        "cookies=" . fn_escape($this->data->cookies,FALSE) . ",\n" .
	        "backgroundsounds=" . fn_escape($this->data->backgroundsounds,FALSE) . ",\n" .
	        "javascript=" . fn_escape($this->data->javascript,FALSE) . ",\n" .
	        "vbscript=" . fn_escape($this->data->vbscript,FALSE) . ",\n" .
	        "javaapplets=" . fn_escape($this->data->javaapplets,FALSE) . ",\n" .
	        "activexcontrols=" . fn_escape($this->data->activexcontrols,FALSE) . ",\n" .
	        "ismobiledevice=" . fn_escape($this->data->ismobiledevice,FALSE) . ",\n" .
	        "issyndicationreader=" . fn_escape($this->data->issyndicationreader,FALSE) . ",\n" .
	        "crawler=" . fn_escape($this->data->crawler,FALSE) . ",\n" .
	        "cssversion=" . fn_escape($this->data->cssversion,FALSE) . ",\n" .
	        "aolversion=" . fn_escape($this->data->aolversion,FALSE);
		$this->query($query);
        $this->constant->id--;
    }
    function xml() {

/* this works
$xml=new DOMDocument();
$xml->load("../../www/logs/browscap.xml");
echo $xml->saveXML();
*/
$xml=new DOMDocument();
$xml->load("../../www/logs/browscap.xml");
$items=$xml->getElementsByTagName('gjk_browscap_version');
foreach ($items as $node) {
	foreach ($node->childNodes as $child_key => $child) {
print "<p>$child_key: ";
print $child->nodeValue;
print "</p>\n";
	}
}

die;
    }
    function import() {
		$ftp_handle=ftp_connect("suburbancomputer.com");
        if (!$ftp_handle) return "Cannot connect to suburbancomputer.com";
		if (!ftp_login($ftp_handle,"download", "Suburban")) return "Invalid user name/password";
		ftp_pasv($ftp_handle, TRUE);
		$tempname = tempnam("/tmp", "browscap.ini");
        $fh=fopen($tempname,"w");
		if (!ftp_fget($ftp_handle,$fh,"browscap.ini",FTP_BINARY)) return "Cannot retrieve browscap.ini";
		ftp_close($ftp_handle);
        fclose($fh);
        $this->contents=file($tempname);
		$this->query("drop table if exists scsbrowser");
		$query =
			"CREATE TABLE `scsbrowser` (\n" .
			"`id` int(11) unsigned NOT NULL DEFAULT '0',\n" .
			"`pattern` varchar(255) DEFAULT '',\n" .
			"`mysql_pattern` varchar(255) DEFAULT '',\n" .
			"`parent` varchar(255) DEFAULT '',\n" .
			"`comment` varchar(255) DEFAULT '',\n" .
			"`browser` varchar(255) DEFAULT '',\n" .
			"`version` varchar(255) DEFAULT '',\n" .
			"`majorver` varchar(255) DEFAULT '',\n" .
			"`minorver` varchar(255) DEFAULT '',\n" .
			"`platform` varchar(255) DEFAULT '',\n" .
			"`platform_version` varchar(255) DEFAULT '',\n" .
			"`alpha` int(1) DEFAULT '0',\n" .
			"`beta` int(1) DEFAULT '0',\n" .
			"`win16` int(1) DEFAULT '0',\n" .
			"`win32` int(1) DEFAULT '0',\n" .
			"`win64` int(1) DEFAULT '0',\n" .
			"`frames`  int(1) DEFAULT '0',\n" .
			"`iframes`  int(1) DEFAULT '0',\n" .
			"`tables`  int(1) DEFAULT '0',\n" .
			"`cookies`  int(1) DEFAULT '0',\n" .
			"`backgroundsounds` int(1) DEFAULT '0',\n" .
			"`javascript` int(1) DEFAULT '0',\n" .
			"`vbscript` int(1) DEFAULT '0',\n" .
			"`javaapplets` int(1) DEFAULT '0',\n" .
			"`activexcontrols` int(1) DEFAULT '0',\n" .
			"`ismobiledevice` int(1) DEFAULT '0',\n" .
			"`issyndicationreader` int(1) DEFAULT '0',\n" .
			"`crawler` int(1) DEFAULT '0',\n" .
			"`cssversion` int(2) DEFAULT '0',\n" .
			"`aolversion` int(2) DEFAULT '0',\n" .
			"PRIMARY KEY (`id`),\n" .
            "KEY `regexp_key` (`mysql_pattern`)\n" .
			") ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='SCS Browser'";
        $this->query($query);
		$this->import_content(TRUE);
		$this->import_content(FALSE);
    }
    function import_content($header) {
	    $first_pass=FALSE;
	    foreach ($this->contents as $line) {
	        $line=trim($line);
	        switch (TRUE) {
	          case (!$line):
	          case (substr($line,0,2)==";;"):
	            break;
	          case (preg_match("/^[\[](.+)\]$/",$line)):
	            switch (TRUE) {
	              case ($pattern =="GJK_Browscap_Version"):
	                break;
	              case (!$first_pass):
	                $first_pass=TRUE;
	                break;
                  case (($header) && (in_array($this->data->parent,array("","DefaultProperties")))):
                  case ((!$header) && (!in_array($this->data->parent,array("","DefaultProperties")))):
	          		$this->data->pattern=$pattern;
                    if (!$this->data->parent) {
                    	$this->data->parent="DefaultProperties";
                        $this->data->comment="Version: " . $this->constant->version . " Released: " .$this->constant->released;
					}
	                $this->update();
                    break;
	            }
	            $pattern=preg_replace("/(^\[)(.+)\]$/","$2",$line);
	            $this->data=new scsbrowser_data_class();
	            break;
	          default:
	            list($field,$value)=explode("=",$line);
	            $field=strtolower(trim($field));
	            $value=preg_replace(array('/^true$/i','/^false$/i','/^default$/i','/^\"(.+)\"$/'),array('1','0','0','$1'),trim($value));
	            switch (TRUE) {
	              case ($pattern == "GJK_Browscap_Version"):
					$this->constant->{$field}=$value;
	                break;
	              case ($field=="parent"):
	                $this->read($value);
	                $this->data->parent=$value;
	                break;
	              default:
	                $this->data->{$field}=$value;
	            }
	        }
	    }
    }
    function agent($agent,$browscap=FALSE) {
		if (!$this->constant->browscap) $browscap=FALSE;
		$this->data=new scsbrowser_data_class();
	    if ($browscap) {
			$php_agent=get_browser($agent);
            foreach ($php_agent as $field => $value) {
				$this->data->{strtolower($field)}=$value;
            }
			$this->options();
            $this->meta->rows=1;
	      } else {
	        $query=
	            "select \n" .
	            fn_escape($agent) . " regexp mysql_pattern as 'success',\n" .
	            "scsbrowser.* from scsbrowser \n" .
	            "where mysql_pattern <> ''\n" .
	            "having success=1 \n" .
	            "order by id limit 1\n";
	        $this->query($query);
	        if ($this->meta->rows) {
	            $this->fetch(TRUE);
				$this->free_result();
	        }
		}
    }
    function agent_table($agent,$php=FALSE) {
		$this->agent($agent,$php);
	    print "<tr>\n";
	    print "<td width='30%'>Agent</td>\n";
	    print "<td width='70%'>" . $agent . "</td>\n";
	    print "</tr>\n";
        $options=array();
        $options[]=$this->data->comment;
		if ($this->data->win16) $options[]="(Windows 16 bit)";
		if ($this->data->win32) $options[]="(Windows 32 bit)";
		if ($this->data->win64) $options[]="(Windows 64 bit)";
		if ($this->data->alpha) $options[]="(Alpha)";
        if ($this->data->beta) $options[]="(Beta)";
        print "<tr>\n";
        print "<td>Browser</td>\n";
        print "<td>" . implode(" ",$options) . "</td>\n";
        print "</tr>\n";
        if (!$this->meta->rows) return;

		print "<tr>\n";
        print "<td>Supported features</td>\n";
        print "<td>" . implode(", ",$this->data->_options) . "</td>\n";
        print "</tr>\n";
        if ($this->data->cssversion) {
	        print "<tr>\n";
	        print "<td>CSS version</td>\n";
	        print "<td>" . $this->data->cssversion . "</td>\n";
	        print "</tr>\n";
        }
        print "<tr>\n";
        print "<td>Operating system</td>\n";
        print "<td>" . $this->data->platform . "</td>\n";
        print "</tr>\n";
        if (!$this->meta->rows) return;
        switch (TRUE) {
          case ($this->data->ismobiledevice):
          	$text="Mobile device";
            break;
          case ($this->data->issyndicationreader):
          	$text="Syndication reader";
			break;
          case ($this->data->crawler):
          	$text="Crawler";
			break;
          default:
			$text="";
		}
        if ($text) {
	        print "<tr>\n";
	        print "<td>Device type</td>\n";
	        print "<td>$text</td>\n";
	        print "</tr>\n";
        }
        if ($this->data->aolversion) {
	        print "<tr>\n";
	        print "<td>AOL Version</td>\n";
	        print "<td>" . $this->data->aolversion . "</td>\n";
	        print "</tr>\n";
        }
    }
}
class scsbrowser_data_class {
    var $pattern;
    var $parent;
	var $comment="unknown";
	var $browser="unknown";
	var $version="0.0";
	var $majorver="0";
	var $minorver="0";
	var $platform="unknown";
	var $platform_version="unknown";
	var $alpha=0;
	var $beta=0;
	var $win16=0;
	var $win32=0;
	var $win64=0;
	var $frames=0;
	var $iframes=0;
	var $tables=0;
	var $cookies=0;
	var $backgroundsounds=0;
	var $javascript=0;
	var $vbscript=0;
	var $javaapplets=0;
	var $activexcontrols=0;
	var $ismobiledevice=0;
	var $issyndicationreader=0;
	var $crawler=0;
	var $cssversion=0;
	var $aolversion=0;
    var $_options=array();
}
class scsbrowser_constant_class {
	var $id=999999;
    var $version;
    var $released;
    var $browscap;
    var $match=array();
    var $replace=array();
    var $contents=array();
    function __construct() {
		$this->browscap=ini_get('browscap');
    }
}
/* Revised 04/24/2013
CREATE TABLE `scsbrowser` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `pattern` varchar(255) DEFAULT '',
  `mysql_pattern` varchar(255) DEFAULT '',
  `parent` varchar(255) DEFAULT '',
  `comment` varchar(255) DEFAULT '',
  `browser` varchar(255) DEFAULT '',
  `version` varchar(255) DEFAULT '',
  `majorver` varchar(255) DEFAULT '',
  `minorver` varchar(255) DEFAULT '',
  `platform` varchar(255) DEFAULT '',
  `platform_version` varchar(255) DEFAULT '',
  `alpha` int(1) DEFAULT '0',
  `beta` int(1) DEFAULT '0',
  `win16` int(1) DEFAULT '0',
  `win32` int(1) DEFAULT '0',
  `win64` int(1) DEFAULT '0',
  `frames` int(1) DEFAULT '0',
  `iframes` int(1) DEFAULT '0',
  `tables` int(1) DEFAULT '0',
  `cookies` int(1) DEFAULT '0',
  `backgroundsounds` int(1) DEFAULT '0',
  `javascript` int(1) DEFAULT '0',
  `vbscript` int(1) DEFAULT '0',
  `javaapplets` int(1) DEFAULT '0',
  `activexcontrols` int(1) DEFAULT '0',
  `ismobiledevice` int(1) DEFAULT '0',
  `issyndicationreader` int(1) DEFAULT '0',
  `crawler` int(1) DEFAULT '0',
  `cssversion` int(2) DEFAULT '0',
  `aolversion` int(2) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pattern_key` (`mysql_pattern`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='SCS Browser'
*/
?>