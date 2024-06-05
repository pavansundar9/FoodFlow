<?php
session_start();
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
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

if(isset($_SESSION['type'])){
    $sql = "SELECT * FROM foodreceivers";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    $image_path = $row['image_path'];
    $receiver_name = $row['name'];
    $c_number = $row['c_number'];
    $address = $row['address'];
    $receiver_type = $row['receiver_type'];
    $req_bool = $row['req_bool'];
    $accept = $row['accept'];
    $req_people = $row['req_people'];
    $count = $row['daily_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="flex m-0">
    <nav class="flex flex-col items-start w-[20.7%]  p-5 bg-[#17272b]">
        <img src="images/foodflow-logo.png" alt="logo" class="h-24 w-48 mb-5">
        <ul class="flex flex-col list-none p-0 ml-5">
            <li class="mb-16 text-xl">
                <a href="profile.php" class="block text-black no-underline bg-white p-4 rounded"><p class="underline-animation">Profile</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="donations.php" class="text-white no-underline"><p class="underline-animation">Donations</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="community.html" class="text-white no-underline"><p class="underline-animation">Community Page</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="settings.html" class="text-white no-underline"><p class="underline-animation">Settings</p></a>
            </li>
        </ul>
    </nav>    
    <div class="border-l-2 border-gray-300 h-screen mt-24"></div>
    <main class="flex flex-col p-5 flex-1">
        <h1 class="mt-9 text-[30px] font-bold ml-7">Profile</h1>
        <div class="flex items-center">
            <img src="<?php echo $image_path; ?>" alt="user-dp" class="rounded-md w-36 h-36 object-cover mr-5">
            <div class="flex flex-col items-start">
                <p class="font-bold mb-2 text-xl"><?php echo $receiver_name; ?></p>
                <p class="text-slate-800 text-sm italic"><?php echo $receiver_type; ?></p>
            </div>
        </div>
        <div class="flex gap-5">
            <div class="w-3/4">
                <div class="rounded-md bg-gray-100 p-2 w-auto mb-5">
                    <p class="m-2 text-xl font-bold">Address</p>
                    <p class="m-3">
                        <?php echo $address; ?>`
                    </p>
                </div>
                <div class="rounded-md bg-gray-100 p-2 w-auto">
                    <p class="m-2 text-xl font-bold">About Your Self</p>
                    <p class="m-3">
                        The <?php echo $receiver_name; ?> is a passionate food bank dedicated to combatting hunger and food waste in the Guntur area. Through our relentless efforts, we collect surplus food from local businesses, farmers, and residents, redirecting it to those in need. Embracing the strength of community, we organize regular food drives and events to raise awareness about food insecurity while promoting sustainable practices. Your contributions, whether big or small, play a vital role in our mission to create a more food-secure environment.<br><br>
                        Join us in our endeavor to make a tangible difference in the lives of individuals and families facing hunger. Your support empowers us to provide nutritious meals to those in need, fostering a stronger, healthier community for all. Together, let's transform surplus food into hope and nourishment for those who need it most.
                    </p>
                </div>
            </div>
            <div class="bg-gray-100 rounded-md p-4 w-96 h-[18rem]">
                <p class="m-2 text-l font-bold">Email</p>
                <p class="m-3"><?php echo $email; ?></p>

                <!-- <div class="flex items-center"> -->
                    <p class="m-2 text-l font-bold">Type of food accepted:</p>
                    <p class="m-3"> <?php if($accept == 'all') echo  "All types of food"; else echo "Only " . $accept; ?></p>
                <!-- </div> -->
                <div class="flex items-center">
                    <p class="m-2 text-l font-bold">Requirement:</p>
                    <p class="font-normal"> <?php echo $req_bool ?></p>
                </div>
                <div class="flex items-center">
                    <p class="m-2 text-l font-bold">Today's Donations:</p>
                    <p class="font-normal"> <?php  echo $count ."/".$req_people; ?> </p>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
