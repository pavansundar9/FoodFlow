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
    <title>Community Page - FoodFlow</title>
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
                <a href="profile.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded  transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-colors duration-300">
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
        <!-- Comming Soon Text that is matching the theam of the page -->
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-primary mb-4">Community Page</h1>
                <p class="text-xl text-primary">This page is under construction. Stay tuned for updates!</p>
            </div>
        </div>

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