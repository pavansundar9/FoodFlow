<?php
session_start();
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sdp";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$image_path = $name = $phone = $address = $user_type = $daily_count = $req_bool = $accept = $req_people = $receiver_type = $doner_type = $typical_donation = $delivery = '';
$default_image = 'uploads/food-bank-logo.png';

if (isset($_SESSION['type'])) {
    // echo "<script>console.log('The session type is, " . $_SESSION['type'] . "');</script>";
    if ($_SESSION['type'] == 'foodreceiver') {
        $sql = "SELECT * FROM foodreceivers WHERE email = '$email'";
    } elseif ($_SESSION['type'] == 'doner') {
        $sql = "SELECT * FROM fooddoners WHERE email = '$email'";
    }
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // echo "<script>console.log('The data fetched is, " . json_encode($row) . "');</script>";

        // Common fields
        $name = $row['name'];
        $phone = $row['phone'];
        $address = $row['address'];
        $image_path = $row['image_path'];
        $user_type = $_SESSION['type'];
        $final_image = (!empty($image_path) && file_exists($image_path)) ? $image_path : $default_image;

        if ($user_type == 'foodreceiver') {
            // Receiver-specific fields
            $receiver_type = $row['receiver_type'];
            $daily_count = $row['daily_count'];
            $req_bool = ($row['req_bool'] == 1) ? 'Yes' : 'No';
            $accept = ($row['accept'] == 'all') ? 'All types of food' : $row['accept'];
            $req_people = $row['req_people'];
        } elseif ($user_type == 'doner') {
            // Doner-specific fields
            $doner_type = $row['doner_type'];
            $typical_donation = $row['typical_donation'];
            $delivery = $row['delivary'];
        }
    } else {
        // echo '<script>console.log("No records found.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page - FoodFlow</title>
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#17272b',
                        accent: '#c8ed6c',
                        secondary: '#ff8a3d',
                        lightBg: '#f9f9f9',
                    },
                    fontFamily: {
                        sans: ['Josefin Sans', 'Arial', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 5px 15px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes underlineAnimation {
            from { width: 0; }
            to { width: 100%; }
        }

        .underline-animation {
            background-image: linear-gradient(currentColor, currentColor);
            background-size: 0 2px;
            background-repeat: no-repeat;
            background-position: 0 100%;
            transition: background-size 0.3s ease-in-out;
        }

        .underline-animation:hover {
            background-size: 100% 2px;
        }

        /* Set up scrollable main content */
        body {
            overflow: hidden; /* Prevent double scrollbars */
            height: 100vh;
            width: 100vw;
        }

        .progress-bar {
            position: relative;
            height: 8px;
            border-radius: 4px;
            background-color: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            position: absolute;
            height: 100%;
            border-radius: 4px;
            background-color: #c8ed6c;
            transition: width 0.3s ease;
        }

        /* Mobile nav menu */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
        }
        
        @media (min-width: 1024px) {
            #mobile-menu-button {
                display: none;
            }
        }
        
        /* Handle sidebar display on different screen sizes */
        @media (max-width: 1023px) {
            .desktop-sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="font-sans bg-lightBg text-gray-800">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="fixed top-4 left-4 z-30 bg-primary text-white p-2 rounded-md lg:hidden">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Sidebar Navigation (hidden by default) -->
    <nav id="mobile-sidebar" class="mobile-menu fixed left-0 top-0 w-64 h-screen bg-primary p-5 z-20 lg:hidden">
        <div class="flex justify-between items-center mb-8">
            <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-16 w-auto">
            <button id="close-mobile-menu" class="text-white text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="flex flex-col list-none p-0 space-y-6">
            <li class="text-xl">
                <a href="profile.php" class="block text-black no-underline bg-white p-3 rounded shadow-md hover:shadow-lg transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Desktop Sidebar Navigation -->
    <nav class="desktop-sidebar fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10 hidden lg:block">
        <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-24 w-auto mb-10">
        <ul class="flex flex-col list-none p-0 ml-5 space-y-12">
            <li class="text-xl">
                <a href="profile.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Overlay for mobile menu -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-10 hidden lg:hidden"></div>

    <!-- Scrollable Main Content -->
    <main class="main-content lg:ml-[20%] min-w-0 flex-1 p-4 sm:p-6 lg:p-8 h-screen overflow-y-auto scrollable-content">
        <h1 class="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8 pb-2 border-b-4 border-accent inline-block mt-12 lg:mt-0">Profile</h1>
        
        <!-- Profile Header -->
        <div class="flex flex-col sm:flex-row items-center mb-8 sm:mb-10 sm:bg-white p-4 sm:p-6 rounded-xl shadow-custom">
            <img src="<?php echo $final_image; ?>" alt="Profile Image" class="rounded-xl w-28 h-28 sm:w-36 sm:h-36 md:w-40 md:h-40 object-cover sm:mr-5 mb-4 sm:mb-0 border-4 border-accent shadow-custom">
            <div class="flex flex-col text-center sm:text-left">
                <p class="font-bold text-xl sm:text-2xl mb-2 text-primary"><?php echo $name; ?>
                    <span class="text-secondary text-sm italic bg-secondary/10 px-3 py-1 rounded-full inline-block whitespace-nowrap">
                        <?php echo ($user_type == 'foodreceiver') ? 'Food Receiver' : 'Donor'; ?>
                    </span>
                </p>
                <div class="space-y-3 sm:space-y-4">
                        <div>
                            
                            <p class="text-gray-800 text-sm sm:text-base break-words"><i class="fa fa-envelope" aria-hidden="true"></i>&nbsp;<?php echo $email; ?></p>
                        </div>
                        
                        <div>
                            <p class="text-gray-800 text-sm sm:text-base"><i class="fa-solid fa-phone"></i>&nbsp;<?php echo $phone; ?></p>
                        </div>
                    </div>
            </div>
        </div>
        
        <!-- Profile Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column - Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Address Panel -->
                <div class="bg-white rounded-xl p-4 sm:p-6 shadow-custom">
                    <h2 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4 pb-2 border-b-2 border-accent text-primary">Address</h2>
                    <p class="leading-relaxed text-sm sm:text-base"><?php echo $address; ?></p>
                </div>
                
                <!-- About Section -->
                <div class="bg-white rounded-xl p-4 sm:p-6 shadow-custom">
                    <h2 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4 pb-2 border-b-2 border-accent text-primary">
                        <?php echo ($user_type == 'foodreceiver') ? 'About Us' : 'About Your Donations'; ?>
                    </h2>
                    
                    <?php if ($user_type == 'foodreceiver'): ?>
                    <p class="leading-relaxed text-sm sm:text-base">
                        <?php echo $name; ?> is a dedicated food receiver committed to addressing hunger and food insecurity in our community. 
                        <?php if ($receiver_type == 'individual'): ?>
                            We are an individual recipient working to ensure food security for our family and community members.
                        <?php elseif ($receiver_type == 'organization'): ?>
                            As an organization, we work tirelessly to distribute food donations to those who need them most in our area.
                        <?php elseif ($receiver_type == 'food_bank'): ?>
                            We operate as a food bank, collecting and distributing food donations to combat hunger and reduce food waste in our community.
                        <?php else: ?>
                            We are dedicated to receiving and distributing food donations to help those in need.
                        <?php endif ?>
                        Through partnerships with donors and volunteers, we strive to create a more food-secure environment for everyone.
                    </p>
                    <?php else: ?>
                    <p class="leading-relaxed text-sm sm:text-base">
                        Thank you for being a valued food donor! Your contributions make a significant impact on our community by reducing food waste and helping those in need. As a <?php echo $doner_type; ?>, your donations of <?php echo $typical_donation; ?> help us serve our community better.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column - Details -->
            <div class="space-y-6">
                <!-- User Type Specific Panel -->
                <div class="bg-white rounded-xl p-4 sm:p-6 shadow-custom">
                    <h2 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4 pb-2 border-b-2 border-accent text-primary">
                        <?php echo ($user_type == 'foodreceiver') ? 'Food Receiver Details' : 'Donor Details'; ?>
                    </h2>
                    
                    <?php if ($user_type == 'foodreceiver'): ?>
                    <!-- Receiver specific content -->
                    <div class="space-y-3 sm:space-y-4">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Receiver Type</p>
                            <span class="inline-block bg-accent/20 text-primary font-semibold px-3 py-1 rounded-full text-xs sm:text-sm">
                                <?php echo ucfirst(str_replace('_', ' ', $receiver_type)); ?>
                            </span>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Food Types Accepted</p>
                            <p class="text-gray-800 text-sm sm:text-base"><?php echo $accept; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Currently Accepting Donations</p>
                            <span class="inline-block px-3 py-1 rounded-full text-xs sm:text-sm font-semibold <?php echo ($req_bool == 'Yes') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $req_bool; ?>
                            </span>
                        </div>
                        
                        <?php if ($req_people > 0): ?>
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Today's Donations Progress</p>
                            <div class="progress-bar mt-2 mb-1">
                                <div class="progress-fill" style="width: <?php echo min(($daily_count / $req_people) * 100, 100); ?>%"></div>
                            </div>
                            <p class="text-right text-xs sm:text-sm">
                                <span class="font-bold text-secondary"><?php echo $daily_count; ?></span> 
                                of 
                                <span class="font-bold"><?php echo $req_people; ?></span> needed
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <!-- Donor specific content -->
                    <div class="space-y-3 sm:space-y-4">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Donor Type</p>
                            <span class="inline-block bg-accent/20 text-primary font-semibold px-3 py-1 rounded-full text-xs sm:text-sm">
                                <?php echo $doner_type; ?>
                            </span>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Typical Donation</p>
                            <p class="text-gray-800 text-sm sm:text-base"><?php echo $typical_donation; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1 text-sm sm:text-base">Delivery Option</p>
                            <div class="flex items-center text-gray-800 mt-1 text-sm sm:text-base">
                                <i class="fas fa-truck text-secondary mr-2"></i>
                                <?php echo $delivery; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Add some bottom padding for better scrolling experience -->
        <div class="h-10"></div>
    </main>

    <script>
        // Mobile menu functionality
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const closeMobileMenu = document.getElementById('close-mobile-menu');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');
        
        mobileMenuButton.addEventListener('click', () => {
            mobileSidebar.classList.add('open');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
            mobileMenuButton.classList.add('hidden'); // Hide hamburger button when menu is open
        });
        
        function closeMenu() {
            mobileSidebar.classList.remove('open');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = ''; // Restore scrolling
            mobileMenuButton.classList.remove('hidden'); // Show hamburger button when menu is closed
        }
        
        closeMobileMenu.addEventListener('click', closeMenu);
        mobileOverlay.addEventListener('click', closeMenu);
        
        // Close menu on window resize if switching to desktop view
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeMenu();
            }
        });
    </script>
</body>
</html>