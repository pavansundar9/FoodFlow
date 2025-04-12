<?php
session_start();
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
}
//Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdp";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Receiver Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            /* padding: 20px; */
            background-color: #f5f5f5;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .container1 {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        .card1 {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px auto;
            max-width: 500px;
            transition: transform 0.2s;
        }

        .card1:hover , .card2:hover{
            transform: translateY(-5px);
        }
        .card2{
            display: flex;
        }
        .card2 img{
            width: 200px;
            height: 200px;
            border-radius: 10px;
            margin: 15px;
        }
        /* Image styling */
        .card1 img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Information container */
        .info {
            padding: 20px;
        }

        .info p {
            margin-bottom: 10px;
            color: #333;
        }

        .info p strong {
            color: #555;
        }

        .info p:last-child {
            margin-bottom: 0;
        }

        .bg {
            opacity: 0.7;
            width: 100%;
            height: auto;
            border-radius: 20px;
            align-self: center;
            text-align: center;
            max-width: 800px;
        }

        .donations-received {
            margin-top: 40px;
            /* width: 700px; */
            width: 100%;
            /* background-color: #172323; */
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <div class="logo"><img src="images/foodflow-logo.png" alt="Donating Food Image"></div>

        <div class="nav-items">
            <ul id="menu-bar">
                <div id="nav-elements">
                    <li><a href="#home">HOME</a></li>
                    <li><a href="#services">SERVICES</a></li>
                    <li><a href="#aboutus">ABOUT US</a></li>
                    <li><a href="#contact">CONTACT US</a></li>
                </div>
                <li class="user-details">
                        <p>
                            <?php 
                                if(isset($_SESSION['email'])){
                                    echo $email;
                                }
                                else
                                    echo "Guest";
                            ?> 
                        </p>
                        
                    </li>
                <div class="user-icon">
                    <?php
                        if(isset($_SESSION['type'])){
                            $sql = "SELECT * FROM foodreceivers";
                            $result = mysqli_query($conn, $sql);
                            $row = mysqli_fetch_assoc($result);
                            $image_path = $row['image_path'];
                            ?>
                            <img class="profile-pic" src="<?php echo $image_path; ?>" alt="" onclick="showMenu()">
                            <?php
                        } else { 
                    ?>
                        <img src="images/user.png" alt="" onclick="showMenu()">
                    <?php } ?>
                    <div class="user-menu">
                        <div class="excess-menu">
                            <ul class="small-screen-nav">
                                <div class="small-navs">
                                    <li><a href="#home">HOME</a></li>
                                    <li><a href="#services">SERVICES</a></li>
                                    <li><a href="#aboutus">ABOUT US</a></li>
                                    <li><a href="#contact">CONTACT US</a></li>
                                    <hr>
                                </div>
                                <?php 
                                    if(isset($_SESSION['logged-in'])){
                                        echo "<li><button type='submit' onClick='window.location=\"profile.php\"'>Profile</button></li>";
                                        echo "<li><button onclick='logout.php'>LOG OUT</button></li>";
                                    } else{ 
                                        echo "<li><button onclick='login.php'>LOG IN</button></li>";
                                    }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </ul>
        </div>
    </nav>
    <div><br>
        <!-- <h2 style="left: 20px;">Dashboard</h2> -->
        <div style="display: flex; margin: 20px; gap: 20px">
            <div class="container1">
                <?php
                
                $sql = "SELECT * FROM foodreceivers WHERE email = '$email'";
                $result = mysqli_query($conn, $sql);
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<div class='card1'>";
                        echo "<img src='" . $row["image_path"] . "' alt='Receiver Image'>";
                        echo "<div class='info'>";
                        echo "<p><strong>Name:</strong> " . $row["name"] . "</p>";
                        echo "<p><strong>Email:</strong> " . $row["email"] . "</p>";
                        echo "<p><strong>Contact Number:</strong> " . $row["c_number"] . "</p>";
                        echo "<p><strong>Address:</strong> " . $row["address"] . "</p>";
                        echo "<p><strong>Receiver Type:</strong> " . $row["receiver_type"] . "</p>";
                        echo "<p><strong>Request People:</strong> " . $row["req_people"] . "</p>";
                        echo "<p><strong>Accept:</strong> " . $row["accept"] . "</p>";
                        echo "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "0 results";
                }
                ?>
            </div>
            <div class="donations-received">
                <h2>Donations Received</h2>
                <?php
                    $donations = "SELECT * FROM donations WHERE receiver = '$email'";
                    $result1 = mysqli_query($conn, $donations);
                    if (mysqli_num_rows($result1) > 0) {
                        while($row = mysqli_fetch_assoc($result1)) {
                            echo "<div class='card2'>";
                            echo "<img src='" . $row["donation_img"] . "' alt='Donation Image'>";
                            echo "<div class='info'>";
                            echo "<p><strong>Doner:</strong> " . $row["doner"] . "</p>";
                            echo "<p><strong>Food Type:</strong> " . $row["food_type"] . "</p>";
                            echo "<p><strong>Quantity:</strong> " . $row["quantity"] . "</p>";
                            echo "<p><strong>Donation Date:</strong> " . $row["donation_date"] . "</p>";
                            echo "<p><strong>Expiration Date:</strong> " . $row["expiration_date"] . "</p>";
                            echo "<p><strong>Does the items have some kind of damage:</strong> " . $row["damage"] . "</p>";
                            echo "<p><strong>Donation Info:</strong> " . $row["donation_info"] . "</p>";
                            echo "</div>";
                            echo "</div>";
                        }
                    } else {
                        // echo "No Donations Yet.<br>";
                        echo "<center><img class='bg' src='images/no-donations-yet.jpeg'></center>";
                    }
                ?>
            </div>
        </div>
    </div>
    <script>
        function showMenu() {
            var menu = document.querySelector(".small-screen-nav");
            // Check if the window width is smaller than 768px (adjust as needed)
            if (window.innerWidth < 768) {
                // Toggle the display of the small screen navigation
                if (menu.style.display === "none" || menu.style.display === "") {
                    menu.style.display = "block";
                } else {
                    menu.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
