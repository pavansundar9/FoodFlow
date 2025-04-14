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
$name = $c_number = $address = $error = $success = "";

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
        $c_number = $row['c_number'];
        $address = $row['address'];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $c_number = htmlspecialchars(trim($_POST['c_number']));
    $address = htmlspecialchars(trim($_POST['address']));

    if (empty($name) || empty($c_number) || empty($address)) {
        $error = "All fields are required.";
    } else {
        $table = ($type == 'doner') ? 'fooddoners' : 'foodreceivers';
        $sql = "UPDATE $table SET name = ?, c_number = ?, address = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $c_number, $address, $email);

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
    </style>
</head>

<body class="font-sans bg-lightBg text-gray-800 flex">
    <!-- Sidebar Navigation -->
    <nav class="fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10">
        <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-24 w-auto mb-10">
        <ul class="flex flex-col list-none p-0 ml-5 space-y-12">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Profile</p>
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
                <a href="settings.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Settings</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="logout.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Logout</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Settings Form -->
    <main class="ml-[20%] flex-1 p-8 h-screen overflow-y-auto scrollable-content">
        <h2 class="text-3xl font-bold mb-8 pb-2 border-b-4 border-accent inline-block">Settings</h2>

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
                <label for="c_number" class="block text-sm font-semibold mb-1">Contact Number</label>
                <input type="text" id="c_number" name="c_number" value="<?php echo $c_number; ?>" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <div class="mb-4">
                <label for="address" class="block text-sm font-semibold mb-1">Address</label>
                <input type="text" id="address" name="address" value="<?php echo $address; ?>" required class="w-full border border-gray-300 px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-accent">
            </div>

            <button type="submit" class="bg-secondary text-white px-6 py-2 rounded shadow hover:bg-secondary/90 transition-all">Update</button>
        </form>
    </main>
</body>

</html>
