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
    <title>FoodFlow-Donations</title>
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
            /* Prevent double scrollbars */
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
    </style>
</head>

<body class="font-sans bg-lightBg text-gray-800 flex">
    <!-- Fixed Sidebar Navigation -->
    <nav class="fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10">
        <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-24 w-auto mb-10">
        <ul class="flex flex-col list-none p-0 ml-5 space-y-12">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Donations</p>
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
        <h1 class="text-3xl font-bold mb-8 pb-2 border-b-4 border-accent inline-block">Donations</h1>

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
                            $receiver_c_number = $receiver_row['c_number'];
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
                            $doner_c_number = $doner_row['c_number'];
                            $doner_address = $doner_row['address'];
                        } else {
                            $doner_name = "Unknown Donor";
                        }
                    }
            ?>
                    <div class="donation-card bg-white rounded-xl shadow-custom w-[48%] mb-6 overflow-hidden group">
                        <div class="flex p-5 items-center">
                            <div class="w-24 h-24 min-w-[6rem] rounded-lg overflow-hidden bg-gray-100 mr-4">
                                <img class="w-full h-full object-cover" src="<?php echo $first_image_path; ?>" alt="Donation Image">
                            </div>

                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-xl font-bold text-primary mb-1">
                                        <?php echo ($type == 'doner') ? htmlspecialchars($receiver_name) : htmlspecialchars($doner_name); ?>
                                    </h3>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-gray-700">Donation Date:</span>
                                        <span class="text-sm ml-2"><?php echo $row["donation_date"]; ?></span>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="text-sm font-semibold text-gray-700 mr-2">Food Type:</span>
                                        <span class="text-xs bg-secondary/10 text-secondary rounded-full px-3 py-1">
                                            <?php echo $row["food_type"]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($type == 'doner' || $type == 'receiver'): ?>
                            <!-- This part only exists if details are to be shown -->
                            <div class="bg-gray-50 px-5 py-0 max-h-0 group-hover:max-h-48 group-hover:py-3 transition-all duration-300 overflow-hidden">
                                <?php if ($type == 'doner'): ?>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="font-semibold text-gray-700">Contact Number:</p>
                                            <p><?php echo htmlspecialchars($receiver_c_number); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Address:</p>
                                            <p><?php echo htmlspecialchars($receiver_address); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                                        <div>
                                            <p class="font-semibold text-gray-700">Daily Count:</p>
                                            <p><?php echo htmlspecialchars($receiver_daily_count); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Requirement Met:</p>
                                            <p><?php echo htmlspecialchars($receiver_req_bool); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                                        <div>
                                            <p class="font-semibold text-gray-700">Food Accepted:</p>
                                            <p><?php echo htmlspecialchars($receiver_accept); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">People Served:</p>
                                            <p><?php echo htmlspecialchars($receiver_req_people); ?></p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="font-semibold text-gray-700">Contact Number:</p>
                                            <p><?php echo htmlspecialchars($doner_c_number); ?></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-700">Address:</p>
                                            <p><?php echo htmlspecialchars($doner_address); ?></p>
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
                <div class="w-full flex flex-col items-center justify-center py-10">
                    <img class="max-w-xl w-full h-auto rounded-2xl mb-6" src="images/no-donations-yet.jpeg" alt="No donations yet">
                    <h2 class="text-2xl font-bold text-primary mb-2">No Donations Yet</h2>
                    <p class="text-gray-600 text-center max-w-md">
                        <?php echo ($type == 'doner') ?
                            "You haven't made any donations yet. Start making a difference today!" :
                            "You haven't received any donations yet. They'll appear here once you do.";
                        ?>
                    </p>
                    <?php if ($type == 'doner'): ?>
                        <button class="mt-4 bg-secondary hover:bg-secondary/90 text-white font-bold py-2 px-6 rounded-full transition-all duration-300 shadow-md hover:shadow-lg">
                            Make a Donation
                        </button>
                    <?php endif; ?>
                </div>
            <?php
            }
            ?>
        </div>

        <!-- Add some bottom padding for better scrolling experience -->
        <div class="h-10"></div>
    </main>
</body>

</html>