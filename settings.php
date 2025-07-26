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
$image_path = "";

// Donor specific variables
$doner_type = $typical_donation = $delivery = "";

// Receiver specific variables
$accept = 0;
$daily_count = 0;
$req_people = 0;
$receiver_type = "";

// Function to delete old image
function deleteOldImage($imagePath) {
    if (!empty($imagePath) && file_exists($imagePath) && $imagePath !== 'images/default-profile.png') {
        unlink($imagePath);
    }
}

// Function to handle image upload
function handleImageUpload($currentImagePath) {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentImagePath; // No new image uploaded, keep current
    }
    
    $uploadDir = 'uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['profile_image'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File size too large. Maximum 5MB allowed.");
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_') . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old image if it exists and is not default
        deleteOldImage($currentImagePath);
        return $uploadPath;
    } else {
        throw new Exception("Failed to upload image.");
    }
}

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
        $image_path = $row['image_path'] ?? '';
        
        if ($type == 'doner') {
            $doner_type = $row['doner_type'] ?? '';
            $typical_donation = $row['typical_donation'] ?? '';
            $delivery = $row['delivary'] ?? ''; // Note: typo in column name
        } else {
            $receiver_type = $row['receiver_type'] ?? '';
            $accept = isset($row['req_bool']) ? (int)$row['req_bool'] : 0;
            $daily_count = isset($row['daily_count']) ? (int)$row['daily_count'] : 0;
            $req_people = isset($row['req_people']) ? (int)$row['req_people'] : 0;
        }
    } else {
        $error = "User data not found.";
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));

    if (empty($name) || empty($phone) || empty($address)) {
        $error = "Name, phone, and address are required.";
    } else {
        try {
            $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';
            
            // Handle image upload
            $newImagePath = handleImageUpload($image_path);
            
            if ($type == 'doner') {
                $doner_type = htmlspecialchars(trim($_POST['doner_type'] ?? ''));
                $typical_donation = htmlspecialchars(trim($_POST['typical_donation'] ?? ''));
                $delivery = htmlspecialchars(trim($_POST['delivery'] ?? ''));
                
                $sql = "UPDATE $table SET name = ?, phone = ?, address = ?, doner_type = ?, typical_donation = ?, delivary = ?, image_path = ? WHERE email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "ssssssss", $name, $phone, $address, $doner_type, $typical_donation, $delivery, $newImagePath, $email);
                
            } else {
                $receiver_type = htmlspecialchars(trim($_POST['receiver_type'] ?? ''));
                $req_people = intval($_POST['req_people'] ?? 0);
                
                // Handle accept status
                if (isset($_POST['accept_status'])) {
                    $accept = ($_POST['accept_status'] === '1') ? 1 : 0;
                } else {
                    $current_sql = "SELECT req_bool FROM $table WHERE email = ?";
                    $current_stmt = mysqli_prepare($conn, $current_sql);
                    mysqli_stmt_bind_param($current_stmt, "s", $email);
                    mysqli_stmt_execute($current_stmt);
                    $current_result = mysqli_stmt_get_result($current_stmt);
                    
                    if ($current_row = mysqli_fetch_assoc($current_result)) {
                        $accept = isset($current_row['req_bool']) ? (int)$current_row['req_bool'] : 0;
                    } else {
                        $accept = 0;
                    }
                    mysqli_stmt_close($current_stmt);
                }
                
                $daily_count = intval($_POST['daily_count'] ?? 0);
                
                $sql = "UPDATE $table SET name = ?, phone = ?, address = ?, receiver_type = ?, req_bool = ?, req_people = ?, daily_count = ?, image_path = ? WHERE email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "ssssiiiss", $name, $phone, $address, $receiver_type, $accept, $req_people, $daily_count, $newImagePath, $email);
            }

            // Execute the update
            if (mysqli_stmt_execute($stmt)) {
                $success = "Profile updated successfully! 🎉";
                $image_path = $newImagePath; // Update the display path
                
                // Re-fetch updated data
                $fetch_sql = "SELECT * FROM $table WHERE email = ?";
                $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
                mysqli_stmt_bind_param($fetch_stmt, "s", $email);
                mysqli_stmt_execute($fetch_stmt);
                $fetch_result = mysqli_stmt_get_result($fetch_stmt);
                
                if ($fetch_row = mysqli_fetch_assoc($fetch_result)) {
                    $name = $fetch_row['name'];
                    $phone = $fetch_row['phone'];
                    $address = $fetch_row['address'];
                    $image_path = $fetch_row['image_path'];
                    
                    if ($type == 'doner') {
                        $doner_type = $fetch_row['doner_type'];
                        $typical_donation = $fetch_row['typical_donation'];
                        $delivery = $fetch_row['delivary'];
                    } else {
                        $receiver_type = $fetch_row['receiver_type'];
                        $accept = (int)$fetch_row['req_bool'];
                        $daily_count = (int)$fetch_row['daily_count'];
                        $req_people = (int)$fetch_row['req_people'];
                    }
                }
                mysqli_stmt_close($fetch_stmt);
                
            } else {
                throw new Exception("Error updating profile: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmt);
            
        } catch (Exception $e) {
            $error = $e->getMessage();
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

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid #c8ed6c;
            transition: all 0.3s ease;
        }

        .image-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .image-upload-container {
            position: relative;
            display: inline-block;
        }

        .image-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .image-upload-container:hover .image-upload-overlay {
            opacity: 1;
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
            <form method="POST" action="settings.php" enctype="multipart/form-data" class="form-card p-8 rounded-2xl shadow-lg w-full max-w-4xl">
                
                <!-- Profile Image Section -->
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-semibold mb-6 text-primary flex flex-start justify-center">
                        <i class="fas fa-user-edit mr-3 text-accent"></i>
                        Profile Information
                    </h2>
                    
                    <div class="image-upload-container mb-4">
                        <img src="<?php echo (!empty($image_path) && file_exists($image_path)) ? htmlspecialchars($image_path) : 'images/default-profile.png'; ?>" 
                            alt="Profile Picture" class="image-preview mx-auto" id="imagePreview" 
                            onerror="this.src='images/default-profile.png';">
                        <div class="image-upload-overlay" onclick="document.getElementById('profile_image').click()">
                            <i class="fas fa-camera text-white text-2xl"></i>
                        </div>
                    </div>
                    
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(this)">
                    <p class="text-sm text-gray-500 mb-2">Click on image to change</p>
                    <p class="text-xs text-gray-400">Max size: 5MB | Formats: JPG, PNG, GIF</p>
                </div>

                <!-- Personal Information -->

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                            <i class="fas fa-user mr-2 text-secondary"></i>
                            Full Name
                        </label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required 
                               class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                    </div>

                    <!-- Phone Field -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                            <i class="fas fa-phone mr-2 text-secondary"></i>
                            Contact Number
                        </label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required 
                               class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                    </div>
                </div>

                <!-- Address Field -->
                <div class="mb-8">
                    <label for="address" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-secondary"></i>
                        Address
                    </label>
                    <textarea id="address" name="address" required rows="3"
                              class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300"><?php echo htmlspecialchars($address); ?></textarea>
                </div>

                <!-- Donor Specific Fields -->
                <?php if ($type == 'doner') : ?>
                    <h2 class="text-2xl font-semibold mb-6 text-primary flex items-center">
                        <i class="fas fa-heart mr-3 text-red-500"></i>
                        Donor Preferences
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Donor Type -->
                        <div>
                            <label for="doner_type" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="fas fa-tags mr-2 text-secondary"></i>
                                Donor Type
                            </label>
                            <select id="doner_type" name="doner_type" 
                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                                <option value="">Select Type</option>
                                <option value="individual" <?php echo $doner_type == 'individual' ? 'selected' : ''; ?>>Individual</option>
                                <option value="restaurant" <?php echo $doner_type == 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                                <option value="hotel" <?php echo $doner_type == 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                                <option value="catering" <?php echo $doner_type == 'catering' ? 'selected' : ''; ?>>Catering Service</option>
                                <option value="grocery" <?php echo $doner_type == 'grocery' ? 'selected' : ''; ?>>Grocery Store</option>
                                <option value="event" <?php echo $doner_type == 'event' ? 'selected' : ''; ?>>Event Organizer</option>
                            </select>
                        </div>

                        <!-- Delivery Option -->
                        <div>
                            <label for="delivery" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="fas fa-truck mr-2 text-secondary"></i>
                                Delivery Option
                            </label>
                            <select id="delivery" name="delivery" 
                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                                <option value="">Select Option</option>
                                <option value="pickup" <?php echo $delivery == 'pickup' ? 'selected' : ''; ?>>Pickup Only</option>
                                <option value="delivery" <?php echo $delivery == 'delivery' ? 'selected' : ''; ?>>Can Deliver</option>
                                <option value="both" <?php echo $delivery == 'both' ? 'selected' : ''; ?>>Both Options</option>
                            </select>
                        </div>
                    </div>

                    <!-- Typical Donation -->
                    <div class="mb-8">
                        <label for="typical_donation" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                            <i class="fas fa-utensils mr-2 text-secondary"></i>
                            Typical Donation
                        </label>
                        <textarea id="typical_donation" name="typical_donation" rows="3" placeholder="Describe the type of food you typically donate..."
                                  class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300"><?php echo htmlspecialchars($typical_donation); ?></textarea>
                    </div>
                <?php endif; ?>

                <!-- Receiver Specific Fields -->
                <?php if ($type == 'foodreceiver') : ?>
                    <h2 class="text-2xl font-semibold mb-6 text-primary flex items-center">
                        <i class="fas fa-hands-helping mr-3 text-blue-500"></i>
                        Receiver Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Receiver Type -->
                        <div>
                            <label for="receiver_type" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="fas fa-building mr-2 text-secondary"></i>
                                Organization Type
                            </label>
                            <select id="receiver_type" name="receiver_type" 
                                    class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                                <option value="">Select Type</option>
                                <option value="ngo" <?php echo $receiver_type == 'ngo' ? 'selected' : ''; ?>>NGO</option>
                                <option value="shelter" <?php echo $receiver_type == 'shelter' ? 'selected' : ''; ?>>Shelter</option>
                                <option value="orphanage" <?php echo $receiver_type == 'orphanage' ? 'selected' : ''; ?>>Orphanage</option>
                                <option value="elderly_home" <?php echo $receiver_type == 'elderly_home' ? 'selected' : ''; ?>>Elderly Home</option>
                                <option value="community_center" <?php echo $receiver_type == 'community_center' ? 'selected' : ''; ?>>Community Center</option>
                                <option value="individual" <?php echo $receiver_type == 'individual' ? 'selected' : ''; ?>>Individual</option>
                                <option value="food_bank" <?php echo $receiver_type == 'food_bank' ? 'selected' : ''; ?>>Food Bank</option>
                                <option value="religious_org" <?php echo $receiver_type == 'religious_org' ? 'selected' : ''; ?>>Religious Organization</option>
                            </select>
                        </div>

                        <!-- Required People -->
                        <div>
                            <label for="req_people" class="block text-sm font-semibold mb-2 text-gray-700 flex items-center">
                                <i class="fas fa-users mr-2 text-secondary"></i>
                                People Served Daily
                            </label>
                            <input type="number" id="req_people" name="req_people" value="<?php echo $req_people; ?>" 
                                   min="0" max="1000"
                                   class="w-full border-2 border-gray-200 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-300">
                        </div>
                    </div>

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
                        
                        <!-- Hidden input to store toggle state -->
                        <input type="hidden" id="accept_status" name="accept_status" value="<?php echo $accept; ?>">
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
            <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 max-w-4xl">
                <h3 class="text-lg font-semibold mb-4 text-red-800 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-red-600"></i>
                    Danger Zone
                </h3>
                
                <div class="bg-white/70 rounded-lg p-4 mb-4">
                    <p class="text-red-600 text-sm mb-3">
                        Once you delete your account, there is no going back. This action will permanently remove all your data, including your profile information, donation history, and uploaded images.
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

        // Image preview functionality
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Toggle switch functionality for receivers
        <?php if ($type == 'foodreceiver') : ?>
        function toggleStatus() {
            const toggle = document.querySelector('.toggle-switch');
            const hiddenInput = document.getElementById('accept_status');
            const statusIndicator = document.querySelector('.status-indicator');
            const statusText = statusIndicator.nextElementSibling;
            
            // Toggle the visual state
            toggle.classList.toggle('active');
            
            // Update the hidden input value based on toggle state
            const isActive = toggle.classList.contains('active');
            hiddenInput.value = isActive ? '1' : '0';
            
            // Update status display
            if (isActive) {
                statusIndicator.className = 'status-indicator status-active';
                statusText.className = 'text-sm font-medium text-green-600';
                statusText.textContent = 'Accepting';
            } else {
                statusIndicator.className = 'status-indicator status-inactive';
                statusText.className = 'text-sm font-medium text-red-600';
                statusText.textContent = 'Not Accepting';
            }
            
            // console.log('Toggle switched. New value:', hiddenInput.value);
        }

        // Daily count validation
        document.getElementById('daily_count')?.addEventListener('input', function() {
            const reqPeople = <?php echo $req_people; ?>;
            if (this.value > reqPeople && reqPeople > 0) {
                this.value = reqPeople;
            }
        });

        // Update max value when req_people changes
        document.getElementById('req_people')?.addEventListener('input', function() {
            const dailyCountInput = document.getElementById('daily_count');
            if (dailyCountInput) {
                dailyCountInput.max = this.value;
                if (parseInt(dailyCountInput.value) > parseInt(this.value)) {
                    dailyCountInput.value = this.value;
                }
            }
        });
        <?php endif; ?>

        // Delete account confirmation
        function confirmDelete() {
            if (confirm('Are you absolutely sure you want to delete your account?\n\nThis action cannot be undone and will permanently remove all your data including:\n- Profile information\n- Donation history\n- Uploaded images\n- All associated records')) {
                const finalConfirm = prompt('Please type "DELETE" to confirm account deletion:');
                if (finalConfirm === 'DELETE') {
                    window.location.href = 'delete_account.php';
                } else if (finalConfirm !== null) {
                    alert('Account deletion cancelled - text did not match "DELETE".');
                }
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            
            if (!name || !phone || !address) {
                e.preventDefault();
                alert('Please fill in all required fields (Name, Phone, Address).');
                return false;
            }
            
            // Phone number validation (basic)
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number.');
                return false;
            }
            
            <?php if ($type == 'foodreceiver') : ?>
            const reqPeople = parseInt(document.getElementById('req_people').value);
            const dailyCount = parseInt(document.getElementById('daily_count').value);
            
            if (reqPeople < 0 || dailyCount < 0) {
                e.preventDefault();
                alert('Please enter valid positive numbers for people counts.');
                return false;
            }
            
            if (dailyCount > reqPeople && reqPeople > 0) {
                e.preventDefault();
                alert('Daily count cannot exceed the total number of people served.');
                return false;
            }
            <?php endif; ?>
            
            return true;
        });
        
    </script>
</body>
</html>