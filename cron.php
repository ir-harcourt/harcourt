<?php
// /usr/local/bin/php /home/harcou6/stage.harcourt.co/cron.php cad_toolbox
$obj=new local_class();
class local_class {
    var $action_list=array("cad_toolbox","image_filmstrip","search_utility");
    var $api_key="67bb9773abba2e7561c2b16719afd67e493d2b92";
    var $home=array();
    function scs_table_version() {
        $results=array();
        $results['10/01/2025']="New url login";
        $results['09/29/2025']="Initial release";
        return $results;
    }
    function __construct() {
        set_time_limit(300);
        $this->home=array_filter(explode("/", $_SERVER['PHP_SELF']));
        array_pop($this->home);
        if ( (!sizeof($this->home)) || ($_SERVER['argc'] != "2") ) die();
        $this->starttime=microtime(true);
        $this->action=$_SERVER['argv'][1];
        $message=$this->process();
        $this->endtime=microtime(true);
        $this->logfile="/" . implode("/", $this->home) . "/scscpq_cron.log";
        if (!file_exists($this->logfile)) {
            $fh=fopen($this->logfile, "w");
            $results=array("Action","Start", "Seconds", "Response", "URL");
            fputcsv($fh, $results, "\t");
          } else {
            $fh=fopen($this->logfile, "a");
        }
        $results=array();
        $results[]=$this->action;
        $results[]=date("m/d/Y H:i:s");
        $results[]=number_format($this->endtime - $this->starttime, 4);
        $results[]=$message;
        $results[]=$this->url;
        $results[]=
        fputcsv($fh, $results, "\t");
        fclose($fh);
    }
    function process() {
        if (!in_array($this->action, $this->action_list)) return "Invalid action";
        $url=array("https://");
        $url[]=(end($this->home) == "public_html") ? "harcourt.co" : "stage.harcourt.co";
        $url[]="/{$this->action}.php";
        $this->url=implode("", $url);
        $ch=curl_init("{$this->url}?api_key={$this->api_key}");
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $results=curl_exec($ch);
        $httpcode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error=curl_error($ch);
        curl_close($ch);
        return ($httpcode == "200") ? $results : "HTTP Code: {$httpcode} CURL Error: {$error}";
    }
}