<?php
require_once "classes/mailform.php";
$registry=new registry_entry_class();
class registry_entry_class {
	var $iframe;
    var $checkbox;
    var $initial=FALSE;
    function scs_version() {
    	$results=array();
        $results['12/09/2024']="Add cpqdomain";
        $results['08/26/2024']="Reorder login options";
        $results['02/09/2024']="PHP 8 compliant";
        $results['04/18/2023']="Add MOTD";
        $results['10/07/2022']="Add email options to login";
        $results['09/15/2021']="Update login module";
        $results['04/20/2021']="Remove hotfix, mailform";
        $results['02/19/2021']="Add authorizenet";
        $results['08/21/2020']="Allow webmaster email for outside of domains";
        $results['08/21/2020']="Integrate address_class";
        $results['06/23/2020']="Support no scheme files";
        $results['11/04/2019']="Add hotfix";
        $results['07/18/2019']="Add cron";
        $results['01/28/2019']="Add login_delay";
        $results['01/23/2019']="Add recaptcha";
        $results['11/26/2018']="Drop eula";
        $results['01/16/2018']="Add eula";
        $results['11/14/2017']="Enhance paypal";
        $results['06/02/2017']="Add imap";
        $results['05/15/2017']="Add paypal";
        $results['04/14/2017']="Add email, phone, fax to address";
        $results['09/09/2016']="Add securenet";
        $results['05/25/2016']="Add body header, footer js";
        $results['01/09/2016']="Add shipping module";
        $results['05/16/2015']="Add company_website";
        $results['04/04/2015']="Add www->js_async, www->css_embed, www->http_cache";
        $results['03/08/2015']="Add GWT component";
        $results['05/27/2014']="Add www_blacklist_script";
        $results['04/11/2014']="email: domain now an array";
        $results['03/26/2014']="Add dashboard";
		$results['04/18/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct($iframe="") {
        global $database;
		$this->checkbox="div_show";
        $database->temp->meta->error_abort=FALSE;
    }
    function parse() {
		$results=array();
        foreach ($_POST as $key => $value) {
			list($field,$id)=explode("\t",base64_decode($key));
            $field=trim($field);
            $value=trim($value);
            if ((strlen($id)) && (strlen($field)) && (strlen($value))) $results[$field][$id]=$value;
        }
        return $results;
    }
    function message() {
        global $database, $forms;
		$database->registry->load();
    	$message="Registry Updated";
		if (sizeof($forms->error)) $message .= " (With " . (sizeof($forms->error)) . " Errors)";
		$forms->message[]=$message;
    }
    function fieldname($module,$field,$item="") {
    	$results="{$module}_{$field}";
        if ($item) $results .= "_{$item}";
        return $results;
    }
    function verify_file($module,$field,$type,$multiple=FALSE) {
        global $forms;
	    $data=explode("\n",$_POST[$field]);
	    $results=array();
        $errors=array();
	    foreach ($data as $file) {
	        $file=trim($file);
            if (!$file) continue;
            $url=parse_url($file);

			if (preg_match("/^(http:|https:|ftp:|)\/\//i",$file)) {
            	$verify_error="";
			  } else {
				$verify_error=verify_file($url['path'],$type);
			}
            switch (TRUE) {
              case ($verify_error):
				$errors[]=$verify_error;
                break;
              case (in_array($file,$results)):
				$errors[]="{$file} duplicate entry";
                break;
			}
            $results[]=$file;
	    }
        if (sizeof($errors)) $forms->error($field,implode("<br>",$errors),"",$module);
        if ($multiple) {
            return $results;
		  } else {
            return $results[0];
		}
    }
    function verify_duplicate($module,$field,$results=array()) {
        global $forms;
		if (!sizeof($results)) return;
		$results=array_count_values($results);
		foreach ($results as $key => $value) {
			if (($key != "") && ($value != 1)) $forms->error($field,"Duplicate entries found","",$module);
        }
    }
    function duplicate_header($title,$field="") {
        global $forms;
        if (($field) && ($forms->error_text[$field])) {
			$bg=$forms->foreground->red;
          } else {
			$bg="";
		}
        $results=array();
		$results[]="<tr $bg><td colspan=2><br><b>" . $title . "</b>";
        if (($field) && ($forms->error_text[$field])) $results[]=" " . $forms->error_text[$field];
		$results[]="</td></tr>\n";
		return implode("\n",$results);
    }
    function html($module_table, $title, $text, $module_version=0) {
        global $database, $forms;
		if (is_array($module_table)) {
			$module=$module_table[0];
            $table=$module_table[1];
        	$key=$module . "_" . $table;
		  } else {
			$module=$module_table;
            $table="";
          	$key=$module;
		}
        if (!$this->initial) {
			$this->initial=TRUE;
        	print $this->js();
		}
	    if ($module_version) $title .= " (" . date("m/d/Y",$module_version) . ")";
        $div_title="registry_title_" . $key;
        $div_content="registry_content_" . $key;
        $field="registry_check_" . $key;
        if ($forms->registry_error[$key]) $forms->error($field);
        switch (TRUE) {
          case ($forms->registry_error[$key]):
          case (!property_exists($database->registry, $module)):
          case ( (!$table) && (!property_exists($database->registry, $module)) ):
          case ( ($table) && (!property_exists($database->registry->{$module}, $table)) ):
		  case ( ($module_version) && (!property_exists($database->registry->{$module}, "module_version")) ):
		  case ( ($module_version) && ($module_version > $database->registry->{$module}->module_version) ):
            $style="block";
            $checked=TRUE;
            break;
          default:
            $style="none";
            $checked=FALSE;
        }
        if (is_array($text)) $text=implode("\n",$text);
		return
        	"<div id='$div_title'><br>" . $forms->checkbox($field,1,$checked,array("onclick"=>$this->checkbox . "('$div_content',this.checked);")) . " <b>$title</b></div>\n" .
            "<div id='$div_content' style='display:$style;'>\n" .
            "<table class='standard noborder'>\n" . $text . "</table>\n" .
            "</div>\n";
    }
    function cpqdomain($update=FALSE) {
	    global $database, $forms;
	    $module_version=strtotime("02/24/2025");
	    if ($update) {
            $database->registry->update(__FUNCTION__,"module_version",$module_version);
            $blacklist=array();
            $errors=array();
            $items=explode("\n", fn_text($_POST['cpqdomain_blacklist']));
            foreach ($items as $item) {
                $item=strtolower(trim($item));
                switch (TRUE) {
                  case (!strlen($item)):
                  case (in_array($item, $blacklist)):
                    break;
                  case (!verify_domain($item)):
                    if (!in_array($item, $errors)) $errors[]=$item;
                    break;
                  default:
                    $blacklist[]=$item;
                }
            }
            $database->registry->update(__FUNCTION__,"blacklist", $blacklist);
            $database->registry->update(__FUNCTION__,"mailform_confirm", $_POST['cpqdomain_mailform_confirm']);
            if (sizeof($errors)) $forms->error("cpqdomain_blacklist", "Errors: " . implode(", ", $errors), "", __FUNCTION__);
            $database->registry->update(__FUNCTION__,"folder",$_POST["cpqdomain_folder"]);
            $database->registry->update(__FUNCTION__,"approve_create", intval($_POST['cpqdomain_approve_create']));
            $database->registry->load(__FUNCTION__);
            $error=scs_verify_folder($database->registry->cpqdomain->folder,FALSE);
            if ($error) $forms->error("cpqdomain_folder",$error,"",__FUNCTION__);
          } else {
            $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Blacklist domain</td>";
            $text=array();
            if ($forms->error_text['cpqdomain_blacklist']) $text[]=$forms->error_text['cpqdomain_blacklist'];
            $text[]=$forms->textarea("cpqdomain_blacklist", $database->registry->cpqdomain->blacklist, 10, 60);
	        $results[]="<td width='70%'>" . implode("<br>", $text) . "</td>";
	        $results[]="</tr>";
            $results[]="<tr>";
	        $results[]="<td width='30%'>Domain confirm email document</td>";
            $text=array();
            $text[]=$database->mailform->select($database->registry->cpqdomain->mailform_confirm,array("field"=>"cpqdomain_mailform_confirm","internal"=>FALSE));
            if ($forms->error_text['cpqdomain_mailform_confirm']) $text[]=$forms->error_text['cpqdomain_mailform_confirm'];
            $results[]="<td width='70%'>" . implode("<br>", $text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Logo Folder</td>";
            $text=array($forms->text("cpqdomain_folder",$database->registry->cpqdomain->folder,60,0,"",array("placeholder"=>"Optional")));
            if ($forms->error_text['cpqdomain_folder']) $text[]=$forms->error_text['cpqdomain_folder'];
            $results[]="<td>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Create at user approval?</td>";
            $results[]="<td>" . $forms->checkbox("cpqdomain_approve_create", 1, $database->registry->cpqdomain->approve_create ) . "</td>";
            $results[]="</tr>";

            return $this->html(__FUNCTION__, "CPQ Domain", $results, $module_version);
        }
    }
    function cron($update=FALSE) {
	    global $database, $forms;
		$module_version=strtotime("07/18/2019");
	    if ($update) {
	        $database->registry->update("cron","module_version",$module_version);
	        $database->registry->update("cron","allowed",$_POST['cron_allowed']);
            $results=array();
            foreach (explode("\n",$_POST['cron_url']) as $text) {
            	$text=trim($text);
                if ( (!strlen($text)) && (in_array($text,$results)) ) continue;
                $results[]=$text;
                if (substr($text,0,1) != "/") $forms->error("cron_url","URL must begin with /","","cron");
            }
	        $database->registry->update("cron","url",$results);
		  } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Allowed?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cron_allowed",1,$database->registry->cron->allowed) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>HTTP Task URL</td>";
            $text=array($forms->textarea("cron_url",$database->registry->cron->url,3,60));
            if ($forms->error_text['cron_url']) $text[]=$forms->error_text['cron_url'];
	        $results[]="<td width='70%'>" . implode("<br>",$text)  . "</td>";
	        $results[]="</tr>";
            return $this->html("cron","CRON",$results,$module_version);
		}
	}
    function motd($update=FALSE) {
	    global $database, $forms;
		$module_version=strtotime("04/18/2023");
	    if ($update) {
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"text",$_POST['motd_text']);
	        $database->registry->update(__FUNCTION__,"style",$_POST['motd_style']);
		  } else {
          	$results=array();
	        $results[]="<tr>";
	        $results[]="<td>Message of the Day</td>";
	        $results[]="<td>" . $forms->textarea("motd_text",$database->registry->motd->text,4, 80) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Style</td>";
	        $results[]="<td>" . $forms->textarea("motd_style",$database->registry->motd->style,4, 80) . "</td>";
	        $results[]="</tr>";
	    	return $this->html(__FUNCTION__,"Message of the Day",$results,$module_version);
        }
	}
    function recaptcha($update=FALSE) {
	    global $database, $forms;
		$module_version=strtotime("01/23/2019");
        $score_array=array("1.0"=>"1.0: Do not use","0.9"=>"0.9: Human","0.8"=>"0.8","0.7"=>"0.7","0.6"=>"0.6","0.5"=>"0.5: Neutral","0.4"=>"0.4","0.3"=>"0.3","0.2"=>"0.2","0.1"=>"0.1: BOT","0.0"=>"0.0");
	    if ($update) {
	        $database->registry->update("recaptcha","module_version",$module_version);
	        $database->registry->update("recaptcha","version",$_POST['recaptcha_version']);
	        $database->registry->update("recaptcha","score_spam",$_POST['recaptcha_score_spam']);
	        $database->registry->update("recaptcha","score_ignore",$_POST['recaptcha_score_ignore']);
	        $database->registry->update("recaptcha","site_key",$_POST['recaptcha_site_key']);
            $database->registry->update("recaptcha","private_key",$_POST['recaptcha_private_key']);
	        $database->registry->update("recaptcha","log",$_POST['recaptcha_log']);
	        $database->registry->update("recaptcha","debug",$_POST['recaptcha_debug']);
	        $database->registry->update("recaptcha","user",$_POST['recaptcha_user']);
	        $database->registry->update("recaptcha","login",$_POST['recaptcha_login']);
	        $database->registry->update("recaptcha","myaccount",$_POST['recaptcha_myaccount']);
            if ( ($_POST['recaptcha_version']) && (!strlen($_POST['recaptcha_site_key'])) ) $forms->error('recaptcha_site_key',"","","recaptcha");
            if ( ($_POST['recaptcha_version']) && (!strlen($_POST['recaptcha_private_key'])) ) $forms->error('recaptcha_private_key',"","","recaptcha");
		  } else {
	        $results=array();
	        $results[]="<tr><td colspan=2>reCAPTCHA sends scores from 0.1 (BOT) to 0.9 (Human)</td></tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Version</td>";
	        $results[]="<td width='70%'>" . $forms->select("recaptcha_version",array("V3"),$database->registry->recaptcha->version) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Spam if score less than</td>";
	        $results[]="<td width='70%'>" . $forms->select("recaptcha_score_spam",$score_array,$database->registry->recaptcha->score_spam,FALSE) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Ignore if score less than</td>";
	        $results[]="<td width='70%'>" . $forms->select("recaptcha_score_ignore",$score_array,$database->registry->recaptcha->score_ignore,FALSE) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Site key</td>";
	        $results[]="<td width='70%'>" . $forms->text("recaptcha_site_key",$database->registry->recaptcha->site_key,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Private key</td>";
	        $results[]="<td width='70%'>" . $forms->text("recaptcha_private_key",$database->registry->recaptcha->private_key,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Log activity?</td>";
            $text=array($forms->checkbox("recaptcha_log",1,$database->registry->recaptcha->log));
            $text[]="(File: recaptcha.log)";
	        $results[]="<td width='70%'>" . implode(" ",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Log CURL?</td>";
            $text=array($forms->checkbox("recaptcha_debug",1,$database->registry->recaptcha->debug));
            $text[]="(File: recaptcha_curl.log)";
            $text[]="(Use only during initial installation)";
	        $results[]="<td width='70%'>" . implode(" ",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Login page?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("recaptcha_login",1,$database->registry->recaptcha->login) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>My account page?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("recaptcha_myaccount",1,$database->registry->recaptcha->myaccount) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Logged in users?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("recaptcha_user",1,$database->registry->recaptcha->user) . "</td>";
	        $results[]="</tr>";
            return $this->html("recaptcha","reCAPTCHA",$results,$module_version);
		}
	}
	function www($update=FALSE) {
	    global $database, $forms;
		$module_version=strtotime("02/05/2023");
		if ($update) {
	        $results=$this->parse();
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"title_prefix",$_POST['www_title_prefix']);
	        $database->registry->update(__FUNCTION__,"icon",$this->verify_file("www","www_icon",array("ico","png")));
	        $database->registry->update(__FUNCTION__,"css",$this->verify_file("www","www_css","css",TRUE));
	        $database->registry->update(__FUNCTION__,"js",$this->verify_file("www","www_js","js",TRUE));
	        $database->registry->update(__FUNCTION__,"js_body",$this->verify_file("www","www_js_body","js",TRUE));
	        $database->registry->update(__FUNCTION__,"js_footer",$this->verify_file("www","www_js_footer","js",TRUE));

            $js=array();
			$js_header=preg_split("/\n/",$_POST['www_js']);
			$js_body=preg_split("/\n/",$_POST['www_js_body']);
			$js_footer=preg_split("/\n/",$_POST['www_js_footer']);
            foreach ($js_header as $value) {
            	$value=trim($value);
                if (strlen($value)) $js[]=$value;
            }
            if (!array_key_exists("www_js_body",$forms->error_text)) {
            	$js_error=array();
	            foreach ($js_body as $value) {
	                $value=trim($value);
	                switch (TRUE) {
	                  case (!strlen($value)):
	                    break;
	                  case (!in_array($value,$js)):
	                    $js[]=$value;
	                    break;
	                  default:
                        $js_error[]=$value . " duplicate entry";
	                    break;
	                }
				}
                if (sizeof($js_error)) $forms->error("www_js_body",implode("<br>",$js_error),"","www");
            }
            if (!array_key_exists("www_js_footer",$forms->error_text)) {
            	$js_error=array();
	            foreach ($js_footer as $value) {
	                $value=trim($value);
	                switch (TRUE) {
	                  case (!strlen($value)):
	                    break;
	                  case (!in_array($value,$js)):
	                    $js[]=$value;
	                    break;
	                  default:
                        $js_error[]=$value . " duplicate entry";
	                    break;
	                }
				}
                if (sizeof($js_error)) $forms->error("www_js_footer",implode("<br>",$js_error),"","www");
            }

            $items=preg_split("/\n/",$_POST['www_meta']);
            $meta=array();
            foreach ($items as $item) {
				$item=trim($item);
                if ( (strlen($item)) && (!in_array($item,$meta)) ) $meta[]=$item;
            }
	        $database->registry->update("www","meta",$meta);
	        $database->registry->update("www","css_embed",$_POST['www_css_embed']);
	        $database->registry->update("www","js_async",$_POST['www_js_async']);
	        $database->registry->update("www","http_cache",$_POST['www_http_cache']);
	        $database->registry->update("www","http_cache_days",$_POST['www_http_cache_days']);
            switch (TRUE) {
              case ( ($_POST['www_http_cache']) && (!$_POST['www_http_cache_days']) ):
              case ( (!$_POST['www_http_cache']) && ($_POST['www_http_cache_days']) ):
				$forms->error("www_http_cache_days","","","www");
			}
	        $database->registry->update("www","jquery",$_POST['www_jquery']);
			$database->registry->update("www","blacklist_ip",$_POST['www_blacklist_ip']);
			$database->registry->update("www","blacklist_script",$_POST['www_blacklist_script']);
		} else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Meta title prefix</td>";
	        $results[]="<td width='70%'>" . $forms->text("www_title_prefix",$database->registry->www->title_prefix,60,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Favorite icon (.ico, .png)</td>";
	        $results[]="<td>" . $forms->text("www_icon",$database->registry->www->icon,60,60);
            if ($forms->error_text['www_icon']) $results[]="<br>" . $forms->error_text['www_icon'];
            $results[]= "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2>Meta tags";
	        $results[]="<br>" . $forms->textarea("www_meta",$database->registry->www->meta,0,100);
            if ($forms->error_text["www_meta"]) $results[]="<br>" . $forms->error_text["www_meta"];
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2>Cascading Style Sheet (.css)";
	        $results[]="<br>" . $forms->textarea("www_css",$database->registry->www->css,0,100);
            if ($forms->error_text["www_css"]) $results[]="<br>" . $forms->error_text["www_css"];
	        $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2>Javascript (html header) (.js)";
	        $results[]="<br>" . $forms->textarea("www_js",$database->registry->www->js,0,100);
            if ($forms->error_text["www_js"]) $results[]="<br>" . $forms->error_text["www_js"];
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2>Javascript (html body) (.js)";
	        $results[]="<br>" . $forms->textarea("www_js_body",$database->registry->www->js_body,0,100);
            if ($forms->error_text["www_js_body"]) $results[]="<br>" . $forms->error_text["www_js_body"];
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2>Javascript (html footer) (.js)";
	        $results[]="<br>" . $forms->textarea("www_js_footer",$database->registry->www->js_footer,0,100);
            if ($forms->error_text["www_js_footer"]) $results[]="<br>" . $forms->error_text["www_js_footer"];
            $results[]="</td>";
	        $results[]="</tr>";

            if (isset($database->blacklist)) {
	            $results[]="<tr><td colspan=2><br>Blacklist/Whitelist</td></tr>";
	            $results[]="<tr>";
	            $results[]="<td>Enforce blacklist/whitelist?</td>";
	            $results[]="<td>" . $forms->checkbox("www_blacklist_ip",1,$database->registry->www->blacklist_ip) . "</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td>Block on script injection?</td>";
	            $results[]="<td>" . $forms->checkbox("www_blacklist_script",1,$database->registry->www->blacklist_script) . "</td>";
	            $results[]="</tr>";
            }
	        $results[]="<tr>";
	        $results[]="<td>Embed CSS content in header?</td>";
	        $results[]="<td>" . $forms->checkbox("www_css_embed",1,$database->registry->www->css_embed) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Asynchronous Javascript?</td>";
	        $results[]="<td>" . $forms->checkbox("www_js_async",1,$database->registry->www->js_async);
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Automatic JQuery classes?</td>";
	        $results[]="<td>" . $forms->checkbox("www_jquery",1,$database->registry->www->jquery);
            $results[]="</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td>HTTP cache type:</td>";
	        $results[]="<td>" . $forms->select("www_http_cache",array("private","public"),$database->registry->www->http_cache) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>HTTP cache days</td>";
	        $results[]="<td>" . $forms->select("www_http_cache_days",array(0,1,2,3,4,5,6,7,30,90,360),$database->registry->www->http_cache_days,FALSE) . "</td>";
	        $results[]="</tr>";
            return $this->html("www","WWW Information",$results,$module_version);
		}
	}
	function company($update=FALSE) {
	    global $database,$forms;
		$module_version=strtotime("11/23/2024");
		if ($update) {
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"name",fn_text($_POST['company_name']));
	        $database->registry->update(__FUNCTION__,"address",fn_text($_POST['company_address']));
	        $database->registry->update(__FUNCTION__,"city_state_zip",fn_text($_POST['company_city_state_zip']));
	        $database->registry->update(__FUNCTION__,"country",fn_text($_POST['company_country']));
			$database->registry->update(__FUNCTION__,"website",fn_text($_POST['company_website']));
			$database->registry->update(__FUNCTION__,"phone",fn_text($_POST['company_phone']));
			$database->registry->update(__FUNCTION__,"tollfree",fn_text($_POST['company_tollfree']));
			$database->registry->update(__FUNCTION__,"mobile",fn_text($_POST['company_mobile']));
			$database->registry->update(__FUNCTION__,"fax",fn_text($_POST['company_fax']));
			$database->registry->update(__FUNCTION__,"logo",$this->verify_file(__FUNCTION__, "company_logo", "png"));
			$database->registry->load(__FUNCTION__);
            if (!strlen($database->registry->company->name)) $forms->error("company_name","Cannot be blank", "", __FUNCTION__);
	      } else {
	        $results=array();
	        $results[]="<td width='30%'>" . $forms->font("red","*","Company name") . "</td>";
	        $results[]="<td width='70%'>" . $forms->text("company_name",$database->registry->company->name,60,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Address</td>";
	        $results[]="<td>" . $forms->textarea("company_address",$database->registry->company->address,4,50) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>City, State Zip</td>";
	        $results[]="<td>" . $forms->text("company_city_state_zip",$database->registry->company->city_state_zip,60,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Country Name</td>";
	        $results[]="<td>" . $forms->text("company_country",$database->registry->company->country,40,40) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Website</td>";
	        $results[]="<td>" . $forms->text("company_website",$database->registry->company->website,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr><td colspan=2><br><b>Phone numbers</b></td></tr>";
	        $results[]="<tr>";
	        $results[]="<td>Phone number</td>";
	        $results[]="<td>" . $forms->text("company_phone",$database->registry->company->phone,20,20) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Toll Free</td>";
	        $results[]="<td>" . $forms->text("company_tollfree",$database->registry->company->tollfree,20,20) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Mobile</td>";
	        $results[]="<td>" . $forms->text("company_mobile",$database->registry->company->mobile,20,20) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>FAX</td>";
	        $results[]="<td>" . $forms->text("company_fax",$database->registry->company->fax,20,20) . "</td>";
	        $results[]="</tr>";
            $results[]="<tr>";
	        $results[]="<td>Logo (.png)</td>";
            $text=array($forms->text("company_logo",$database->registry->company->logo,60));
            if ($forms->error_text['company_logo']) $text[]=$forms->error_text['company_logo'];
	        $results[]="<td>" . implode("<br>", $text) . "</td>";
	        $results[]="</tr>";
            return $this->html("company","Company Information",$results,$module_version);
	    }
	}
	function login($update=FALSE) {
	    global $database,$forms;
		$module_version=strtotime("10/07/2022");
	    if ($update) {
	        $database->registry->update("login","module_version",$module_version);
	        $database->registry->update("login","max",$_POST['login_max']);
	        $database->registry->update("login","delay",$_POST['login_delay']);
	        $database->registry->update("login","pend",$_POST['login_pend']);
	        $database->registry->update("login","cookie",$_POST['login_cookie']);
            if (!$_POST['login_cookie']) $forms->error('login_cookie',"","","login");
	        $database->registry->update("login","auto",$_POST['login_auto']);
	        $database->registry->update("login","password_compare",$_POST['login_password_compare']);
	        $database->registry->update("login","password_generate",$_POST['login_password_generate']);
	        $database->registry->update("login","success",$_POST['login_success']);

            $items=array("forgot","approve","new");
			$mailform=array();
            foreach ($items as $item) {
            	$field="login_mailform_{$item}";
                switch (TRUE) {
                  case (!strlen($_POST[$field])):
                  	break;
	              case (in_array($_POST[$field], $mailform)):
	                $forms->error($field,"Duplicate Entry","",__FUNCTION__);
	                break;
				}
	            $mailform[$item]=$_POST[$field];
            }
	        $database->registry->update("login","mailform",$mailform);
	        $database->registry->update("password","encoding",$_POST['password_encoding']);
	        $database->registry->update("password","minimum",$_POST['password_minimum']);
	        $database->registry->update("password","maximum",$_POST['password_maximum']);
	        $database->registry->update("password","numeric",$_POST['password_numeric']);
	        $database->registry->update("password","uppercase",$_POST['password_uppercase']);
	        $database->registry->update("password","lowercase",$_POST['password_lowercase']);
	        $database->registry->update("password","punctuation",$_POST['password_punctuation']);
	        $database->registry->update("password","similar",$_POST['password_similar']);
	        $charset="";
	        if ($_POST['password_numeric']) $charset .= "0123456789";
	        if ($_POST['password_uppercase']) $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	        if ($_POST['password_lowercase']) $charset .= "abcdefghijklmnopqrstuvwxyz";
	        if ($_POST['password_punctuation']) $charset .= "~!@#$%^&*()_+`-={}[]|:;<>?";
	        if ($_POST['password_similar']) $charset=str_replace(array("!","|",":",";","_","-","0","1","O","I","o","l"),"",$charset);
	        $database->registry->update("password","charset",$charset);
	        $database->registry->update("password","reset_date",strtotime(fn_date($_POST['password_reset_date'],"mdy")));
	        $database->registry->update("password","expiry",$_POST['password_expiry']);
	        if (strlen($charset)) {
	            $js .=
	                "function random_password() {\n" .
	                "  valid_charset=\"" . $charset . "\";\n" .
	                "  var password='';\n" .
	                "  for (i=0; i < " . $_POST['password_maximum'] . "; i++) {\n" .
	                "    password += valid_charset.charAt(Math.floor(Math.random() * valid_charset.length));\n" .
	                "  }\n" .
	                "  document.getElementById('password').value=password;\n" .
	                "}\n";
	          } else {
	            $js="";
	        }
	        $database->registry->update("password","js",$js);
            if ((!isset($database->user->security)) || (in_array($database->user->security,array("","Both")))) {
	            if (!$_POST['password_minimum']) $forms->error('password_minimum',"","",__FUNCTION__);
	            if (intval($_POST['password_minimum']) > intval($_POST['password_maximum'])) $forms->error('password_maximum',"","",__FUNCTION__);
            }
	      } else {
			if (!property_exists($database->registry,"login")) $database->registry->login=new stdClass();
			if (!property_exists($database->registry->login,"mailform")) $database->registry->login->mailform=array();
			$mailform_customer=array();
            $mailform_internal=array();
            $database->mailform->query("select * from mailform where active=1 order by name");
            while ($database->mailform->fetch = $database->mailform->fetch_array() ) {
            	$database->mailform->fetch();
                if (strlen($database->mailform->data->recipient)) {
                	$mailform_internal[$database->mailform->data->code]=$database->mailform->data->name . " (" . $database->mailform->data->recipient . ")";
				  } else {
                	$mailform_customer[$database->mailform->data->code]=$database->mailform->data->name;
                }
            }
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Login attempts</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("login_max",$database->registry->login->max,0,10) . "</td>";
	        $results[]="</tr>";
            if (isset($database->user->data->login_delay)) {
	            $results[]="<tr>";
	            $results[]="<td width='30%'>Login delay after login attempts</td>";
	            $results[]="<td width='70%'>" . $forms->select("login_delay",array(0=>"Disable account",5=>"5 minutes",15=>"15 minutes",30=>"30 minutes",60=>"60 minutes"),$database->registry->login->delay,FALSE) . "</td>";
	            $results[]="</tr>";
            }
	        $results[]="<tr>";
	        $results[]="<td>Set new users as <b>pending?</b></td>";
	        $results[]="<td>" . $forms->checkbox("login_pend",1,$database->registry->login->pend) . "</td>";
	        $results[]="</tr>";
 	        $results[]="<tr>";
	        $results[]="<td>Cookie name</td>";
	        $results[]="<td>" . $forms->text("login_cookie",$database->registry->login->cookie,40) . "</td>";
	        $results[]="</tr>";
 	        $results[]="<tr>";
	        $results[]="<td>Successful login</td>";
            $options=array(""=>"Dashboard (if available)","return_url"=>"Referring page","home"=>"Home page");
	        $results[]="<td>" . $forms->select("login_success",$options,$database->registry->login->success,FALSE) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Auto login?</td>";
	        $results[]="<td>" . $forms->checkbox("login_auto",1,$database->registry->login->auto) . " (Automatic access if last successful login was from the same browser on the same desktop)</td>";
            $results[]="</tr>";
            if (sizeof($mailform_customer)) {
	            $results[]="<tr><td colspan=2><br>Email Documents Sent to Visitor</td></tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; Forgot password</td>";
	            $text=array( $forms->select("login_mailform_forgot",$mailform_customer,$database->registry->login->mailform['forgot']) );
	            if ($forms->error_text['login_mailform_forgot']) $text[]=$forms->error_text['login_mailform_forgot'];
                $results[]="<td>" . implode("<br>",$text) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; Access Approved</td>";
	            $text=array( $forms->select("login_mailform_approve",$mailform_customer,$database->registry->login->mailform['approve']) );
	            if ($forms->error_text['login_mailform_approve']) $text[]=$forms->error_text['login_mailform_approve'];
                $results[]="<td>" . implode("<br>",$text) . "</td>";
                $results[]="</tr>";
            }
            if (sizeof($mailform_internal)) {
	            $results[]="<tr><td colspan=2><br>Email Documents Sent to Us</td></tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; New user signup</td>";
	            $text=array( $forms->select("login_mailform_new",$mailform_internal,$database->registry->login->mailform['new']) );
	            if ($forms->error_text['login_mailform_new']) $text[]=$forms->error_text['login_mailform_new'];
                $results[]="<td>" . implode("<br>",$text) . "</td>";
                $results[]="</tr>";
            }
            $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
 	        $results[]="<tr>";
	        $results[]="<td>Password Encoding</td>";
	        $results[]="<td>" . $forms->select("password_encoding",array("SHA1","SHA2:256","SHA2:512","Legacy"),$database->registry->password->encoding,"Password (Deprecated)") . "</td>";
	        $results[]="</tr>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Password compare?</td>";
	        $results[]="<td>" . $forms->checkbox("login_password_compare",1,$database->registry->login->password_compare) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Password length</td>";
            $results[]="<td>Minimum: " . $forms->select_number("password_minimum",$database->registry->password->minimum,0,20);
            $results[]=" Maximum: " . $forms->select_number("password_maximum",$database->registry->password->maximum,0,20) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
	        $results[]="<td>Password generate?</td>";
	        $results[]="<td>" . $forms->checkbox("login_password_generate",1,$database->registry->login->password_generate, array("onclick"=>"login_password_generate_options();")) . "</td>";
            $results[]="</tr>";

            $results[]="<tr class=login_password_generate_tr>";
            $results[]="<td>&bull; Allow numbers?</td>";
            $results[]="<td>" . $forms->checkbox("password_numeric",1,$database->registry->password->numeric) . " (e.g. 012345)</td>";
            $results[]="</tr>";
            $results[]="<tr class=login_password_generate_tr>";
            $results[]="<td>&bull; Allow uppercase letters?</td>";
            $results[]="<td>" . $forms->checkbox("password_uppercase",1,$database->registry->password->uppercase) . " (e.g. ABCDEF)</td>";
            $results[]="</tr>";
            $results[]="<tr class=login_password_generate_tr>";
            $results[]="<td>&bull; Allow lowercase letters?</td>";
            $results[]="<td>" . $forms->checkbox("password_lowercase",1,$database->registry->password->lowercase) . " (e.g. abcdef)</td>";
            $results[]="</tr>";
            $results[]="<tr class=login_password_generate_tr>";
            $results[]="<td>&bull; Allow punctuation?</td>";
            $results[]="<td>" . $forms->checkbox("password_punctuation",1,$database->registry->password->punctuation) . " (e.g. !@#$%)</td>";
            $results[]="</tr>";
            $results[]="<tr class=login_password_generate_tr>";
            $results[]="<td>&bull; Prevent similar characters?</td>";
            $results[]="<td>" . $forms->checkbox("password_similar",1,$database->registry->password->similar) . " (e.g. i, l, o, 1, 0, I)</td>";
            $results[]="</tr>";
            if (isset($database->user->data->password_date)) {
	            $results[]="<tr>";
	            $results[]="<td>Password must be changed if set before</td>";
	            if ($database->registry->password->reset_date) {
	                $reset_date=date("Y/m/d",$database->registry->password->reset_date);
	              } else {
	                $reset_date="";
	            }
	            $results[]="<td>" . $forms->text("password_reset_date",$reset_date,10,10,"date") . "</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td>Password life</td>";
	            $results[]="<td>" . $forms->select("password_expiry",array(0,30,60,90,180,360),$database->registry->password->expiry,FALSE) . " days</td>";
	            $results[]="</tr>";
			}
            $results[]=$this->login_js();
            return $this->html(__FUNCTION__,"Login & Security",$results,$module_version);
	    }
    }

        function login_js() {
            return <<< EOT

<script>

jQuery(document).ready(function() {
    login_password_generate_options();
});

/* Never implemented
document.onreadystatechange = function() {
console.log(document.readyState);
    if (document.readyState == "complete") {
        jquery_found=(typeof jQuery === 'function') ? true : false;
    }
}
*/
function login_password_generate_options() {
    switch (true) {
      case (jQuery('#login_password_generate').is(':checked')):
        jQuery(".login_password_generate_tr").show();
        break;
      default:
        jQuery(".login_password_generate_tr").hide();
    }
}
</script>

EOT;
	}

	function email($update=FALSE) {
	    global $database,$forms;
		$module_version=strtotime("08/21/2020");
	    if ($update) {
	        $database->registry->update("email","module_version",$module_version);
			$domain_list=preg_split("/\n/",$_POST['email_domain']);
            $domain=array();
            $domain_error=array();
            foreach ($domain_list as $item) {
				$item=trim(strtolower($item));
				if (!$item) continue;
                switch (TRUE) {
				  case (preg_match("/[ \/\\\\,?!:;@#]/",$item)):
                  case (sizeof(preg_split("/\./",$item)) < 2):
                	$domain_error[]="Incorrect domain format: $item";
                    break;
                  case (in_array($item,$domain)):
                	$domain_error[]="Duplicate entry: $item";
					break;
				}
                $domain[]=$item;
            }
            if (!sizeof($domain)) $domain_error[]="Cannot be blank";
			$database->registry->update("email","domain",$domain);
	        if (sizeof($domain_error)) $forms->error('email_domain',$domain_error,'',"email");
            $base_href=strtolower(trim($_POST['email_base_href']));
            $base_href_parse=parse_url($base_href);
            switch (TRUE) {
              case (!$base_href):
              	break;
              case (!$base_href_parse['scheme']):
              case (!$base_href_parse['host']):
				$forms->error('email_base_href',"Must be format https://www.domain",'','email');
                break;
              default:
				$base_href=$base_href_parse['scheme'] . "://" . $base_href_parse['host'];
            }
	        $database->registry->update("email","base_href",$base_href);
			$database->registry->update("email","css",$this->verify_file("email","email_css","css"));
			$email_webmaster=new email_class($_POST['email_webmaster'],TRUE,( ($_POST['email_webmaster'] != $_POST['email_username']) ? $domain : array()));
	        $database->registry->update("email","webmaster",$email_webmaster->address);
	        if ($email_webmaster->error) $forms->error('email_webmaster',$email_webmaster->error,'','email');
	        $database->registry->update("email","webmaster_name",$_POST['email_webmaster_name']);
	        $database->registry->update("email","host",$_POST['email_host']);
	        $database->registry->update("email","secure",$_POST['email_secure']);
	        $database->registry->update("email","smtp_port",intval($_POST['email_smtp_port']));
	        $database->registry->update("email","authentication",$_POST['email_authentication']);
	        $database->registry->update("email","username",$_POST['email_username']);
	        $database->registry->update("email","password",$_POST['email_password']);
            $database->registry->load("email");
            if (($database->registry->email->secure) && (!$database->registry->email->smtp_port)) $forms->error("email_smtp_port","Secure port must be specified","",'email');
			if ($database->registry->email->authentication) {
            	if (!$database->registry->email->username) $forms->error("email_username","Required if authentication selected","",'email');
				if (!$database->registry->email->password) $forms->error("email_password","Required if authentication selected","",'password');
			}
	      } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>" . $forms->font("red","*","Domain name(s)") . "</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("email_domain",$database->registry->email->domain,3,60);
            if ($forms->error_text["email_domain"]) $results[]="<br>" . implode("<br>",$forms->error_text["email_domain"]);
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Base href (https://domain.com)</td>";
	        $results[]="<td>" . $forms->text("email_base_href",$database->registry->email->base_href,60,60);
            if ($forms->error_text["email_base_href"]) $results[]="<br>" . $forms->error_text["email_base_href"];
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>CSS</td>";
	        $results[]="<td>" . $forms->text("email_css",$database->registry->email->css,60,60);
            if ($forms->error_text["email_css"])  $results[]="<br>" . $forms->error_text["email_css"];
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>" . $forms->font("red","*","Webmaster email") . "</td>";
            $text=array($forms->text("email_webmaster",$database->registry->email->webmaster,60,60));
            if ($forms->error_text["email_webmaster"]) $text[]=$forms->error_text["email_webmaster"];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Webmaster name</td>";
	        $results[]="<td>" . $forms->text("email_webmaster_name",$database->registry->email->webmaster_name,60,60) . "</td>";
	        $results[]="</tr>";
            $results[]="<tr><td colspan=2><br><b>Hosting fields</b></td></tr>";
	        $results[]="<tr>";
	        $results[]="<td>Host</td>";
            $text=array($forms->text("email_host",$database->registry->email->host,60));
            if ($forms->error_text["email_host"]) $text[]=$forms->error_text["email_host"];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Secure?</td>";
	        $results[]="<td>" . $forms->select("email_secure",array("tls","ssl"),$database->registry->email->secure) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>SMTP Port# (Default: 25)</td>";
            $text=array($forms->text("email_smtp_port",$database->registry->email->smtp_port,5,5));
            if ($forms->error_text["email_smtp_port"]) $text[]=$forms->error_text["email_smtp_port"];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>SMTP Authentication?</td>";
	        $results[]="<td>" . $forms->checkbox("email_authentication",1,$database->registry->email->authentication) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Username</td>";
            $text=array($forms->text("email_username",$database->registry->email->username,60));
            if ($forms->error_text["email_username"]) $text[]=$forms->error_text["email_username"];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Password</td>";
            $text=array($forms->text("email_password",$database->registry->email->password,60));
            if ($forms->error_text["email_password"]) $text[]=$forms->error_text["email_password"];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
            $results[]="<tr><td><b>Typical settings</b></td>";
            $results[]="<td>";
			$results[]="<p class=standard><b>Email is hosted by web site (native interface)</b>";
            $results[]="<br>Host: blank";
            $results[]="<br>All other hosting fields are ignored";
            $results[]="</p>";
			$results[]="<p class=standard><b>Email is hosted by web site (authentication required)</b>";
            $results[]="<br>Host: localhost";
            $results[]="<br>Secure: None";
            $results[]="<br>Port: 25";
            $results[]="<br>Authentication, user name & password: Required";
            $results[]="</p>";
			$results[]="<p class=standard><b>SMTP mail client</b>";
            $results[]="<br>Host: &lt;Your SMTP mail server&gt;";
            $results[]="<br>Secure: None";
            $results[]="<br>Port: 25";
            $results[]="<br>Authentication, user name & password: Required";
            $results[]="</p>";
			$results[]="<p class=standard><b>GMail</b>";
            $results[]="<br>Host: aspmx.l.google.com";
            $results[]="<br>Port 25";
            $results[]="<br>Secure Host: smtp.gmail.com";
            $results[]="<br>Port 465 (ssl)";
            $results[]="<br>Port 587 (tls)";
            $results[]="<br>IMAP Host: imap.gmail.com";
            $results[]="<br>Port 993 (tls/ssl)";
            $results[]="<br>Authentication, user name & password: Required";
            $results[]="</p>";
            $results[]="</td>";
            $results[]="</tr>";
            return $this->html("email","Email",$results,$module_version);
	    }
	}
    function address($update,$table,$title="") {
	    global $database,$forms;
		if (!$table) return;
        if (!$title) $title=$table;
		$address=new address_class($table, new stdClass(),array("create"=>TRUE));
        if ($table=="user") unset($address->fields['email']);
		$address_options=array(""=>"","allowed"=>"Allowed","required"=>"Required");
	    if ($update) {
			$results=array();
            foreach ($address->fields as $key => $value) {
                $field=$this->fieldname("address",$table,$key);
                if ($_POST[$field]) $results[$key]=$_POST[$field];
            }
	        $database->registry->update("address",$table,$results);
	      } else {
	        $results=array();
            foreach ($address->fields as $key => $value) {
	            $results[]="<tr>";
	            $results[]="<td width='30%'>{$value}?</td>";
                $field=$this->fieldname("address",$table,$key);
	            $results[]="<td width='70%'>" . $forms->select($field,$address_options,$database->registry->address->{$table}[$key],FALSE) . "</td>";
	            $results[]="</tr>";
            }
	        return $this->html(array("address",$table),"Address Fields (Table: {$title})",$results);
	    }
    }
	function upload($update=FALSE) {
	    global $database,$forms;
	    if ($update) {
            $filetype=array_unique(explode(" ",strtolower(trim($_POST['upload_filetype']))));
            sort($filetype);
	        $database->registry->update("upload","filetype",$filetype);
	        $database->registry->update("upload","folder",$_POST['upload_folder']);
            $error=scs_verify_folder($_POST['upload_folder'],FALSE);
            if (sizeof($filetype) && (!$_POST['upload_folder']) && (!$error)) $error="Upload folder must be specified";
            if ($error) $forms->error('upload_folder',$error,"","upload");
		  } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Allowed file types<br>(Space separated)</td>";
            if (isset($database->registry->upload->filetype)) {
            	$text=implode(" ",$database->registry->upload->filetype);
			  } else {
              	$text="";
            }
	        $results[]="<td width='70%'>" . $forms->textarea("upload_filetype",$text,3,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Folder name</td>";
	        $results[]="<td>" . $forms->text("upload_folder",$database->registry->upload->folder,60,60);
            if ($forms->error_text["upload_folder"]) $results[]="<br>" . $forms->error_text["upload_folder"];
            $results[]="</td>";
	        $results[]="</tr>";
            return $this->html("upload","User File Upload",$results);
	    }
    }
    function files($update=FALSE,$file_list=array(),$title="File Locations",$module="files") {
        global $database,$forms;
		switch (TRUE) {
          case (!is_array($file_list)):
          case (!sizeof($file_list)):
          case (!$module):
			scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
         	die;
		}
        if ($update) {
            foreach ($file_list as $field => $item) {
				$fieldname=$this->fieldname($module,$field);
				$filename=trim($_POST[$fieldname]);
				$filename_error=scs_verify_folder($filename,FALSE);
				$database->registry->update($module,$field,$filename);
            	if ($filename_error) $forms->error($fieldname,$filename_error,"",$module);
            }
		  } else {
	        $results=array();
            foreach ($file_list as $field => $item) {
	            $fieldname=$this->fieldname($module,$field);
	            $results[]="<tr>";
	            $results[]="<td width='30%'>$item</td>";
	            $results[]="<td width='70%'>" . $forms->text($fieldname,$database->registry->{$module}->{$field},40,80);
	            if ($forms->error_text[$fieldname]) $results[]="<br><b>" . $forms->error_text[$fieldname] . "</b>";
	            $results[]="</td>";
	            $results[]="</tr>";
            }
            return $this->html($module,$title,$results);
        }
    }
	function cms($update=FALSE) {
	    global $database,$forms;
		$folders=array("image"=>"Images","image_backup"=>"Backup Images","file"=>"Files");
    	$cms_frame=array(""=>"No","files"=>"No, only files","numeric"=>"Yes, numeric frames","alpha"=>"Yes, alphanumeric frames");
	    if ($update) {
			$database->registry->update("cms","frame",$_POST['cms_frame']);
            foreach ($folders as $key => $value) {
            	$field="cms_{$key}";
                if (!$_POST['cms_frame']) {
                	$data="";
				  } else {
					$data=trim($_POST[$field]);
				}
				$database->registry->update("cms",$key,$data);
	            switch (TRUE) {
                  case (!$_POST['cms_frame']):
	              case (!$data):
                  	break;
	              case (!is_dir($data)):
	                $forms->error($field,"Invalid folder name");
	                break;
				  case (substr($data,-1,1) != "/"):
	                $forms->error($field,"Folder names must end with /");
                  	break;
	            }
                $database->registry->update("cms","width",$_POST['cms_width']);
                if ($forms->error[$field]) $forms->registry_error['cms']=TRUE;
            }
	      } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Is CMS Installed?</td>";
	        $results[]="<td width='70%'>" . $forms->select("cms_frame",$cms_frame,$database->registry->cms->frame,FALSE) . "</td>";
	        $results[]="</tr>";
            if ($database->registry->cms->frame) {
	            foreach ($folders as $key => $value) {
	                $results[]="<tr>";
	                $results[]="<td>$value folder</td>";
                    $field="cms_{$key}";
	                $results[]="<td>" . $forms->text($field,$database->registry->cms->{$key},40,40) . " <b>" . $forms->error_text[$field] . "</b></td>";
	                $results[]="</tr>";
	            }
                $results[]=
                	"<tr>" .
                    "<td>Content editor width</td>" .
                    "<td>" . $forms->select_number("cms_width",$database->registry->cms->width,40,120) . "</td>" .
                    "</tr>";
			}
            return $this->html("cms","Content Management System",$results);
	    }
	}
    function cc($update=FALSE) {
    require_once "classes/cc.php";
        global $database,$forms;
		$module_version=strtotime("06/12/2014");
		$cc=new cc_class();
		if ($update) {
	        $database->registry->update("cc","module_version",$module_version);
			$data=array();
            $count=0;
			foreach ($cc->constant->card_list as $card => $item) {
				$field=$this->fieldname("cc","card",$count);
                if (isset($_POST[$field])) $data[$count]=$card;
                $count++;
			}
			$database->registry->update("cc","card",$data);
		  } else {
			$results=array();
            if (!isset($database->registry->cc->card)) $database->registry->cc->card=array();
            $count=0;
			foreach ($cc->constant->card_list as $card => $item) {
                $field=$this->fieldname("cc","card",$count);
                $results[]=
                    "<tr>" .
                    "<td width='30%'>" . $card . "?</td>" .
                    "<td width='70%'>" . $forms->checkbox($field,1,in_array($card,$database->registry->cc->card)) . "</td>" .
                    "</tr>";
				$count++;
            }
			return $this->html("cc","Credit Card Information",$results,$module_version);
        }
    }
    function shipping($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("01/21/2016");
	    if ($update) {
	        $database->registry->update("shipping","module_version",$module_version);
	        $database->registry->update("shipping","name",$_POST['shipping_name']);
	        $database->registry->update("shipping","address",$_POST['shipping_address']);
	        $database->registry->update("shipping","city",$_POST['shipping_city']);
	        $database->registry->update("shipping","state",$_POST['shipping_state']);
	        $database->registry->update("shipping","zip",$_POST['shipping_zip']);
	        $database->registry->update("shipping","country_code","US");
	        $database->registry->update("shipping","phone",$_POST['shipping_phone']);
	        $database->registry->update("shipping","days",$_POST['shipping_days']);
	        $database->registry->update("shipping","tare",fn_number($_POST['shipping_tare'],2));
            $data=array();
            if ($_POST['shipping_ups_access']) {
	            $data['access']=$_POST['shipping_ups_access'];
	            $data['account']=trim($_POST['shipping_ups_account']);
                if (!strlen($data['account'])) $forms->error('shipping_ups_account',"","","shipping");
	            $data['license']=trim($_POST['shipping_ups_license']);
                if (!strlen($data['license'])) $forms->error('shipping_ups_license',"","","shipping");
	            $data['userid']=trim($_POST['shipping_ups_userid']);
                if (!strlen($data['userid'])) $forms->error('shipping_ups_userid',"","","shipping");
	            $data['password']=trim($_POST['shipping_ups_password']);
                if (!strlen($data['password'])) $forms->error('shipping_ups_password',"","","shipping");
	            $data['handling']=fn_number($_POST['shipping_ups_handling'],2);
			}
	        $database->registry->update("shipping","ups",$data,$module_version);
	      } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td colspan=2><b>Warehouse Information</b></td>";
	        $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Name</td>";
            $results[]="<td width='70%'>" . $forms->text("shipping_name",$database->registry->shipping->name,30,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Address</td>";
            $results[]="<td>" . $forms->text("shipping_address",$database->registry->shipping->address,30,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>City</td>";
            $results[]="<td>" . $forms->text("shipping_city",$database->registry->shipping->city,30,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>State</td>";
            $results[]="<td>" . $database->country->select("US",$database->registry->shipping->state,"shipping_state") . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Zip Code</td>";
            $results[]="<td>" . $forms->text("shipping_zip",$database->registry->shipping->zip,10,10) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Phone</td>";
            $results[]="<td>" . $forms->text("shipping_phone",$database->registry->shipping->phone,20,20) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Days to ship</td>";
            $results[]="<td>" . $forms->select_number("shipping_days",$database->registry->shipping->days,0,10) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Packing weight (tare)</td>";
            $results[]="<td>" . $forms->text("shipping_tare",$database->registry->shipping->tare,12,12,"decimal:2") . "</td>";
            $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td colspan=2><br><b>United Parcel Service API</b></td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Access</td>";
	        $results[]="<td>" . $forms->select("shipping_ups_access",array("test"=>"Testing","production"=>"Production"),$database->registry->shipping->ups['access']) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>UPS Account#</td>";
	        $results[]="<td>" . $forms->text("shipping_ups_account",$database->registry->shipping->ups['account'],6,6) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Access License#</td>";
	        $results[]="<td>" . $forms->text("shipping_ups_license",$database->registry->shipping->ups['license'],30,30) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>User name</td>";
	        $results[]="<td>" . $forms->text("shipping_ups_userid",$database->registry->shipping->ups['userid'],30,30) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Password</td>";
	        $results[]="<td>" . $forms->text("shipping_ups_password",$database->registry->shipping->ups['password'],30,30) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Handling charge</td>";
	        $results[]="<td>$" . $forms->text("shipping_ups_handling",$database->registry->shipping->ups['handling'],12,12,"decimal:2") . "</td>";
	        $results[]="</tr>";
	        return $this->html("shipping","Shipping",$results,$module_version);
	    }
	}

    function authorizenet($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("02/18/2021");
        $environment=array("production"=>"Production","sandbox"=>"Sandbox");
	    if ($update) {
	        $database->registry->update("authorizenet","module_version",$module_version);
	        $database->registry->update("authorizenet","environment",$_POST['authorizenet_environment']);
            foreach ($environment as $code => $name) {
				$data=array();
                $data['login_id']=trim($_POST["authorizenet_{$code}_login_id"]);
                $data['transaction_key']=trim($_POST["authorizenet_{$code}_transaction_key"]);
                $database->registry->update("authorizenet",$code,$data);
            }
	        $database->registry->update("authorizenet","description",$_POST['authorizenet_description']);
	        $database->registry->update("authorizenet","cvv_mandatory",$_POST['authorizenet_cvv_mandatory']);
	        $database->registry->update("authorizenet","debug",$_POST['authorizenet_debug']);
            $database->registry->load("authorizenet");
            foreach ($environment as $code => $name) {
                if ($code == $database->registry->authorizenet->environment) {
                	if (!strlen($database->registry->authorizenet->$code['login_id'])) $forms->error("authorizenet_{$code}_login_id","Cannot be blank for default environment","",__FUNCTION__);
                	if (!strlen($database->registry->authorizenet->$code['transaction_key'])) $forms->error("authorizenet_{$code}_transaction_key","Cannot be blank for default environment","",__FUNCTION__);
				}
            }
	      } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Environment</td>";
            $results[]="<td width='70%'>" . $forms->select("authorizenet_environment",$environment,$database->registry->authorizenet->environment) . "</td>";
            $results[]="</tr>";
            foreach ($environment as $code => $name) {
            	$results[]="<tr><td colspan=2>Environment: <b>{$name}</b></td></tr>";
	            $results[]="<tr>";
	            $results[]="<td><li>API Login ID</li></td>";
                $field="authorizenet_{$code}_login_id";
                $text=array($forms->text($field,$database->registry->authorizenet->$code['login_id'],40,40));
                if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
	            $results[]="<td>" . implode("<br>",$text) . "</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td><li>Transaction Key</li></td>";
                $field="authorizenet_{$code}_transaction_key";
                $text=array($forms->text($field,$database->registry->authorizenet->$code['transaction_key'],40,40));
                if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
	            $results[]="<td>" . implode("<br>",$text) . "</td>";
	            $results[]="</tr>";
            }
            $results[]="<tr>";
            $results[]="<td>CVV Mandatory?</td>";
            $results[]="<td>" . $forms->checkbox("authorizenet_cvv_mandatory",1,$database->registry->authorizenet->cvv_mandatory) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Default description</td>";
            $results[]="<td>" . $forms->text("authorizenet_description",$database->registry->authorizenet->description,20,20) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Debug CURL?</td>";
            $results[]="<td>" . $forms->checkbox("authorizenet_debug",1,$database->registry->authorizenet->debug) . "</td>";
            $results[]="</tr>";
	        return $this->html("authorizenet","Authorize.net",$results,$module_version);
	    }
    }
    function payments_securenet($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("04/08/2019");
	    if ($update) {
	        $database->registry->update("payments","module_version",$module_version);
			$data=array();
            $data['environment']=$_POST['securenet_environment'];
            if ($data['environment']) {
				$data['production']=trim($_POST['securenet_production']);
                if (!strlen($data['production'])) $forms->error('securenet_production',"","","payments");
				$data['sandbox']=trim($_POST['securenet_sandbox']);
                if (!strlen($data['sandbox'])) $forms->error('securenet_sandbox',"","","payments");
				$data['merchant_id']=trim($_POST['securenet_merchant_id']);
                if (!strlen($data['merchant_id'])) $forms->error('securenet_merchant_id',"","","payments");
				$data['password']=trim($_POST['securenet_password']);
                if (!strlen($data['password'])) $forms->error('securenet_password',"","","payments");
				$data['developer_id']=trim($_POST['securenet_developer_id']);
                if (!strlen($data['developer_id'])) $forms->error('securenet_developer_id',"","","payments");
				$data['version']=trim($_POST['securenet_version']);
                if (!strlen($data['version'])) $forms->error('securenet_version',"","","payments");
				$data['debug']=intval($_POST['securenet_debug']);
            }
	        $database->registry->update("payments","securenet",$data);
	      } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Environment</td>";
            $results[]="<td width='70%'>" . $forms->select("securenet_environment",array("sandbox"=>"Sandbox","production"=>"Production"),$database->registry->payments->securenet['environment']) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Production URL</td>";
            $results[]="<td>" . $forms->text("securenet_production",$database->registry->payments->securenet['production'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Sendbox URL</td>";
            $results[]="<td>" . $forms->text("securenet_sandbox",$database->registry->payments->securenet['sandbox'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Merchant ID</td>";
            $results[]="<td>" . $forms->text("securenet_merchant_id",$database->registry->payments->securenet['merchant_id'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Password</td>";
            $results[]="<td>" . $forms->text("securenet_password",$database->registry->payments->securenet['password'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Developer ID</td>";
            $results[]="<td>" . $forms->text("securenet_developer_id",$database->registry->payments->securenet['developer_id'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Version</td>";
            $results[]="<td>" . $forms->text("securenet_version",$database->registry->payments->securenet['version'],12,12) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>CURL Debug?</td>";
            $results[]="<td>" . $forms->checkbox("securenet_debug",1,$database->registry->payments->securenet['debug']) . "</td>";
            $results[]="</tr>";
	        return $this->html("payments","<a href='https://www.worldpay.us/' target='_blank'>WoldPay (SecureNet)</a>",$results,$module_version);
	    }
    }
    function paypal($update=FALSE) {
        global $database,$forms;
    	$server=array("sandbox"=>"Sandbox","production"=>"Production");
		$module_version=strtotime("11/14/2017");
	    if ($update) {
	        $database->registry->update("paypal","module_version",$module_version);
	        $database->registry->update("paypal","server",$_POST['paypal_server']);
            foreach ($server as $item => $value) {
            	$data=array();
                $data['url']=trim($_POST["paypal_{$item}_url"]);
                $data['email']=trim($_POST["paypal_{$item}_email"]);
                $data['client_id']=trim($_POST["paypal_{$item}_client_id"]);
                $data['secret']=trim($_POST["paypal_{$item}_secret"]);
                $data['token']=trim($_POST["paypal_{$item}_token"]);
                $data['token_expires']=strtotime($_POST["paypal_{$item}_token_expires"]);
	        	$database->registry->update("paypal",$item,$data);
            }
            $database->registry->load("paypal");
            foreach ($server as $item => $name) {
	            if ( (!$database->registry->paypal->{$item}['url']) && ($item != $database->registry->paypal->server) ) continue;
                $email=new email_class($database->registry->paypal->{$item}['email'],TRUE);
				if ($email->error) $forms->error("paypal_{$item}_email",$email->error,"","paypal");
            	if (!$database->registry->paypal->{$item}['url']) $forms->error("paypal_{$item}_url","","","paypal");
            	if (!$database->registry->paypal->{$item}['client_id']) $forms->error("paypal_{$item}_client_id","","","paypal");
	            if (!$database->registry->paypal->{$item}['secret']) $forms->error("paypal_{$item}_secret","","","paypal");
			}
		  } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Environment</td>";
            $results[]="<td width='70%'>" . $forms->select("paypal_server",$server,$database->registry->paypal->server,FALSE) . "</td>";
            $results[]="</tr>";
            foreach ($server as $item => $name) {
            	$results[]="<tr><td colspan=2><b>{$name} Server</td></tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; URL</td>";
                $results[]="<td>" . $forms->text("paypal_{$item}_url",$database->registry->paypal->{$item}['url'],80) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; Email</td>";
                $text=array($forms->text("paypal_{$item}_email",$database->registry->paypal->{$item}['email'],60,60));
                if ($forms->error_text["paypal_{$item}_email"]) $text[]="<b>" . $forms->error_text["paypal_{$item}_email"] . "</b>";
                $results[]="<td>" . implode(" ",$text) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; API Client ID</td>";
                $results[]="<td>" . $forms->text("paypal_{$item}_client_id",$database->registry->paypal->{$item}['client_id'],80) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; API Secret</td>";
                $results[]="<td>" . $forms->text("paypal_{$item}_secret",$database->registry->paypal->{$item}['secret'],80) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; Token</td>";
                $results[]="<td>" . $forms->text("paypal_{$item}_token",$database->registry->paypal->{$item}['token'],60) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>&bull; Token Expires</td>";
                $results[]="<td>" . $forms->text("paypal_{$item}_token_expires",( ($database->registry->paypal->{$item}['token_expires']) ? date("m/d/Y H:i",$database->registry->paypal->{$item}['token_expires']) :""),30) . "</td>";
                $results[]="</tr>";
            }
	        return $this->html("paypal","Paypal",$results,$module_version);
		}
	}
    function imap($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("07/01/2019");
	    if ($update) {
	        $database->registry->update("imap","module_version",$module_version);
	        $database->registry->update("imap","server",$_POST['imap_server']);
	        $database->registry->update("imap","mailbox",$_POST['imap_mailbox']);
	        $database->registry->update("imap","sent",$_POST['imap_sent']);
	        $database->registry->update("imap","email",$_POST['imap_email']);
	        $database->registry->update("imap","password",$_POST['imap_password']);
            $database->registry->load("imap");
            if ($database->registry->imap->server) {
            	if (!strlen($database->registry->imap->mailbox)) $forms->error("imap_mailbox","","","imap");
            	if (!strlen($database->registry->imap->email)) $forms->error("imap_email","","","imap");
            	if (!strlen($database->registry->imap->password)) $forms->error("imap_password","","","imap");
			}
		  } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Server</td>";
            $results[]="<td width='70%'>" . $forms->text("imap_server",$database->registry->imap->server,80) . "</td>";
            $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Mailbox</td>";
	        $results[]="<td>" . $forms->text("imap_mailbox",$database->registry->imap->mailbox,80) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Mailbox (Sent)</td>";
	        $results[]="<td>" . $forms->text("imap_sent",$database->registry->imap->sent,80) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<tr>";
	        $results[]="<td>Email</td>";
	        $results[]="<td>" . $forms->text("imap_email",$database->registry->imap->email,80) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<tr>";
	        $results[]="<td>Password</td>";
	        $results[]="<td>" . $forms->text("imap_password",$database->registry->imap->password,80) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
            $text=array();
			$text[]="<b>Google/Gmail</b>";
            $text[]=" Server: imap.gmail.com:993/imap/ssl";
            $text[]=" Server: imap.gmail.com:993/imap/ssl/novalidate-cert";
            $text[]=" Mailbox: INBOX";
            $text[]=" Mailbox (Sent): [Gmail]/Sent Mail";
            $results[]="<tr><td colspan=2>" . implode("<br>",$text) . "</td></tr>";
	        return $this->html("imap","IMAP",$results,$module_version);
		}
	}
    function payments($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("01/21/2016");
	    if ($update) {
	        $database->registry->update("payments","module_version",$module_version);
			$data=array();
            $data['environment']=$_POST['braintree_environment'];
            if ($data['environment']) {
				$data['merchant_id']=trim($_POST['braintree_merchant_id']);
                if (!strlen($data['merchant_id'])) $forms->error('braintree_merchant_id',"","","payments");
				$data['public_key']=trim($_POST['braintree_public_key']);
                if (!strlen($data['public_key'])) $forms->error('braintree_public_key',"","","payments");
				$data['private_key']=trim($_POST['braintree_private_key']);
                if (!strlen($data['private_key'])) $forms->error('braintree_private_key',"","","payments");
            }
	        $database->registry->update("payments","braintree",$data);
	      } else {
	        $results=array();
            $results[]="<tr><td colspan=2><a href='https://www.braintreepayments.com/' target='_blank'>Braintree</a></td></tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Environment</td>";
            $results[]="<td width='70%'>" . $forms->select("braintree_environment",array("sandbox"=>"Sandbox","production"=>"Production"),$database->registry->payments->braintree['environment']) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Merchant ID</td>";
            $results[]="<td>" . $forms->text("braintree_merchant_id",$database->registry->payments->braintree['merchant_id'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Public Key</td>";
            $results[]="<td>" . $forms->text("braintree_public_key",$database->registry->payments->braintree['public_key'],60,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Private Key</td>";
            $results[]="<td>" . $forms->text("braintree_private_key",$database->registry->payments->braintree['private_key'],60,60) . "</td>";
            $results[]="</tr>";
	        return $this->html("payments","Payment Processing",$results,$module_version);
	    }
	}
    function gwt($update=FALSE) {
        global $database,$forms;
    	if (!class_exists("gwtscs_class")) return $this->html("gwt","Google Webmaster Tools: Undefined gwtscs_class","");
		$gwt=new gwtscs_class();
		if ($update) {
			switch ($_POST['gwt_status']) {
			  case "disabled":
              case "reset":
				$database->registry->delete("gwt");
				$database->registry->update("gwt","status","disabled");
                return;
			}
 			$database->registry->update("gwt","status",$_POST['gwt_status']);
	        $email=new email_class($_POST['gwt_user'],TRUE);
 			$database->registry->update("gwt","user",$email->address);
	        if ($email->error) $forms->error('gwt_user',$email->error,"","gwt");
            $password=trim($_POST['gwt_password']);
 			$database->registry->update("gwt","password",$password);
            if (!strlen($password)) $forms->error('gwt_password',"","","gwt");
            $color=array();
            foreach ($gwt->color as $color_code => $color_name) {
				$field="gwt_color_" . $color_code;
				$color[$color_code]=$_POST[$field];
                if (!$_POST[$field]) $forms->error($field,"","","gwt");
            }
 			$database->registry->update("gwt","color",$color);
            foreach ($gwt->type as $type => $type_name ) {
				$options=array();
                $options['poll']=intval($_POST["gwt_{$type}_poll"]);
                if ($options['poll']) {
					$options['date']=fn_date($_POST["gwt_{$type}_date"],"mdy");
                    switch (TRUE) {
                      case (!$forms->date_ok):
                      case ($options['date'] < date("Y/m/d",strtotime("-30 days"))):
                      case ($options['date'] > date("Y/m/d",strtotime("-2 days"))):
						$forms->error("gwt_{$type}_date","","","gwt");
					}
                    if ($database->registry->gwt->{$type}['next']) {
                    	$options['next']=($database->registry->gwt->{$type}['next']);
					  } else {
                    	$options['next']=fn_next_poll();
					}
				}
				$database->registry->update("gwt",$type,$options);
			}
			$gwt->login($email->address,$password);
			if ( (!array_key_exists("gwt_user",$forms->error)) && (!$gwt->login) ) $forms->error('gwt_user',"Invalid GWT user name / password","","gwt");
 			$database->registry->update("gwt","domain",$_POST['gwt_domain']);
            if ( (!$_POST['gwt_domain']) || (!in_array($_POST['gwt_domain'],$gwt->domain)) ) $forms->error('gwt_domain',"","","gwt");
		  } else {
			if (!isset($database->registry->gwt)) $database->registry->gwt=new stdClass();
			$gwt->login();
			$results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Status</td>";
	        $results[]="<td width='70%'>" . $forms->select("gwt_status",array("disabled"=>"Disabled","enabled"=>"Enabled","reset"=>"Reset"),$database->registry->gwt->status,FALSE) . "</td>";
	        $results[]="</tr>";
	        $results[]="<td>User Name</td>";
	        $results[]="<td>" . $forms->text("gwt_user",$database->registry->gwt->user,60);
            if (array_key_exists("gwt_user",$forms->error_text)) $results[]="<br><b>" . $forms->error_text['gwt_user'] . "</b>";
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Password</td>";
	        $results[]="<td>" . $forms->text("gwt_password",$database->registry->gwt->password,60) . "</td>";
	        $results[]="</tr>";
	        if (sizeof($gwt->domain)) {
	            $results[]="<tr>";
	            $results[]="<td>Domain</td>";
	            $results[]="<td>" . $forms->select("gwt_domain",$gwt->domain,$database->registry->gwt->domain,TRUE) . "</td>";
	            $results[]="</tr>";
	        }
            $color_list=array();
            foreach ($forms->background as $color => $value) {
				$color_list[]=$color;
            }
	        $results[]="<tr>";
	        $results[]="<td colspan=2><br>Background Colors</td>";
	        $results[]="</tr>";
            foreach ($gwt->color as $color_code => $color_name) {
	            $results[]="<tr>";
	            $results[]="<td>" . $forms->span($color_name,array("id"=>"gwt_color_{$color_code}_text","style"=>"display: inline-block; width: 150px; background-color: " . $database->registry->gwt->color[$color_code] . ";")) . "</td>";
	            $results[]="<td>" . $forms->select("gwt_color_{$color_code}",$color_list,$database->registry->gwt->color[$color_code],TRUE,array("onchange"=>"document.getElementById(this.id + '_text').style.backgroundColor=this.value;")) . "</td>\n";
	            $results[]="</tr>";
            }
            foreach ($gwt->type as $type => $type_name ) {
				if (!isset($database->registry->gwt->$type)) $database->registry->gwt->{$type}=array();
	            $results[]="<tr>";
	            $results[]="<td colspan=2><br>Polling: $type_name</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td>Poll Enabled?</td>";
	            $results[]="<td>" . $forms->checkbox("gwt_{$type}_poll",1,$database->registry->gwt->{$type}['poll']) . "</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td>Thru Date</td>";
	            $results[]="<td>" . $forms->text("gwt_{$type}_date",$database->registry->gwt->{$type}['date'],10,10,"date:ymd") . " (" . date("m/d/Y",strtotime("-30 days")) . " - " . date("m/d/Y",strtotime("-2 days")) . ")" . "</td>";
	            $results[]="</tr>";
                if ($database->registry->gwt->{$type}['next']) {
	                $results[]="<tr>";
	                $results[]="<td>Next Poll</td>";
	                $results[]="<td>" . $database->registry->gwt->{$type}['next'] . "</td>";
	                $results[]="</tr>";
				}
            }
            return $this->html("gwt","Google Webmaster Tools (GWT)",$results,$module_version);
		}
    }
    function watermark($update,$options) {
	    global $database,$forms;
	    $location=array(
	        1=>"Top / Left",
	        2=>"Top / Center",
	        3=>"Top / Right",
	        4=>"Middle / Left",
	        5=>"Middle / Center",
	        6=>"Middle / Right",
	        7=>"Bottom / Left",
	        8=>"Bottom / Center",
	        9=>"Bottom / Right");
	    $opacity=array(
	        25=>"25%",
	        50=>"50%",
	        75=>"75%",
	        100=>"100%");
		if ($update) {
			$results=array();
			foreach ($_POST as $key => $value) {
				list($module,$field,$item)=explode("\t",base64_decode($key));
				if ($module != "watermark") continue;
                if (!array_key_exists($field,$results)) $results[$field]=array();
                $results[$field][$item]=trim($value);
            }
			foreach ($results as $code => $item) {
				if ($options[$code]->location) $item['location']=$options[$code]->location;
				if ($options[$code]->opacity) $item['opacity']=$options[$code]->opacity;
				$field=base64_encode("watermark" . "\t" . $code . "\t" . "url");
				$error=verify_file($item['url'],$options['type'],$options['required']);
                switch (TRUE) {
                  case ($error):
					$forms->error($field,$error,'','watermark');
                    break;
            	  case (!$item['url']):
					$item=array();
					break;
				  default:
                  	$imagesize=getimagesize($_SERVER['DOCUMENT_ROOT'] . $item['url']);
					switch (TRUE) {
					  case (!$imagesize):
				  		$forms->error($field,"Not an image",'','watermark');
                        break;
					  case ( ($options[$code]->width) && ($imagesize[0] != $options[$code]->width) ):
				  		$forms->error($field,"Mandatory width: " . $options[$code]->width,'','watermark');
                        break;
					  case ( ($options[$code]->height) && ($imagesize[1] != $options[$code]->height) ):
				  		$forms->error($field,"Mandatory height: " . $options[$code]->height,'','watermark');
                        break;
                    }
                }
				$database->registry->update("watermark",$code,$item);
			}
          } else {
            $results=array();
			if (!isset($database->registry->watermark)) $database->registry->watermark=new stdClass();
			foreach ($options as $code => $item) {
				$text=$item->name;
                if (!isset($database->registry->watermark->{$code})) $database->registry->watermark->{$code}=array();
                if ($item->required) $text .= " (Required)";
	            $results[]="<tr>";
	            $results[]="<td colspan=2><br>$text</td>";
	            $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td width='30%'>Image URL</td>";
				$field=base64_encode("watermark" . "\t" . $code . "\t" . "url");
                $results[]="<td width='70%'>" . $forms->text($field,$database->registry->watermark->{$code}['url'],60);
                if (array_key_exists($field,$forms->error_text)) $results[]="<br>" . $forms->error_text[$field];
                $resutls[]="</td>";
	            $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>Opacity</td>";
                if (!$item->opacity) {
                	$text=$forms->select(base64_encode("watermark" . "\t" . $code . "\t" . "opacity"),$opacity,$database->registry->watermark->{$code}['opacity'],FALSE);
				  } else {
                  	$text="<b>" . $opacity[$item->opacity] . "</b>";
				}
                $results[]="<td>$text</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>Location</td>";
                if (!$item->location) {
                	$text=$forms->select(base64_encode("watermark" . "\t" . $code . "\t" . "location"),$location,$database->registry->watermark->{$code}['location'],FALSE);
				  } else {
                  	$text="<b>" . $location[$item->location] . "</b>";
				}
                $results[]="<td>$text</td>";
                $results[]="</tr>";
            }
        return $this->html("watermark","Watermarks",$results,$module_version);
        }
    }
    function dashboard($update=FALSE) {
        global $database,$dashboard,$forms;
		switch (TRUE) {
          case (!isset($dashboard)):
			break;
		  case ($update):
	        foreach ($dashboard->module as $module => $module_content) {
				$data=array();
				foreach ($module_content->item as $item => $item_content) {
					$field="dashboard_" . $module . "_" . $item;
                    if ($_POST[$field]) {
                    	$data[$item]=$_POST[$field];
	                    foreach ($item_content->fields as $field => $field_name) {
	                        $data[$item . "_" . $field]=trim($_POST["dashboard_" . $module . "_" . $item . "_" . $field]);
	                    }
                    }
                }
				$database->registry->update("dashboard",$module,$data);
			}
			break;
		  default:
          	$results=array();
	        foreach ($dashboard->module as $module => $module_content) {
				foreach ($module_content->item as $item => $item_content) {
                	$results[]="<tr><td colspan=2>" . $item_content->name . "</td></tr>";
	                $user_type=$database->user->constant->type;
	                if ($item_content->access) {
						foreach ($user_type as $key => $value) {
							if ($value == $item_content->access) {
                            	break;
							  } else {
								array_shift($user_type);
							}
                        }
	                }
                	$results[]="<tr>";
                	$results[]="<td width='30%'>Minimum access (blank to deny)</td>";
                	$results[]="<td width='70%'>" . $forms->select("dashboard_" . $module . "_" . $item,$user_type,$database->registry->dashboard->{$module}[$item]) . "</td>";
                	$results[]="</tr>";
					foreach ($item_content->fields as $field => $field_name) {
	                    $results[]="<tr>";
	                    $results[]="<td width='30%'>$field_name</td>";
	                    $results[]="<td width='70%'>" . $forms->select_number("dashboard_" . $module . "_" . $item . "_" . $field,$database->registry->dashboard->{$module}[$item . "_" . $field],0,99) . "</td>";
	                    $results[]="</tr>";
                     }
                	$results[]="<tr><td colspan=2>&nbsp;</td></tr>";
				}
	        }
			return $this->html("dashboard","Dashboard",$results);
		}
    }
    function js() {
        return <<< EOT

<script>
if (typeof div_show != 'function') {
    function div_show(div,show) {
        switch (true) {
            case (!document.getElementById(div)):
            return;
            case (!show):
            document.getElementById(div).style.display='none';
            break;
            default:
            document.getElementById(div).style.display='block';
        }
    }
}
if (typeof scs_content_resize != 'function') {
    function scs_content_resize() {
        div=document.getElementById('scs_content_div');
        if (!div) return;
        iframe=parent.document.getElementById('scs_content_iframe');
        if (iframe) iframe.height=div.offsetHeight + 30;
    }
}
</script>
EOT;
    }
}
class registry_watermark_class {
	var $name;
    var $required=FALSE;
    var $type="gif";
	var $location=0;
    var $opacity=0;
    var $width=0;
    var $height=0;
    function __construct($name,$options=array()) {
		$this->name=$name;
        foreach ($this as $key => $value) {
			if ( ($key != "name") && (array_key_exists($key,$options)) ) $this->{$key}=$options[$key];
        }
    }
}
?>