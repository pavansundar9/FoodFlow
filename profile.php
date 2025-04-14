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

$image_path = $name = $c_number = $address = $user_type = $daily_count = $req_bool = $accept = $req_people = $doner_type = $typical_donation = $delivery = '';
$default_image = 'uploads/food-bank-logo.png';

if (isset($_SESSION['type'])) {
    echo "<script>console.log('The session type is, " . $_SESSION['type'] . "');</script>";
    if ($_SESSION['type'] == 'foodreceiver') {
        $sql = "SELECT * FROM foodreceivers WHERE email = '$email'";
    } elseif ($_SESSION['type'] == 'doner') {
        $sql = "SELECT * FROM fooddoners WHERE email = '$email'";
    }
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "<script>console.log('The data fetched is, " . json_encode($row) . "');</script>";

        // Common fields
        $name = $row['name'];
        $c_number = $row['c_number'];
        $address = $row['address'];
        $image_path = $row['image_path'];
        $user_type = $_SESSION['type'];
        $final_image = (!empty($image_path) && file_exists($image_path)) ? $image_path : $default_image;

        if ($user_type == 'receiver') {
            // Receiver-specific fields
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
        echo '<script>console.log("No records found.");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFlow-Profile</title>
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

        /* Custom scrollbar for main content */
        /* .scrollable-content::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .scrollable-content::-webkit-scrollbar-thumb {
            background: #c8ed6c;
            border-radius: 4px;
        }

        .scrollable-content::-webkit-scrollbar-thumb:hover {
            background: #a9d24c;
        } */
    </style>
</head>
<body class="font-sans bg-lightBg text-gray-800 flex">
    <!-- Fixed Sidebar Navigation -->
    <nav class="fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10">
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
            <li class="text-xl">
                <a href="logout.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Logout</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Scrollable Main Content -->
    <main class="ml-[20%] min-w-0 flex-1 p-8 h-screen overflow-y-auto scrollable-content">
        <h1 class="text-3xl font-bold mb-8 pb-2 border-b-4 border-accent inline-block">Profile</h1>
        
        <!-- Profile Header -->
        <div class="flex items-center mb-10 flex-wrap md:flex-nowrap">
            <img src="<?php echo $final_image; ?>" alt="Profile Image" class="rounded-xl w-36 h-36 md:w-40 md:h-40 object-cover mr-5 border-4 border-accent shadow-custom">
            <div class="flex flex-col mt-4 md:mt-0">
                <p class="font-bold text-2xl mb-2 text-primary"><?php echo $name; ?></p>
                <span class="text-secondary text-sm italic bg-secondary/10 px-3.5 py-1 rounded-full inline-block">
                    <?php echo ucfirst($user_type); ?>
                </span>
            </div>
        </div>
        
        <!-- Profile Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Address Panel -->
                <div class="bg-white rounded-xl p-6 shadow-custom">
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b-2 border-accent text-primary">Address</h2>
                    <p class="leading-relaxed"><?php echo $address; ?></p>
                </div>
                
                <!-- About Section -->
                <div class="bg-white rounded-xl p-6 shadow-custom">
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b-2 border-accent text-primary">
                        <?php echo ($user_type == 'receiver') ? 'About Us' : 'About Your Donations'; ?>
                    </h2>
                    
                    <?php if ($user_type == 'receiver'): ?>
                    <p class="leading-relaxed">
                        The <?php echo $name; ?> is a passionate food bank dedicated to combatting hunger and food waste in the Guntur area. Through our relentless efforts, we collect surplus food from local businesses, farmers, and residents, redirecting it to those in need. Embracing the strength of community, we organize regular food drives and events to raise awareness about food insecurity while promoting sustainable practices. Your contributions, whether big or small, play a vital role in our mission to create a more food-secure environment.
                    </p>
                    <?php else: ?>
                    <p class="leading-relaxed">
                        Thank you for being a valued food donor! Your contributions make a significant impact on our community by reducing food waste and helping those in need. As a <?php echo $doner_type; ?>, your donations of <?php echo $typical_donation; ?> help us serve our community better.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column - Details -->
            <div class="space-y-6">
                <!-- Contact Info Panel -->
                <div class="bg-white rounded-xl p-6 shadow-custom">
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b-2 border-accent text-primary">Contact Info</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Email</p>
                            <p class="text-gray-800"><?php echo $email; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Contact Number</p>
                            <p class="text-gray-800"><?php echo $c_number; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- User Type Specific Panel -->
                <div class="bg-white rounded-xl p-6 shadow-custom">
                    <h2 class="text-xl font-bold mb-4 pb-2 border-b-2 border-accent text-primary">
                        <?php echo ($user_type == 'receiver') ? 'Food Bank Details' : 'Donor Details'; ?>
                    </h2>
                    
                    <?php if ($user_type == 'receiver'): ?>
                    <!-- Receiver specific content -->
                    <div class="space-y-4">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Food Types Accepted</p>
                            <p class="text-gray-800"><?php echo $accept; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Currently Accepting Donations</p>
                            <p class="text-gray-800"><?php echo $req_bool; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Today's Donations</p>
                            <div class="progress-bar mt-2 mb-1">
                                <div class="progress-fill" style="width: <?php echo ($daily_count / $req_people) * 100; ?>%"></div>
                            </div>
                            <p class="text-right text-sm">
                                <span class="font-bold text-secondary"><?php echo $daily_count; ?></span> 
                                of 
                                <span class="font-bold"><?php echo $req_people; ?></span>
                            </p>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Donor specific content -->
                    <div class="space-y-4">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Donor Type</p>
                            <span class="inline-block bg-accent/20 text-primary font-semibold px-3 py-1 rounded-full text-sm">
                                <?php echo $doner_type; ?>
                            </span>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Typical Donation</p>
                            <p class="text-gray-800"><?php echo $typical_donation; ?></p>
                        </div>
                        
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Delivery Option</p>
                            <div class="flex items-center text-gray-800 mt-1">
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
</body>
</html>
