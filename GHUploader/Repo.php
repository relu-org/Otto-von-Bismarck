<?php

namespace GHUploader;

use Dotenv;
use Github;

class Repo {
	private $user;
	private $repos;
	private $gh;

	public function __construct($gh) {
		if ($_SESSION['oauth_token']) {
			$gh->authenticate($_SESSION['oauth_token'],Github\Client::AUTH_HTTP_TOKEN);
		} else {
			die('No unauthorised access');
		}
		$this->user = $gh->api('me')->show()['login'];
		$_SESSION['me'] = $this->user;
		$repos = $gh->api('user')->repositories($this->user);
		$repos = array_map(function ($repo) {
			return $repo['name'];
		}, $repos);
		$this->repos = $repos;
		$this->gh = $gh;
	}

	public function createRepo($name, $org = null) {
		$name = $this->friendlyName($name);
		try {
			$repos = $this->gh->api('repo')->create($name, $organization = $org);
			print('Creatied repository: '.$name.'<br>All you need to do now is clicking on the upload button');
			return $name;
		} catch (Exception $e) {
			print('Something went wrong. API returned message: '.$e->getMessage());
			return false;
		}
	}

	public function getRepos() {
		return $this->repos;
	}

	public function friendlyName($name) {
		$slugifier = new \Slug\Slugifier;
		return $slugifier->slugify($name);
	}
}