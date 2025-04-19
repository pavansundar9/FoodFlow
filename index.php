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
        // Debug session data
        echo "<script>console.log('Session type: " . $type . "');</script>";
        echo "<script>console.log('Session email: " . $_SESSION['email'] . "');</script>";
        if ($type == 'doner') {
            $doner_email = $_SESSION['email'];
            $doner_name = $_SESSION['name'];
            $doner_phone = $_SESSION['phone'];
            $table = 'fooddoners';
            // $location = 'doner_extra_info.php';
            $sql = "SELECT * FROM $table WHERE email = '$doner_email'";
        } else {
            $receiver_email = $_SESSION['email'];
            $receiver_name = $_SESSION['name'];
            $receiver_phone = $_SESSION['phone'];
            $table = 'foodreceivers';

            $sql = "SELECT * FROM $table WHERE email = '$receiver_email'";
            // $location = 'receiver_extra_info.php';
        }

        // $sql ="SELECT * FROM $table";
        $result = mysqli_query($conn, $sql);
        echo "<script>console.log('SQL Query: " . $sql . "');</script>";

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                $image_path = $row['image_path'];
            } else {
                $image_path = "images/user.png";
            }
        }else {
            echo "<script>console.log('No results found or query failed');</script>";
            if (!$result) {
                echo "<script>console.log('MySQL Error: " . mysqli_error($conn) . "');</script>";
            }
        }
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
            <li class="user">
                <p class="user-details">
                    <?php 
                        if(isset($_SESSION['email'])) 
                            echo $doner_email; 
                        else echo "Guest&nbsp;";  
                    ?>
                </p>
                <a href="javascript:void(0)">
                    <div class="user-icon">
                    <?php 
                        if(isset($image_path)) {
                            echo "<script>console.log('Using image path: " . $image_path . "');</script>";
                        } else {
                            echo "<script>console.log('Using default image path');</script>";
                        }
                    ?>
                    <img src="
                        <?php 
                            if(isset($image_path)) 
                                echo $image_path; 
                            else 
                                echo "images/user.png"; 
                        ?>" 
                    alt="user icon" id="user-icon">
                    </div>
                </a>
            </li>
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

    <section class="hero-section" id="home">
        <div class="cta">
            <h2>In a world where abundant food coexists with hungry souls, FoodFlow aims to bridge the gap between surplus and scarcity.</h2>
            <div class="cta-items">
                <button class="cta-button" onclick="location.href='search-foodBank.php'">Donate</button>
                <a href="#about">Learn More...</a>
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

    <section class="fewStories">
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
    </section>

    <section class="services-section" id="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <img src="images/donation-icon.png" alt="Food Donation">
                    </div>
                    <h3>Food Donation</h3>
                    <p>Connect with local food banks and donate your surplus food to those who need it most.</p>
                    <a href="search-foodBank.php" class="service-link">Donate Now</a>
                </div>
                <!-- <div class="service-card">
                    <div class="service-icon">
                        <img src="images/volunteer-icon.png" alt="Volunteer">
                    </div>
                    <h3>Volunteer</h3>
                    <p>Join our community of volunteers and help us distribute food to vulnerable populations.</p>
                    <a href="volunteer.php" class="service-link">Join Us</a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <img src="images/event-icon.png" alt="Food Drives">
                    </div>
                    <h3>Food Drives</h3>
                    <p>Organize food collection events in your community to support local food banks.</p>
                    <a href="organize-drive.php" class="service-link">Get Started</a>
                </div> -->
                <div class="service-card">
                    <div class="service-icon">
                        <img src="images/education-icon.png" alt="Education">
                    </div>
                    <h3>Food Waste Education</h3>
                    <p>Learn about reducing food waste and making sustainable food choices.</p>
                    <a href="resources.php" class="service-link">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how">
        <div class="container">
            <h2>How It Works</h2>
            <!-- Step 1 -->
            <div class="step">
                <div class="step-icon">
                    1
                </div>
                <div class="step-content">
                    <h4>Sign Up or Log In</h4>
                    <p>Create an account as a donor or receiver, or log in to access your personalized dashboard.</p>
                </div>
            </div>
            <!-- Step 2 -->
            <div class="step">
                <div class="step-icon">
                    2
                </div>
                <div class="step-content">
                    <h4>List or Search Donations</h4>
                    <p>Donors can list surplus food for donation, while receivers can search for available donations or nearby food banks.</p>
                </div>
            </div>
            <!-- Step 3 -->
            <div class="step">
                <div class="step-icon">
                    3
                </div>
                <div class="step-content">
                    <h4>Connect and Donate</h4>
                    <p>Donors and receivers connect to arrange the donation, ensuring food reaches those in need efficiently.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="impact-section">
        <div class="container">
            <h2>Our Impact</h2>
            <div class="impact-stats">
                <div class="stat-box">
                    <h3>10,000+</h3>
                    <p>Meals Delivered</p>
                </div>
                <div class="stat-box">
                    <h3>500+</h3>
                    <p>Active Donors</p>
                </div>
                <div class="stat-box">
                    <h3>50+</h3>
                    <p>Partner Organizations</p>
                </div>
                <div class="stat-box">
                    <h3>100+</h3>
                    <p>Communities Served</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About FoodFlow</h2>
                    <p>FoodFlow was founded in 2022 with a simple mission: to reduce food waste and hunger simultaneously. We connect food donors with those in need through a network of local food banks and community organizations.</p>
                    <p>Our platform makes it easy for individuals, restaurants, and event venues to donate surplus food that would otherwise go to waste. We also educate communities about sustainable food practices and the importance of reducing food waste.</p>
                    <a href="about.php" class="about-link">Learn More About Us</a>
                </div>
                <div class="about-image">
                    <img src="images/about-image.png" alt="FoodFlow Team">
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <h2>What People Say</h2>
            <div class="testimonials-slider">
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"FoodFlow has made it so easy for our restaurant to donate surplus food. Instead of throwing away perfectly good meals, we're now helping feed families in need."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial-1.png" alt="Restaurant Owner">
                        <div>
                            <h4>Sarah Johnson</h4>
                            <p>Restaurant Owner</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"As a food bank coordinator, I've seen firsthand how FoodFlow has increased our food supply. We're now able to serve more families than ever before."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial-2.png" alt="Food Bank Coordinator">
                        <div>
                            <h4>Michael Thompson</h4>
                            <p>Food Bank Coordinator</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"The FoodFlow app has made it so simple to find where I can donate food after events. It's rewarding to know nothing goes to waste."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonial-3.png" alt="Event Planner">
                        <div>
                            <h4>Lisa Chen</h4>
                            <p>Event Planner</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section" id="contact">
        <div class="container">
            <h2>Contact Us</h2>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    <p>Have questions about FoodFlow? We're here to help!</p>
                    <div class="info-item">
                        <span class="material-symbols-outlined">email</span>
                        <p>info@foodflow.org</p>
                    </div>
                    <div class="info-item">
                        <span class="material-symbols-outlined">call</span>
                        <p>+1 (555) 123-4567</p>
                    </div>
                    <div class="info-item">
                        <span class="material-symbols-outlined">location_on</span>
                        <p>123 Main Street, City, Country</p>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link">Facebook</a>
                        <a href="#" class="social-link">Twitter</a>
                        <a href="#" class="social-link">Instagram</a>
                    </div>
                </div>
                <div class="contact-form">
                    <form action="contact-process.php" method="post">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" class="input" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="input" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="input" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" class="input" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="images/foodflow-logo.png" alt="FoodFlow Logo">
                    <p>Bridging the gap between surplus and scarcity</p>
                </div>
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="login.php">Login/Register</a></li>
                    </ul>
                </div>
                <div class="footer-newsletter">
                    <h3>Stay Updated</h3>
                    <p>Subscribe to our newsletter for updates on our initiatives and impacts.</p>
                    <form action="subscribe.php" method="post">
                        <input type="email" name="email" placeholder="Your Email" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 FoodFlow. All Rights Reserved.</p>
                <div class="footer-policies">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

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

            // Testimonials slider
            const testimonials = document.querySelectorAll('.testimonial');
            let currentTestimonial = 0;
            
            function showTestimonial(index) {
                testimonials.forEach((testimonial, i) => {
                    testimonial.style.display = i === index ? 'block' : 'none';
                });
            }
            
            function nextTestimonial() {
                currentTestimonial = (currentTestimonial + 1) % testimonials.length;
                showTestimonial(currentTestimonial);
            }
            
            // Initialize testimonials display
            if (testimonials.length > 0) {
                showTestimonial(0);
                setInterval(nextTestimonial, 5000); // Change testimonial every 5 seconds
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