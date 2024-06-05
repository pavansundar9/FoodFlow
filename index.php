<?php
    session_start();
    $error = "";
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sdp";
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    // Checking if the session-type is created 
    // if session type is created then session emil is crearted
    if(isset($_SESSION['type'])) {
        $type = $_SESSION['type'];
        if ($type == 'doner') {
            $doner_email = $_SESSION['email'];
            $doner_name = $_SESSION['name'];
            $doner_c_number = $_SESSION['c_number'];
            $table = 'fooddoners';
            // $location = 'doner_extra_info.php';
        } else {
            $receiver_email = $_SESSION['email'];
            $receiver_name = $_SESSION['name'];
            $receiver_c_number = $_SESSION['c_number'];
            $table = 'foodreceivers';
            // $location = 'receiver_extra_info.php';
        }

        $sql ="SELECT * FROM $table";
        $result = mysqli_query($conn,$sql);
        $row = mysqli_fetch_assoc($result);
        $image_path = $row['image_path'];
    }

    
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
    <title>FoodFlow-HomePage</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&family=Paprika&family=Tenor+Sans&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <img src="images/foodflow-logo.png" alt="logo">
        <ul>
            <li class="main-menu"><a href="#home">Home</a></li>
            <li class="main-menu"><a href="#services">Services</a></li>
            <li class="main-menu"><a href="#about">About Us</a></li>
            <li class="main-menu"><a href="#contact">Contact</a></li>
            <li class="user"><p class="user-details"><?php if(isset($_SESSION['email'])) echo $doner_email; else echo "Guest&nbsp;";  ?></p><a href="javascript:void(0)"><div class="user-icon"><img src="<?php if(isset($image_path)) echo $image_path; else echo "images/user.png" ?>" alt="user icon" id="user-icon"></div></a></li>
        </ul>
        <div id="other-menu">
            <li class="main-menu-other"><a href="#home">Home</a></li>
            <li class="main-menu-other"><a href="#services">Services</a></li>
            <li class="main-menu-other"><a href="#about">About Us</a></li>
            <li class="main-menu-other"><a href="#contact">Contact</a></li>
            <hr class="main-menu-other">
            <?php 
                if(isset($_SESSION['logged-in'])){
                    echo '<li><a href="profile.php">Profile</a></li>';
                    echo '<li><a href="logout.php">Logout</a></li>';
                }
                else
                    echo '<li><a href="login.php">Login/Register</a></li>';
            ?>
        </div>
    </nav>
    <section class="hero-section">
        <div class="cta">
            <h2>In a world where abundant food coexists with hungry souls, FoodFlow aims to bridge the gap between surplus and scarcity.</h2>
            <div class="cta-items">
                <button class="cta-button" onclick="location.href='search-foodBank.php'">Donate</button>
                <a>Learn More...</a>
            </div>
        </div>
        <div class="image-side">
            <img class="background" src="images/background.png" alt=""> 
            <div class="doner-receiver">
                <img class="receiver-img" src="images/receiver.png" alt="">
                <img class="doner-img" src="images/doner.png" alt="">
            </div>  
        </div>
    </section>
    <section>
        <div class="fewStories">
            <div class="heading">
                <h3>FEW STORIES</h3>
                <p>See How Your Donations Nourish Communities</p>
            </div>
            <div class="storiesContainer">
                <div class="outer">
                    <div class="card">
                        <img src="images/fd1.jpg" alt="fd1">
                        <div class="info">
                            <h3>Generosity in Action: Transforming Lives of Vulnerable Seniors</h3><br>
                            <p>Our initiative successfully provided nutritious meals to 30 neglected elderly individuals daily, offering them dignity and hope. With the support of generous donors like you, we were able to combat hunger and homelessness among vulnerable seniors. Together, we made a profound impact on their lives, ensuring no elderly person went hungry or suffered neglect. Thank you for helping us make a difference.</p>
                        </div>
                    </div>
                    <div class="card">
                        <img src="images/fd3.jpeg" alt="fd2">
                        <div class="info">
                            <h3>Weddings Without Waste: Turn Leftovers into Hope</h3>
                            <p>Amidst the joy of weddings, consider giving back to society by donating surplus food. Organizations like Robin Hood Army, Feeding India, Glow Tide, Food Bank Hyderabad, and Feed The Need are tackling food wastage and hunger. From distributing surplus food to the needy to setting up food banks, these initiatives make it easy for you to contribute. Join the movement and ensure no one sleeps hungry on the streets. Make a difference at your wedding or any occasion, big or small.</p>
                        </div>
                    </div>
                    <div class="card">
                        <img src="images/fd5.jpg" alt="fd3">
                        <div class="info">
                            <h3>The Cake of Kindness</h3><br>
                            <p>Birthdays at the orphanage were quiet affairs, marked only by a single cake (if there were enough supplies). Maya longed for more. Inspired, she and her friends, Leo and Priya, decided to bake cookies and collect treats. "Happy Birthday Brigade," they called themselves, delivering smiles and sugary surprises to kids at another orphanage. Even a quiet birthday, Maya realized, could be filled with the sweetness of giving.</p>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </section>
    <section class="container mt-5">
        <h2 class="mb-4">How It Works</h2>
        <!-- Step 1 -->
        <div class="step">
            <div class="step-icon">
                1
            </div>
            <div class="step-content">
                <h4>Find What You Need</h4>
                <p>Search our database for the information or service you need.</p>
            </div>
        </div>
        <!-- Step 2 -->
        <div class="step">
            <div class="step-icon">
                2
            </div>
            <div class="step-content">
                <h4>Submit Your Request</h4>
                <p>Use our simple form to submit your request for service.</p>
            </div>
        </div>
        <!-- Step 3 -->
        <div class="step">
            <div class="step-icon">
                3
            </div>
            <div class="step-content">
                <h4>Get It Done</h4>
                <p>Our team will process your request and deliver the service promptly.</p>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctaButton = document.querySelector('.cta-button');
            const receiverImg = document.querySelector('.receiver-img');
            const donerImg = document.querySelector('.doner-img');

            var fewStoriesDiv = document.querySelector('.fewStories');

            if (ctaButton && receiverImg && donerImg) {
                ctaButton.addEventListener('mouseover', function() {
                    receiverImg.style.display = 'block';
                    donerImg.classList.add('doner-img-hover'); 
                });

                ctaButton.addEventListener('mouseout', function() {
                    receiverImg.style.display = 'none';
                    donerImg.classList.remove('doner-img-hover');
                });
            }

            if (fewStoriesDiv) {
                window.addEventListener('scroll', function(event) {
                    // Check if the user is scrolling inside the fewStories div
                    var scrollLeft = fewStoriesDiv.scrollLeft;
                    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                    if (scrollTop > fewStoriesDiv.offsetTop && scrollTop < (fewStoriesDiv.offsetTop + fewStoriesDiv.offsetHeight)) {
                        // User is scrolling inside the fewStories div
                        fewStoriesDiv.scrollLeft = scrollLeft + event.deltaY;
                        // Prevent default vertical scrolling
                        event.preventDefault();
                    }
                });
            }
        });
        document.getElementById('user-icon').addEventListener('click', function() {
            const other_menu = document.getElementById('other-menu');
            if (other_menu.style.display === 'none' || other_menu.style.display === '') {
                other_menu.style.display = 'block';
            } else {
                other_menu.style.display = 'none';
            }
        });

        // Hide the menu if clicked outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('other-menu');
            const userIcon = document.getElementById('user-icon');
            if (!menu.contains(event.target) && !userIcon.contains(event.target)) {
                menu.style.display = 'none';
            }
        });

    </script>
</body>
</html>