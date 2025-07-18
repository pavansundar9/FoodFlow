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
$accept = 1;
$daily_count = 0;
$req_people = 0;

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
        
        if ($type == 'foodreceiver') {
            $accept = isset($row['accept']) ? $row['accept'] : 1;
            $daily_count = isset($row['daily_count']) ? $row['daily_count'] : 0;
            $req_people = isset($row['req_people']) ? $row['req_people'] : 0;
        }
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
        
        if ($type == 'foodreceiver') {
            $accept = isset($_POST['accept']) ? 1 : 0;
            $daily_count = intval($_POST['daily_count']);
            $sql = "UPDATE $table SET name = ?, phone = ?, address = ?, accept = ?, daily_count = ? WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssiis", $name, $phone, $address, $accept, $daily_count, $email);
        } else {
            $sql = "UPDATE $table SET name = ?, phone = ?, address = ? WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $address, $email);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success = "Profile updated successfully! 🎉";
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
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
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
                        'glow': '0 0 20px rgba(200, 237, 108, 0.3)',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background-color: #ccc;
            border-radius: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-switch.active {
            background-color: #c8ed6c;
        }

        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(30px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #f9f9f9 0%, #e8f5e8 100%);
        }

        .form-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }

        .status-active {
            background-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        .status-inactive {
            background-color: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
        }

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

<body class="font-sans gradient-bg text-gray-800">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="fixed top-4 left-4 z-30 bg-primary text-white p-2 rounded-md lg:hidden">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Sidebar -->
    <nav id="mobile-sidebar" class="mobile-menu fixed left-0 top-0 w-64 h-screen bg-primary p-5 z-20 lg:hidden">
        <div class="flex justify-between items-center mb-8">
            <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-16 w-auto">
            <button id="close-mobile-menu" class="text-white text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="flex flex-col list-none p-0 space-y-6">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="block text-black no-underline bg-white p-3 rounded shadow-md">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Desktop Sidebar -->
    <nav class="desktop-sidebar fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10 hidden lg:block">
        <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-24 w-auto mb-10">
        <ul class="flex flex-col list-none p-0 ml-5 space-y-12">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="block text-black no-underline bg-white p-4 rounded shadow-md">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-10 hidden lg:hidden"></div>

    <!-- Main Content -->
    <main class="main-content lg:ml-[20%] min-w-0 flex-1 p-4 sm:p-6 lg:p-8 h-screen overflow-y-auto">
        <!-- Header -->
        <div class="fade-in-up">
            <h1 class="text-3xl sm:text-4xl font-bold mb-2 pb-2 border-b-4 border-accent inline-block mt-12 lg:mt-0 flex items-center">
                <i class="fas fa-cog mr-3 text-secondary"></i>
                Settings
            </h1>
            <p class="text-gray-600 mb-8 text-lg">Customize your FoodFlow experience</p>
        </div>
     
        <!-- Alert Messages -->
        <?php if (!empty($error)) : ?>
            <div class="fade-in-up bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6 shadow-md flex items-center">
                <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)) : ?>
            <div class="fade-in-up bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6 shadow-md flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-500"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <div class="fade-in-up">
            <form method="POST" action="settings.php" class="form-card p-8 rounded-2xl shadow-lg w-full max-w-2xl">
                <h2 class="text-2xl font-semibold mb-6 text-primary flex items-center">
                    <i class="fas fa-user-edit mr-3 text-accent"></i>
                    Personal Information
                </h2>

                <!-- Name Field -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                        <i class="fas fa-user mr-2 text-secondary"></i>
                        Full Name
                    </label>
                    <input type="text" id="name" name="name" value="<?php echo $name; ?>" required 
                           class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                </div>

                <!-- Phone Field -->
                <div class="mb-6">
                    <label for="phone" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                        <i class="fas fa-phone mr-2 text-secondary"></i>
                        Contact Number
                    </label>
                    <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>" required 
                           class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                </div>

                <!-- Address Field -->
                <div class="mb-6">
                    <label for="address" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-secondary"></i>
                        Address
                    </label>
                    <input type="text" id="address" name="address" value="<?php echo $address; ?>" required 
                           class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                </div>

                <!-- Receiver Fields -->
                <?php if ($type == 'foodreceiver') : ?>
                    <!-- Donation Status -->
                    <div class="mb-6 p-6 bg-gradient-to-r from-accent/10 to-accent/20 rounded-xl border-2 border-accent/30">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-primary flex items-center">
                                <i class="fas fa-toggle-on mr-3 text-accent"></i>
                                Donation Status
                            </h3>
                            <div class="flex items-center">
                                <span class="status-indicator <?php echo $accept ? 'status-active' : 'status-inactive'; ?>"></span>
                                <span class="text-sm font-medium <?php echo $accept ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $accept ? 'Accepting' : 'Not Accepting'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-heart mr-3 text-red-500"></i>
                                <span class="text-gray-700 font-medium">Accept Donations</span>
                            </div>
                            <div class="toggle-switch <?php echo $accept ? 'active' : ''; ?>" onclick="toggleStatus()">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>
                        
                        <input type="checkbox" id="accept" name="accept" 
                               <?php echo $accept ? 'checked' : ''; ?> style="display: none;">
                    </div>

                    <!-- Daily Count -->
                    <div class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200">
                        <h3 class="text-lg font-semibold mb-4 text-primary flex items-center">
                            <i class="fas fa-chart-pie mr-3 text-blue-500"></i>
                            Daily Count Tracking
                        </h3>
                        
                        <div class="mb-4">
                            <label for="daily_count" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="fas fa-utensils mr-2 text-blue-500"></i>
                                People Fed Today
                            </label>
                            <input type="number" id="daily_count" name="daily_count" value="<?php echo $daily_count; ?>" 
                                   min="0" max="<?php echo $req_people; ?>" 
                                   class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                        </div>
                        
                        <?php if ($req_people > 0) : ?>
                            <div class="bg-white/70 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">
                                        Progress: <?php echo $daily_count; ?> / <?php echo $req_people; ?> people
                                    </span>
                                    <span class="text-sm font-semibold text-blue-600">
                                        <?php echo $req_people > 0 ? round(($daily_count / $req_people) * 100) : 0; ?>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300" 
                                         style="width: <?php echo min(($daily_count / $req_people) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8">
                    <button type="submit" class="bg-gradient-to-r from-secondary to-secondary/80 text-white px-8 py-4 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 font-semibold text-lg flex items-center justify-center">
                        <i class="fas fa-save mr-3"></i>
                        Update Settings
                    </button>
                    
                    <a href="logout.php" class="bg-red-500 text-white text-lg px-6 py-4 rounded-lg hover:bg-red-600 transition-all duration-300 inline-flex items-center justify-center shadow-md hover:shadow-lg">
                        <i class="fas fa-door-open mr-3"></i>
                        Logout
                    </a>
                </div>
            </form>
        </div>

        <!-- Delete Account Section -->
        <div class="fade-in-up mt-8">
            <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 max-w-2xl">
                <h3 class="text-lg font-semibold mb-4 text-red-800 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-red-600"></i>
                    Danger Zone
                </h3>
                
                <div class="bg-white/70 rounded-lg p-4 mb-4">
                    <p class="text-red-600 text-sm mb-3">
                        Once you delete your account, there is no going back. This action will permanently remove all your data.
                    </p>
                    
                    <button onclick="confirmDelete()" class="bg-red-700 text-white px-6 py-3 rounded-lg hover:bg-red-800 transition-all duration-300 inline-flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-trash-alt mr-3"></i>
                        Delete Account
                    </button>
                </div>
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
            document.body.style.overflow = 'hidden';
            mobileMenuButton.classList.add('hidden');
        });
        
        function closeMenu() {
            mobileSidebar.classList.remove('open');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            mobileMenuButton.classList.remove('hidden');
        }
        
        closeMobileMenu.addEventListener('click', closeMenu);
        mobileOverlay.addEventListener('click', closeMenu);
        
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeMenu();
            }
        });

        // Toggle switch functionality
        function toggleStatus() {
            const toggle = document.querySelector('.toggle-switch');
            const checkbox = document.getElementById('accept');
            const statusIndicator = document.querySelector('.status-indicator');
            const statusText = statusIndicator.nextElementSibling;
            
            toggle.classList.toggle('active');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                statusIndicator.className = 'status-indicator status-active';
                statusText.className = 'text-sm font-medium text-green-600';
                statusText.textContent = 'Accepting';
            } else {
                statusIndicator.className = 'status-indicator status-inactive';
                statusText.className = 'text-sm font-medium text-red-600';
                statusText.textContent = 'Not Accepting';
            }
        }

        // Delete account confirmation
        function confirmDelete() {
            if (confirm('Are you absolutely sure you want to delete your account?\n\nThis action cannot be undone and will permanently remove all your data.')) {
                const finalConfirm = prompt('Please type "DELETE" to confirm account deletion:');
                if (finalConfirm === 'DELETE') {
                    window.location.href = 'delete_account.php';
                } else if (finalConfirm !== null) {
                    alert('Account deletion cancelled - text did not match "DELETE".');
                }
            }
        }

        // Daily count validation
        document.getElementById('daily_count')?.addEventListener('input', function() {
            const reqPeople = <?php echo $req_people; ?>;
            if (this.value > reqPeople && reqPeople > 0) {
                this.value = reqPeople;
            }
        });
    </script>
</body>
</html>