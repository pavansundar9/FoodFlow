<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first');</script>";
    echo "<script>window.location.href='login.php';</script>";
    exit();
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

// Initialize variables
$email = $_SESSION['email'];
$type = $_SESSION['type'];
$name = $phone = $address = $error = $success = "";

// Fetch user details
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';
    $sql = "SELECT * FROM $table WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        $phone = $row['phone'];
        $address = $row['address'];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));

    if (empty($name) || empty($phone) || empty($address)) {
        $error = "All fields are required.";
    } else {
        $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';
        $sql = "UPDATE $table SET name = ?, phone = ?, address = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $address, $email);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Profile updated successfully.";
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings - FoodFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />
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
                <a href="community.html" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
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


    <!-- Settings Form -->
    <main class="main-content lg:ml-[20%] min-w-0 flex-1 p-4 sm:p-6 lg:p-8 h-screen overflow-y-auto scrollable-content">
        <h1 class="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8 pb-2 border-b-4 border-accent inline-block mt-12 lg:mt-0">Settings</h1>
     
        <?php if (!empty($error)) : ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 shadow"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)) : ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4 shadow"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="settings.php" class="bg-white p-6 rounded-lg shadow-md w-full max-w-xl">
            <div class="mb-4">
                <label for="name" class="block text-sm font-semibold mb-1">Name</label>
                <input type="text" id="name" name="name" value="<?php echo $name; ?>" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-sm font-semibold mb-1">Contact Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <div class="mb-4">
                <label for="address" class="block text-sm font-semibold mb-1">Address</label>
                <input type="text" id="address" name="address" value="<?php echo $address; ?>" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <button type="submit" class="bg-secondary text-white px-6 py-2 rounded shadow hover:bg-secondary/90 transition-all">Update</button>
        </form>
        <div class="mt-8">
            <a href="logout.php" class="bg-red-500 text-white text-lg px-6 py-3 rounded-lg hover:bg-red-600 transition">
                Logout
            </a>
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