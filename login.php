<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="image/png" href="food-flow-icon.png">
    <link rel="stylesheet"  href="signup.css"> 

    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <title>FoodFlow-Login</title>
</head>
<body>
    <!-- <div class="bg"></div> -->
    <!--Form for user to input their information-->
    <div class="body">
        <div class="logo-side">
            <img src="images/foodflow-bg.png" alt="Logo" id="logo"/>
            <!-- <div class="logo" style="font-family: Kaushan Script, cursive;"><label class="" style="font-family: Meddon, cursive;">F</label>oodFlow</div> -->
        </div>
        <div class="form login-form" >
            <center><h2>Login</h2></center>
            <form name="form" id="form" action="login_valid.php" method="POST">
                <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                        unset($_SESSION['error_message']);
                    }
                ?><br>
                <div class="input-wrapper">
                    <!-- <label for="type" class="radio-label">Type of User:</label><br><br> -->
                    <div class="radio">
                        <input type="radio" name="type" value="doner" id="doner"><label for="doner">Food Doner</label>
                        <input type="radio" name="type" value="foodreceiver" id="foodreceiver"><label for="foodreceiver">Food Receiver</label>
                    </div><br><br>
                </div>
                <div class="input-wrapper">
                    <input type="email" class="input" id="email" name="email" placeholder=" ">
                    <label class="label" for="email">Email</label>
                </div><br><br>
                <div class="input-wrapper">
                    <input type="password" class="input" id="password" name="password" placeholder=" ">
                    <label class="label" for="name">Password</label>
                </div><br><br>
                <input type="submit" id="submit" name="submit" value="Login"><br>
                <div class="login">
                <p>Don't have an account yet? <a href="signup.php">Sign Up</a></p>                </div>
            </form>
        </div>
    </div>
</body>
</html>