<?php 

require_once 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required('OAUTH_APP_ID', 'OAUTH_APP_SECRET');

define('OAUTH_APP_ID', getenv('OAUTH_APP_ID'));
define('OAUTH_APP_SECRET', getenv('OAUTH_APP_SECRET'));

session_start();

if(isset($_POST['data'])) {
	$_SESSION['data'] = $_POST['data'];
}

if(isset($_GET['code'])) {
  if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {
    die('Oops...');
  }
  $args = array(
    'client_id' => OAUTH_APP_ID,
    'client_secret' => OAUTH_APP_SECRET,
    'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'state' => $_SESSION['state'],
    'code' => $_GET['code']
  );

  $ch = curl_init('https://github.com/login/oauth/access_token');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
  $headers[] = 'Accept: application/json';
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  $response = json_decode($response);
  $_SESSION['oauth_token'] = $response->access_token;
  header('Location: ' . $_SERVER['PHP_SELF']);
} else if (!isset($_SESSION['oauth_token'])) {
	$_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
	$args = array(
	  'client_id' => OAUTH_APP_ID,
	  'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
	  'scope' => 'user,repo',
	  'state' => $_SESSION['state']
	);
	// Redirect the user to Github's authorization page
	// print('<pre>'); var_dump($args); print('</pre>');die;
	header('Location: https://github.com/login/oauth/authorize?' . http_build_query($args));
	die();
} 

$gh = new \Github\Client();
if ($_SESSION['oauth_token']) {
	$gh->authenticate($_SESSION['oauth_token'],Github\Client::AUTH_HTTP_TOKEN);
}

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'create':
			$repos = $gh->api('repo')->create($_POST['repoName']);
			print('Creatied repository: '.$_POST['repoName'].'<br>All you need to do now is clicking on the upload button');
			break;
		case 'upload':
			$commiter = array('name' => 'Relu-team', 'email' => 'info@relu.org');
			if (!isset($_SESSION['data'])) {
				die ('Nothing to upload!');
			}
			$data = json_decode($_SESSION['data']);

			foreach ($data as $index => $file) {
				$filePath = explode('/', $file->filePath);
				print('Uploading data to: '.$_POST['repoName'].' repository');
				$data = $gh->api('repo')->contents()->create($_SESSION['me'], $_POST['repoName'], 
                     $file->filePath, base64_decode($file->fileContent), 'Uploaded by relu.org at '.date('Y-m-d H:i:s'), 
                     'gh-pages', $commiter);
				print('Uploaded file:'. array_pop($filePath).' to: '.implode('/',$filePath).' with sha1: '.$data['content']['sha'].'<br>');
			}
			print('Finished uploading. Your page should be accessible in a shor while ');
			print('<a href="http://'.$_SESSION['me'].'.github.io/'.$_POST['repoName'].'">here</a>');
			print('<h1>Thanks for using <a href="http://relu.org">RELU</a></h1>');
			break;
		default:
			die('Go kill yourselve!');
	}
}

$me = $gh->api('me')->show()['login'];
$_SESSION['me'] = $me;
$repos = $gh->api('user')->repositories($me);
$repos = array_map(function ($repo) {
	return $repo['name'];
}, $repos);

$repoName = (isset($_POST['repoName']))? $_POST['repoName']: NULL;
?>
<h1>Select one of your's repositories</h1>
<form method="POST" action="?action=upload">
	<select name="repoName" id="repoName">
		<?php foreach ($repos as $repo): ?>
			<?php $selected = ($repoName==$repo)? 'selected="selected"':''; ?>
			<option value="<?php print($repo); ?>" <?php print($selected); ?>>
				<?php print($repo); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<button>Upload!</button>
</form>
<h1>or create new one</h1>
<form method="POST" action="?action=create">
	<input type="text" name="repoName" id="newRepoName">
	<button>Create!</button>
</form>