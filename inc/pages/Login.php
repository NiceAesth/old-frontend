<?php

class Login
{
	const PageID = 2;
	const URL = 'login';
	const Title = 'SAP - Login';
	const LoggedIn = false;
	public $mh_POST = ['u', 'p'];

	public function P()
	{
		clir(true, 'index.php?p=1&e=1');
		// Print login form
		echo '
		<div class="peers ai-s fxw-nw h-100vh">
		<div class="d-n@sm- peer peer-greed h-100 pos-r bgr-n bgpX-c bgpY-c bgsz-cv" style=\'background-image: url("assets/static/images/bg.jpg")\'>
		  <div class="pos-a centerXY">
			<div class="bgc-white bdrs-50p pos-r" style=\'width: 120px; height: 120px;\'>
			  <img class="pos-a centerXY" src="assets/static/images/logo.png" alt="">
			</div>
		  </div>
		</div>
		<div class="col-12 col-md-4 peer pX-40 pY-80 h-100 bgc-white scrollable pos-r" style=\'min-width: 320px;\'>
		  <h4 class="fw-300 c-grey-900 mB-40">Login</h4>
		  <form action="submit.php" method="POST">
		  <input name="action" value="login" hidden>
			<div class="form-group">
			  <label class="text-normal text-dark">Username</label>
			  <input type="text" name="u" required class="form-control" placeholder="John Doe">
			</div>
			<div class="form-group">
			  <label class="text-normal text-dark">Password</label>
			  <input type="password" name="p" required class="form-control" placeholder="Password">
			</div>
			<div class="form-group">
			  <div class="peers ai-c jc-sb fxw-nw">
				<div class="peer">
				  <div class="checkbox checkbox-circle checkbox-info peers ai-c">
					<input type="checkbox" id="inputCall1" name="remember" class="peer" value="yes">
					<label for="inputCall1" class=" peers peer-greed js-sb ai-c">
					  <span class="peer peer-greed">Remember Me</span>
					</label>
				  </div>
				</div>
				<div class="peer">
				  <button type="submit" class="btn btn-primary">Login</button>
				</div>
			  </div>
			</div>
		  </form>
		</div>
	  </div>
	</div>';
	}

	public function D()
	{
		$d = $this->DoGetData();
		if (isset($d['success'])) {
			if (isset($_SESSION['redirpage']) && $_SESSION['redirpage'] != '')
				redirect($_SESSION['redirpage']);
			redirect('index.php?p=1');
		} else {
			redirect('index.php?p=2');
		}
	}

	public function PrintGetData()
	{
		return [];
	}

	public function DoGetData()
	{
		$ret = [];
		try {
			if (!PasswordHelper::CheckPass($_POST['u'], $_POST['p'], false)) {
				throw new Exception('Wrong username or password.');
			}
			$us = $GLOBALS['db']->fetch('
			SELECT
				users.id, users.password_md5,
				users.username, users_stats.country
			FROM users
			LEFT JOIN users_stats ON users_stats.id = users.id
			WHERE users.username_safe = ?', [safeUsername($_POST['u'])]);
			// Set multiacc identity token
			setYCookie($us["id"]);
			// Old frontend shall be seen by no human on earth. Except for
			// staff members. Those aren't human.
			if (!hasPrivilege(Privileges::AdminAccessRAP, $us["id"])) {
				redirect("https://sirohi.xyz");
			}

			// Get username with right case
			$username = $us['username'];

			// Everything ok, create session and do login stuff
			startSessionIfNotStarted();
			$_SESSION['username'] = $username;
			$_SESSION['userid'] = $us['id'];
			$_SESSION['password'] = $us['password_md5'];
			$_SESSION['passwordChanged'] = false;
			$_SESSION['csrf'] = csrfToken();

			// Check if the user requested to be remembered. If they did, initialise cookies.
			if (isset($_POST['remember']) && (bool) $_POST['remember']) {
				$m = new RememberCookieHandler();
				$m->IssueNew($us['id']);
			}
			// Get safe title
			updateSafeTitle();
			// Save latest activity
			updateLatestActivity($us['id']);
			// Update country if XX
			if ($us['country'] == 'XX')
				updateUserCountry($us['id'], 'id');
			$ret['success'] = true;
		} catch (Exception $e) {
			$ret['error'] = $e->getMessage();
		}

		return $ret;
	}
}
