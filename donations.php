<?php
session_start();
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
    echo "<script>console.log('Email: $email, Type: $type');</script>";
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
if (isset($_SESSION['type'])) {
    if ($_SESSION['type'] == 'foodreceiver') {
        $type = 'receiver';
    }
    $donations_table = "SELECT * FROM donations WHERE $type = '$email'";
    $donations_result = mysqli_query($conn, $donations_table);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - FoodFlow</title>
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
            from {
                width: 0;
            }

            to {
                width: 100%;
            }
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
            overflow: hidden;
            height: 100vh;
        }

        /* Donation card animations */
        .donation-card {
            transition: all 0.3s ease;
        }

        .donation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Mobile menu styles */
        #mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        #mobile-sidebar.open {
            transform: translateX(0);
        }

        /* Optional: Make the menu button transition smoother */
        #mobile-menu-button {
            transition: opacity 0.3s ease;
        }

        #mobile-menu-button.hidden {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>

<body class="font-sans bg-lightBg text-gray-800 flex">
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
                <a href="community.html" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
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
                <a href="profile.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded  transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-colors duration-300">
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
        <h1 class="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8 pb-2 border-b-4 border-accent inline-block mt-12 lg:mt-0">Donations</h1>

        <!-- Donations Container -->
        <div class="flex flex-wrap gap-4 justify-between">
            <?php
            if (mysqli_num_rows($donations_result) > 0) {
                while ($row = mysqli_fetch_assoc($donations_result)) {
                    $image_paths = explode(';', $row["donation_img"]);
                    $first_image_path = $image_paths[0]; // Get the first image path

                    // Determine whether to fetch donor or receiver data based on user type
                    if ($type == 'doner') {
                        // If the user is a donor, fetch receiver data
                        $receiver_email = $row["receiver"];
                        $receiver_query = "SELECT * FROM foodreceivers WHERE email = '$receiver_email'";
                        $result = $conn->query($receiver_query);
                        if ($result->num_rows > 0) {
                            $receiver_row = $result->fetch_assoc();
                            $receiver_name = $receiver_row['name'];
                            $receiver_phone = $receiver_row['phone'];
                            $receiver_address = $receiver_row['address'];
                            $receiver_daily_count = $receiver_row['daily_count'];
                            $receiver_req_bool = ($receiver_row['req_bool'] == 1) ? 'Yes' : 'No';
                            $receiver_accept = $receiver_row['accept'];
                            $receiver_req_people = $receiver_row['req_people'];
                        } else {
                            $receiver_name = "Unknown Receiver";
                        }
                    } else {
                        // If the user is a receiver, fetch donor data
                        $doner_email = $row["doner"];
                        $doner_query = "SELECT * FROM fooddoners WHERE email = '$doner_email'";
                        $result = $conn->query($doner_query);
                        if ($result->num_rows > 0) {
                            $doner_row = $result->fetch_assoc();
                            $doner_name = $doner_row['name'];
                            $doner_phone = $doner_row['phone'];
                            $doner_address = $doner_row['address'];
                        } else {
                            $doner_name = "Unknown Donor";
                        }
                    }
            ?>
                    <div class="donation-card bg-white rounded-xl shadow-custom w-full sm:w-[48%] mb-6 overflow-hidden group">
                        <div class="flex flex-col sm:flex-row p-4 sm:p-5 items-start sm:items-center">
                            <div class="w-full sm:w-24 h-40 sm:h-24 min-w-0 sm:min-w-[6rem] rounded-lg overflow-hidden bg-gray-100 mb-3 sm:mb-0 sm:mr-4">
                                <img class="w-full h-full object-cover" src="<?php echo $first_image_path; ?>" alt="Donation Image">
                            </div>

                            <div class="flex-1 w-full">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg sm:text-xl font-bold text-primary mb-1">
                                        <?php echo ($type == 'doner') ? htmlspecialchars($receiver_name) : htmlspecialchars($doner_name); ?>
                                    </h3>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center">
                                        <span class="text-sm font-semibold text-gray-700">Donation Date:</span>
                                        <span class="text-sm sm:ml-2"><?php echo $row["donation_date"]; ?></span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center">
                                        <span class="text-sm font-semibold text-gray-700 sm:mr-2">Food Type:</span>
                                        <span class="text-xs bg-secondary/10 text-secondary rounded-full px-3 py-1 mt-1 sm:mt-0 inline-block">
                                            <?php echo $row["food_type"]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($type == 'doner' || $type == 'receiver'): ?>
                            <!-- This part only exists if details are to be shown -->
                            <div class="bg-gray-50 px-4 sm:px-5 py-0 max-h-0 group-hover:max-h-[32rem] sm:group-hover:max-h-48 group-hover:py-3 transition-all duration-300 overflow-hidden">
                                <?php if ($type == 'doner'): ?>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <div class="mb-2 sm:mb-0">
                                            <p class="font-semibold text-gray-700">Contact Number:</p>
                                            <p><?php echo htmlspecialchars($receiver_phone); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Address:</p>
                                            <p class="break-words"><?php echo htmlspecialchars($receiver_address); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mt-3">
                                        <div class="mb-2 sm:mb-0">
                                            <p class="font-semibold text-gray-700">Daily Count:</p>
                                            <p><?php echo htmlspecialchars($receiver_daily_count); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Requirement Met:</p>
                                            <p><?php echo htmlspecialchars($receiver_req_bool); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mt-3">
                                        <div class="mb-2 sm:mb-0">
                                            <p class="font-semibold text-gray-700">Food Accepted:</p>
                                            <p><?php echo htmlspecialchars($receiver_accept); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">People Served:</p>
                                            <p><?php echo htmlspecialchars($receiver_req_people); ?></p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <div class="mb-2 sm:mb-0">
                                            <p class="font-semibold text-gray-700">Contact Number:</p>
                                            <p><?php echo htmlspecialchars($doner_phone); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Address:</p>
                                            <p class="break-words"><?php echo htmlspecialchars($doner_address); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php
                }
            } else {
                ?>
                <!-- No Donations Message - Centered to the entire page -->
                <div class="fixed inset-0 lg:left-[20%] flex flex-col items-center justify-center z-0 pointer-events-none">
                    <div class="text-center px-4 pointer-events-auto">
                        <!-- Icon -->
                        <div class="mb-6">
                            <i class="fas fa-heart text-6xl sm:text-7xl text-gray-300"></i>
                        </div>
                        
                        <!-- Main Message -->
                        <h2 class="text-2xl sm:text-3xl font-bold text-primary mb-4">No Donations Yet</h2>
                        <p class="text-gray-600 text-center max-w-md mx-auto mb-8 text-sm sm:text-base">
                            <?php echo ($type == 'doner') ?
                                "You haven't made any donations yet. Start making a difference in someone's life today!" :
                                "You haven't received any donations yet. They'll appear here once you do.";
                            ?>
                        </p>
                        
                        <!-- Donate Button (only for donors) -->
                        <?php if ($type == 'doner'): ?>
                            <button onclick="window.location.href='search-foodBank.php'" 
                                class="bg-secondary hover:bg-secondary/90 text-white font-bold py-3 px-8 rounded-full transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base">
                                <i class="fas fa-plus mr-2"></i>Make Your First Donation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            }
            ?>
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