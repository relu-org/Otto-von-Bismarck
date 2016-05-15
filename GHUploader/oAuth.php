<?php 

namespace GHUploader;

use Dotenv;

class oAuth {
	private $appId;
	private $appSecret;
	private $codeUri;
	private $tokenUri;
	private $rootPath = __DIR__.'/../';
	private $rootUrl;
	private $notifyUrl;

	public function __construct($remote = false) {
		$this->makeReqiure();
		if (session_id() == '' || !isset($_SESSION)) {
			session_start();
		}

		if ($remote) {
			$_SESSION['remote'] = true;
		}

		$this->appId = getenv('OAUTH_APP_ID');
		$this->appSecret = getenv('OAUTH_APP_SECRET');
		$this->codeUri = (getenv('OAUTH_CODE_URI')? getenv('OAUTH_CODE_URI'):'https://github.com/login/oauth/authorize');
		$this->tokenUri = (getenv('OAUTH_TOKEN_URI')? getenv('OAUTH_TOKEN_URI'):'https://github.com/login/oauth/access_token');
		$this->rootUrl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		$this->notifyUrl = (getenv('REMOTE_SIGNUP_CALLBACK'))?getenv('REMOTE_SIGNUP_CALLBACK'):'';

		if ($this->isRemote()) {
			$this->remoteNotify();
		}
	}

	public function authenticate() {
		if(isset($_GET['code'])) {
		  if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {
		    die('Oops...');
		  }
		  $args = array(
		    'client_id' => $this->appId,
		    'client_secret' => $this->appSecret,
		    'redirect_uri' => $this->rootUrl,
		    'state' => $_SESSION['state'],
		    'code' => $_GET['code']
		  );
		  $response = $this->doRequest($args);
		  $_SESSION['oauth_token'] = $response->access_token;
		  header('Location: ' . $this->rootUrl);
		} else if (!isset($_SESSION['oauth_token'])) {
			$_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
			$args = array(
			  'client_id' => $this->appId,
			  'redirect_uri' => $this->rootUrl,
			  'scope' => 'user,repo',
			  'state' => $_SESSION['state']
			);
			header('Location: '.$this->codeUri.'?'.http_build_query($args));
			die();
		} 
	}

	private function doRequest($args) {
		$ch = curl_init($this->tokenUri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
		$headers[] = 'Accept: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		return json_decode(curl_exec($ch));
	}

	private function makeReqiure() {
		$dotenv = new Dotenv\Dotenv($this->rootPath);
		$dotenv->load();
		$dotenv->required('OAUTH_APP_ID', 'OAUTH_APP_SECRET');
	}

	private function isRemote() {
		if (isset($_SESSION['remote'])) {
			return true;
		} else {
			return false;
		}
	}

	private function remoteNotify() {
		if(isset($_SESSION['oauth_token'])) {
			$ch = curl_init($this->notifyUrl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('token' => $_SESSION['oauth_token'])));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			die;
		}
	}
}