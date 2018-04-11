
<?php
	$additional_css = '<link rel="stylesheet" href="./css/login_signup.css">';
	$pg_title = "Login or Signup";
	require_once("lib/layout_top.php");
?>
  
  <div id="login-signup">
  
	<div id="title_container">
		<div id="login_tab" onclick="login.showTab(this.id);" class="tab">Login</div>
		<div id="signup_tab" onclick="login.showTab(this.id);" class="tab">Signup</div>
	</div>
	
	<div id="login">
		<div class="wrapper">
			<div class="title">
				<img src="./images/smaller_height.svg" alt="dots" />
				
			</div>
			<p>Glad to see you're back! Please login below to start playing!</p>
			<p class="error" id="login_errors"></p>
			
			<input id="unm" type="text" placeholder="username" />
			<input id="pwd" type="password" placeholder="password" />
			<!-- einloggen -->
			<input type="submit" id="login_btn" value="login" />
			
			<!-- neu anmelden -->
			<p>Not yet a member? <span class="link_lookalike" onclick="login.showTab('signup_tab');">Signup</span></p>
		</div>
	</div>
	
	
	<div id="signup">
		<div class="wrapper">
			<p>Why hello, stranger! We'd love to have you join us.</p>
			<ul class="error" id="signup_errors"></ul>
			<p class="details">
				<strong>Username</strong><br>
				
				<!-- Deine Benutzername  muss 4 Zeichen lange sein. -->
				<em>Username must be at least 4 characters long.</em><br/>
				<em>Allowed Characters: alphanumeric and . _ -</em>
				<span id="username_errors"></span>
			</p>
			<input id="new_unm" type="text" placeholder="username" />
			<p class="details">
				<strong>Password</strong><br>
				<!-- Dein Passwort muss 6 Zeichen lange sein. -->
				<em>Password must be at least 6 characters long</em><br/>
				<em>Allowed Characters: alphanumeric and . !</em>
			</p>
			<input id="new_pwd" type="password" placeholder="password" />
			<input id="new_pwd_again" type="password" placeholder="retype password" />
			<p class="details"><strong>Email</strong></p>
			<input id="email" type="email" placeholder="email" />
			<input id="email_again" type="email" placeholder="retype email" />
			
			<!-- neu anmelden -->
			<input type="submit" id="signup_btn" value="signup" />
		</div>
	</div>
  </div>
  
  <script src="app/login/login.js"></script>
  <script>var login = new Login(); login.init();</script>
  
<?php
	$additional_js = "";
	require_once("./lib/layout_bottom.php");
?>
