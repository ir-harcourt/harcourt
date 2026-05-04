<?php
// https://developers.google.com/recaptcha/docs/v3
class recaptcha_class {
    var $action;
    var $recaptcha=FALSE;
    var $spam=0;
    var $ignore=0;
    var $score=1.0;
    var $comment;
    var $response;
    var $local=FALSE;
    var $options;
    var $error;
	function scs_table_version() {
        $results['08/04/2022']="Enhance V3";
        $results['12/17/2020']="Add options to body_button";
        $results['03/18/2020']="Add function submitForm";
        $results['01/23/2019']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct($action, $required=TRUE, $options=array()) {
    global $database;
        if (!$database->registry->recaptcha->version) return;
        $this->action=$action;
        $this->recaptcha=$required;
        $this->options=(is_array($options)) ? $options : array();
        $this->local=($options['local']) ? $options['local'] : FALSE;
    }
    function js() {
	global $database;
    	if (!$this->recaptcha) return;
        $results=array();
        $results[]="<script src='https://www.google.com/recaptcha/api.js'></script>";
        if (!$this->local) {
        	$text=array("");
            $text[]="function recaptcha_script(token) {";
            $text[]="    document.scs_form.submit();";
            $text[]="}";
            $text[]="";
        	$results[]="<script>" . implode("\n",$text) . "</script>";
		}
        return implode("\n",$results);
	}
    function button($text,$options=array()) {
    global $database, $forms;
		if (!$this->recaptcha) return $forms->submit($text,$options);
	    $class=array("g-recaptcha");
        $items=(is_array($options['class'])) ? $options['class'] : array($options['class']);
        foreach ($items as $item) {
            $item=trim($item);
            if ( (strlen($item)) && (!in_array($item,$class)) ) $class[]=$item;
        }
    	$options['class']=$class;
        $options['id']="recaptcha_button";
        switch (TRUE) {
          case (!array_key_exists("data",$options)):
            $options['data']=array();
            break;
          case (!is_array($options['data'])):
            $options['data']=array($options['data']);
            break;
        }
        $options['data']['data-sitekey']=$database->registry->recaptcha->site_key;
        $options['data']['data-callback']="recaptcha_script";
        $options['data']['data-action']=$this->action;
		return $forms->button($text,$options);
    }
    function score() {
	global $database;
		$this->token=(!func_num_args()) ? $_POST['g-recaptcha-response'] : func_get_arg(0);
    	switch (TRUE) {
          case (!$this->recaptcha):
          	return TRUE;
          case (!strlen($this->token)):
            $this->spam=TRUE;
            $this->ignore=TRUE;
            $this->score=0.0;
            return FALSE;
		}
        $data=array();
        $data['secret']=$database->registry->recaptcha->private_key;
        $data['response']=$this->token;
        $data['remoteip']=$_SERVER['REMOTE_ADDR'];
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($database->registry->recaptcha->debug) {
            $fh=fopen($_SERVER['DOCUMENT_ROOT'] . "/recaptcha_curl.log","w");
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $fh);
        }
        $response=curl_exec($ch);
        if ($database->registry->recaptcha->debug) fclose($fh);
		if ($database->registry->recaptcha->log) file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/recaptcha.log",  $_SERVER['REMOTE_ADDR'] . "\n" . $response . "\n", FILE_APPEND );
		if (curl_error($ch)) {
        	$this->response=new stdClass();
    		$this->error=curl_error($ch);
		  } else {
        	$this->response=json_decode($response);
		}
        curl_close($ch);
        switch (TRUE) {
          case (strlen($this->error)):
          	break;
          case (!isset($this->response->score)):
          	$this->error="Internal error";
            break;
          case (array_key_exists("score",$this->options)):
          	$this->score=number_format(floatval($this->options['score']),1);
          	break;
          default:
          	$this->score=number_format(floatval($this->response->score),1);
		}
        switch (TRUE) {
          case (strlen($this->error)):
          	break;
          case ($this->response->action != $this->action):
            $this->spam=TRUE;
            $this->ignore=TRUE;
          	$this->comment="Action expected: " . $this->action . " received: " . $this->response->action;
            break;
          case ($this->score < $database->registry->recaptcha->score_spam):
            $this->spam=TRUE;
            if (number_format(floatval($this->score),1) < number_format(floatval($database->registry->recaptcha->score_ignore),1)) $this->ignore=TRUE;
          	$this->comment="Score " . $this->score . " less than " . $database->registry->recaptcha->score_ignore;
            break;
		}
    }
    function comment() {
		return (!$this->recaptcha) ? "" : "reCAPTCH score: " . $this->score;
    }
}
?>