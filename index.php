<?php 

require_once 'vendor/autoload.php';

$gh = new \Github\Client();
$oAuth = new GHUploader\oAuth();

if(isset($_POST['data'])) {
	$_SESSION['data'] = $_POST['data'];
}

if (isset($_GET['remote'])) {
	$_SESSION['remote'] = $_SERVER['REMOTE_ADDR'];
}

$oAuth->authenticate();

if (isset($_SESSION['remote']) && isset($_SESSION['oauth_token'])) {
	$ch = curl_init(getenv('REMOTE_SIGNUP_CALLBACK'));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('token' => $_SESSION['oauth_token'])));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	die;
}

$Repo = new GHUploader\Repo($gh);

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'create':
			$Repo->createRepo($_POST['repoName']);
			break;
		case 'upload':
			$commiter = array('name' => 'Relu-team', 'email' => 'info@relu.org');
			if (!isset($_SESSION['data'])) {
				die ('Nothing to upload!');
			}
			$data = json_decode($_SESSION['data']);

			foreach ($data as $index => $file) {
				$filePath = explode('/', $file->filePath);
				$fileName = array_pop($filePath);
				print('Uploading data to: '.$_POST['repoName'].' repository');
				try {
					$data = $gh->api('repo')->contents()->create($_SESSION['me'], $_POST['repoName'], 
	                     $file->filePath, base64_decode($file->fileContent), 'Uploaded by relu.org at '.date('Y-m-d H:i:s'), 
	                     'gh-pages', $commiter);
					print('Uploaded file:'. $fileName.' to: '.implode('/',$filePath).' with sha1: '.$data['content']['sha'].'<br>');
				} catch (Exception $e) {
					print ('Something went wrong while uploading file: '.$filename.' error message: '.$e->getMessage().'<br>');
				}
			}
			print('Finished uploading. Your page should be accessible in a shor while ');
			print('<a href="http://'.$_SESSION['me'].'.github.io/'.$_POST['repoName'].'">here</a>');
			print('<h1>Thanks for using <a href="http://relu.org">RELU</a></h1>');
			break;
		default:
			die('Go kill yourselve!');
	}
}

$repos = $Repo->getRepos();

$repoName = (isset($_POST['repoName']))? $Repo->freindlyName($_POST['repoName']): NULL;
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
