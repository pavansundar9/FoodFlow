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
    $donations_table = "SELECT * FROM donations WHERE receiver = '$email'";
    $donations_result = mysqli_query($conn,$donations_table);
    // $donations = mysqli_fetch_all($donations_result,MYSQLI_ASSOC);

    // $doners_table = "SELECT name FROM fooddoners";
    // $doners_result = mysqli_query($conn,$doners_table);

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
    <nav class="flex flex-col items-start w-[25%] h-screen p-5 bg-[#17272b]">
        <img src="images/foodflow-logo.png" alt="logo" class="h-24 w-48 mb-5">
        <ul class="flex flex-col list-none p-0 ml-5">
            <li class="mb-16 text-xl">
                <a href="profile.php" class="text-white no-underline"><p class="underline-animation">Profile</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="donations.php" class="block text-black no-underline bg-white p-4 rounded"><p class="underline-animation">Donations</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="community.html" class="text-white no-underline"><p class="underline-animation">Community Page</p></a>
            </li>
            <li class="mb-16 text-xl">
                <a href="settings.html" class="text-white no-underline"><p class="underline-animation">Settings</p></a>
            </li>
        </ul>
    </nav>    
    <!-- <div class="border-l-2 border-gray-300 h-screen mt-24"></div> -->
    
    <main class="flex flex-col w-[75%]  p-5 w-full">
        <h1 class="mt-9 text-[30px] font-bold ml-7 mb-10">Donations</h1>
        <div class="flex flex-wrap justify-between  border-sky-200">
            <?php
                if(mysqli_num_rows($donations_result)>0){
                    while($row = mysqli_fetch_assoc($donations_result)){
                        echo '<div class="flex bg-gray-200 rounded-md items-center w-[45%] h-44 p-4 m-4 group hover:p-8 hover:w-[48%] transition-all duration-300">';
                            $image_paths = explode(',', $row["donation_img"]);
                            $first_image_path = $image_paths[0]; // Get the first image path
                            
                            echo '<img class="w-[100px] h-[100px] bg-red-200" src="' . $first_image_path . '" alt="">';
                            echo '<div class="flex flex-col t-0">';
                            $doner_email = $row["doner"];
                            $doner_name_query = "SELECT name FROM fooddoners WHERE email = '$doner_email'";

                            // Assuming you have a MySQLi connection named $conn
                            $result = $conn->query($doner_name_query);
                            if ($result->num_rows > 0) {
                                $doner_row = $result->fetch_assoc();
                                $doner_name = $doner_row['name'];
                                // echo '<p class="text-2xl ml-5">' . htmlspecialchars($doner_name) . '</p>';
                            } else {
                                // echo '<p class="text-2xl ml-5">Name not found</p>';
                            }
                                echo '<p class="text-2xl ml-5">'.$doner_name.'</p>';
                                echo '<div class="flex items-center ml-5">';
                                    echo '<p class="text-l font-bold">Donation Date:</p>';
                                    echo '<p class="text-l ml-2">'.$row["donation_date"].'</p>';
                                echo '</div>';
                                echo '<div class="flex items-center ml-5">';
                                    echo '<p class="text-l font-bold">Food Type:</p>';
                                    echo '<p class="text-sm bg-gray-200 rounded-md p-2">'.$row["food_type"].'</p>';
                                echo '</div>';
                            echo '</div>';
                            echo '<button class="hidden group-hover:block self-end hover:bg-teal-950 text-white w-10 ml-10 mt-3 rounded-md p-2">
                                    <img src="images/right-arrow.png" alt="right-arrow" class="w-10">
                                </button>';
                        echo '</div>';
                    }
                }
                else {
                    // echo "No Donations Yet.<br>";
                    echo "<img class='bg-opacity-70 w-full h-auto rounded-2xl self-center text-center max-w-[800px]' src='images/no-donations-yet.jpeg' alt='No donations yet'>";
                }
            ?>
    </main>
</body>
</html>
