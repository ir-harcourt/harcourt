<?php
if ($_SERVER['SERVER_ADMIN'] != "host@suburbancomputer.com") {
	ini_set("display_errors", "1");
	ini_set("error_log", $_SERVER['DOCUMENT_ROOT'] . "/error.log");
}
$path=array( get_include_path() );
$path[]=$_SERVER['DOCUMENT_ROOT'] . "/includes";
$path[]=$_SERVER['DOCUMENT_ROOT'] . "/scsps";
$path[]=$_SERVER['DOCUMENT_ROOT'] . "/legacy";
$path[]=$_SERVER['DOCUMENT_ROOT'] . ( (phpversion() < "8") ? "/composer7" : "/composer8");
$path[]="c:/www/library/composer7";
set_include_path(implode(PATH_SEPARATOR,$path));

require_once "functions.php";
require_once "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use Greew\OAuth2\Client\Provider\Azure;

$obj=new local_class();
class local_class {
    function __construct() {


        $this->clientId="f0eb6110-c8e6-41bf-9b0d-c9e9ecd1a277";
        $this->clientSecret="jse8Q~D9mt3P4gm~0mxROxABCBWlylio.J.UfagG";
        $this->tenantId="ed0d9149-b05b-4f4b-9e5f-31e22439e482";
        $this->refreshToken="";
        $this->email="oauthtest@harcourt.co";

//        $this->test();
        $this->email();
    }
    function test() {
//https://stackoverflow.com/questions/74886433/php-connect-office365-mailboxoauth-with-refresh-token


$url= "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

$param_post_curl=array();
$param_post_curl['client_id']=$this->clientId;
$param_post_curl['client_secret']=$this->clientSecret;
$param_post_curl['refresh_token']="";
$param_post_curl['grant_type']='refresh_token';
//$param_post_curl['login_hint']=$this->email;
fn_pre($param_post_curl);

$headers=array();

$ch=curl_init();
curl_setopt($ch,CURLOPT_URL,$url);

$headers[]="Content-Type: application/x-www-form-urlencoded";
curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($param_post_curl));

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch,CURLOPT_POST, 1);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

$oResult=curl_exec($ch);
fn_pre(json_decode($oResult),1);


    }
    function email() {
        $provider=new Azure([
            "clientId" => $this->clientId,
            "clientSecret" => $this->clientSecret,
            "tenantId" => $this->tenantId
        ]);
//fn_pre($provider,1);
//fn_pre($provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']),1);


        $mail=new PHPMailer();
        $mail->IsSMTP(true);
    //    $mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to SMTP::DEBUG_SERVER for detailed error output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Set to SMTP::DEBUG_SERVER for detailed error output
        $mail->Host = 'smtp.office365.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS on port 587
        $mail->SMTPAuth = true;
        $mail->AuthType = 'XOAUTH2'; // Specify OAUTH2 authentication

        $mail->setOAuth(new OAuth([
            'provider'     => $provider,
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'refreshToken' => $this->refreshToken,
            'userName'     => $this->email
        ]));

        $mail->setFrom($this->email, 'Harcourt Webmaster');
        $mail->addAddress("tom@suburbancomputer.com", 'Thomas Hayes');

        $mail->Subject = 'PHPMailer Office 365 OAuth2 Test';
        $mail->Body    = 'This is a test email using <b>Office 365 SMTP</b> and PHPMailer with OAuth2.';
        $mail->AltBody = 'This is the plain text version of the email.';
        $mail->IsHTML(TRUE);
        $mail->send();

        print "<p>{$mail->ErrorInfo}</p>";


    }
}

/*
Username: oauthtest@harcourt.co
Password: 2I0*$#ZJ1Q4wA0C0v^!#

Client: f0eb6110-c8e6-41bf-9b0d-c9e9ecd1a277
Tenant: ed0d9149-b05b-4f4b-9e5f-31e22439e482
Secret: jse8Q~D9mt3P4gm~0mxROxABCBWlylio.J.UfagG
*/
?>