<?php
session_start(); // Starting Session
$receiver_password = $conform = $error = $type = '';

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_password = isset($_POST["password"]) ? $_POST["password"] : "";
    $conform = isset($_POST["confirm-password"]) ? $_POST["confirm-password"] : "";

    // Handle image upload
    $image_name = isset($_FILES["image"]["name"]) ? $_FILES["image"]["name"] : "";
    $image_tmp = isset($_FILES["image"]["tmp_name"]) ? $_FILES["image"]["tmp_name"] : "";
    $image_type = isset($_FILES["image"]["type"]) ? $_FILES["image"]["type"] : "";
     
    // Move uploaded file to a folder
    if (!empty($image_name) && !empty($image_tmp)) {
        $upload_directory = "uploads/";
        $target_file = $upload_directory . basename($image_name);
        move_uploaded_file($image_tmp, $target_file);
    }

    if (empty(trim($receiver_password)) || empty(trim($conform))) {
        $error = "Please fill out all the fields.";
    } elseif ($receiver_password !== $conform) {
        $error = "Passwords do not match. Please try again.";
    } elseif (!preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()]).{8,}$/", $receiver_password)) {
        $error = "Password must contain at least 8 characters, 1 number, 1 lowercase letter, 1 uppercase letter, and 1 special symbol.";
    } else {
        if ($type == 'doner') {
            $table = 'fooddoners';
            $location = 'doner_extra_info.php';
        } else {
            $table = 'foodreceivers';
            $location = 'receiver_extra_info.php';
        }

        // Prepared statement to prevent SQL injection
        $sql = "SELECT * FROM $table WHERE email=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            $row = mysqli_fetch_array($result);
            if ($row) {
                // Update password
                $hashed_password = password_hash($receiver_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE $table SET password=?, image_path=? WHERE email=?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "sss", $hashed_password, $target_file, $email);
                mysqli_stmt_execute($stmt_update);
                $_SESSION['loggedin'] = true;
                header("Location: $location");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="images/food-flow-icon.png">
    <link rel="stylesheet" href="signup.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kaushan+Script&family=Meddon&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons&display=swap" rel="stylesheet">
    <!-- FontAwesome  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Create a Password</title>
</head>
<body>
<div class="password-creation">
    <div class="form">
        <center><h2>Create Password</h2></center>
        <form name="form" id="form" action="" method="POST" enctype="multipart/form-data">
            <?php
            if (!empty($error)) {
                echo '<p style="color: red;">' . htmlspecialchars($error) . '</p>';
            }
            ?><br>
            <div class="user-pe">
                <div class="profile-pic">
                    <div class="pic">
                        <!-- Placeholder for image preview -->
                    </div>
                    <div class="file-input">
                        <input type="file" id="upload" name="image" class="inputfile" onchange="updateFileName(this)" />
                        <label for="upload"><i class="fas fa-camera"></i> Upload Image</label>
                    </div>
                </div>
                <div class="user-details">
                    <?php echo '<h3>'.$email.'</h3>';?>
                </div>
            </div>
            <div class="input-wrapper">
                <input type="password" class="input" id="password" name="password" placeholder=" " pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Password must contain at least one number, one uppercase and one lowercase letter, and at least 8 or more characters" required>
                <label class="label" for="password">Enter Password</label>
                <i class="fas fa-eye-slash" id="password-toggle" onclick="togglePasswordVisibility('password','password-toggle')"></i>
            </div><br><br>
            <div class="input-wrapper">
                <input type="password" class="input" id="confirm-password" name="confirm-password" placeholder=" ">
                <label class="label" for="confirm-password">Confirm Password</label>
                <i class="fas fa-eye-slash" id="confirm-toggle" onclick="togglePasswordVisibility('confirm-password','confirm-toggle')"></i>
            </div>
            <div class="password-check">
                <div class="content">
                    <p>Password must contain:</p>
                    <ul class="requirement-list">
                        <li><i class="fas fa-check-circle"></i> At least 8 characters length</li>
                        <li><i class="fas fa-check-circle"></i> At least 1 number (0...9)</li>
                        <li><i class="fas fa-check-circle"></i> At least 1 lowercase letter (a...z)</li>
                        <li><i class="fas fa-check-circle"></i> At least 1 special symbol (!...$)</li>
                        <li><i class="fas fa-check-circle"></i> At least 1 uppercase letter (A...Z)</li>
                    </ul>
                </div>
            </div>
            <input type="submit" id="submit" name="submit" value="Continue"><br>
        </form>
    </div>
</div>
<script>
    const requirementItems = document.querySelectorAll(".requirement-list li");
    const passwordInput = document.querySelector("#password");

    const requirements = [
        { regex: /.{8,}/, message: "At least 8 characters length" },
        { regex: /\d/, message: "At least 1 number (0...9)" },
        { regex: /[a-z]/, message: "At least 1 lowercase letter (a...z)" },
        { regex: /[!@#$%^&*(),.?":{}|<>]/, message: "At least 1 special symbol (!...$)" },
        { regex: /[A-Z]/, message: "At least 1 uppercase letter (A...Z)" }
    ];

    passwordInput.addEventListener("input", () => {
        requirements.forEach((item, index) => {
            const isValid = item.regex.test(passwordInput.value);
            const requirementItem = requirementItems[index];
            const icon = requirementItem.querySelector("i");
            if (isValid) {
                icon.classList.remove("fa-square-xmark");
                icon.classList.add("fa-check-square");
                requirementItem.classList.add("valid");
                requirementItem.classList.remove("invalid");
            } else {
                icon.classList.remove("fa-check-square");
                icon.classList.add("fa-square-xmark");
                requirementItem.classList.remove("valid");
                requirementItem.classList.add("invalid");
            }
        });
    });

    function togglePasswordVisibility(passwordId, toggleId) {
        const passwordInput = document.getElementById(passwordId);
        const toggleIcon = document.getElementById(toggleId);

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.classList.remove("fa-eye-slash");
            toggleIcon.classList.add("fa-eye");
        } else {
            passwordInput.type = "password";
            toggleIcon.classList.remove("fa-eye");
            toggleIcon.classList.add("fa-eye-slash");
        }
    }

    function updateFileName(input) {
        var fileName = input.files[0].name;
        document.querySelector('.pic').innerHTML = fileName;
    }

    const imageInput = document.getElementById('upload');
    const imagePreview = document.querySelector('.pic');

    imageInput.addEventListener('change', function() { 
        const file = this.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.classList.add('preview-image'); // Add a class for styling if needed
            imagePreview.innerHTML = ''; // Clear previous content
            imagePreview.appendChild(img);
        };

        reader.readAsDataURL(file);
    });

</script>
</body>
</html>
