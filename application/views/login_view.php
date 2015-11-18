<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery-1.7.2.js"></script>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<title>Hr Login</title>
<link href="<?php echo base_url(); ?>/css/style.css" rel="stylesheet" type="text/css" />
<!--[if IE]> <link href="css/ie.css" rel="stylesheet" type="text/css"> <![endif]-->

<!-- Shared on MafiaShare.net  -->
</head>
<body>
<!-- Top line begins -->
  
<br clear="all" />
<!-- Login wrapper begins -->
<div class="loginWrapper">
  <!-- Current user form -->
  <form action="" id="login" name="login" method="post">
    <div class="loginPic"> <a href="#" title=""><img src="<?php echo base_url(); ?>images/logo.png" alt="" width="150" /></a> 
    
    <span><img src="<?php echo base_url(); ?>images/lock.png" width="60"  alt=""  /></span>
    </div>
	    <?php if(!empty(@$msg)){
	echo "<div style='color: red;font-size: 12px;padding-right: 19px;text-align: right;'>".$msg."</div>";
	}?> 
    
    <input type="text" name="username" id="username" placeholder="Username" class="loginEmail" required style="height:31px;" />
	<?php echo "<div style='color: red;font-size: 12px;padding-right: 19px;text-align: right;'></div>"; ?>
    <input type="password" name="password" required id="password" placeholder="Password" class="loginPassword" style="height:31px;" />
	<?php echo "<div style='color: red;font-size: 12px;padding-right: 19px;text-align: right;'></div>"; ?>

    <div class="logControl">
      <div class="memory">
        <input type="checkbox" checked="checked" class="check" id="remember1" />
        <label for="remember1">Remember me</label>
      </div>
      <br clear="all" />
      <input type="submit" name="submit" value="Login" class="buttonM bBlue btn login-icon" />
      <div class="clear"></div>
    </div>
  </form>
  <!-- New user form -->
</div>
<!-- Login wrapper ends -->
 <script type="text/javascript">
	$('#form input').keydown(function(e) {
		if (e.keyCode == 13) {
			$('#form').submit();
		}
	});
	</script>
</body>
</html>
