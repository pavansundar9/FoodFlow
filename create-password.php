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
    $receiver_password = $_POST["password"] ?? "";
    $conform = $_POST["confirm-password"] ?? "";

    // Handle image upload
    $image_name = $_FILES["image"]["name"] ?? "";
    $image_tmp = $_FILES["image"]["tmp_name"] ?? "";
    $image_type = $_FILES["image"]["type"] ?? "";

    if (!empty($image_name) && !empty($image_tmp)) {
        $upload_directory = "uploads/";
        $target_file = $upload_directory . basename($image_name);
        move_uploaded_file($image_tmp, $target_file);
    } else {
        $target_file = ""; // fallback if image not uploaded
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

        $sql = "SELECT * FROM $table WHERE email=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $row = mysqli_fetch_array($result)) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create a Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="signup.css">
    <link rel="icon" type="image/png" href="images/food-flow-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="password-creation">
    <div class="form">
        <center><h2>Create Password</h2></center>
        <form action="" method="POST" enctype="multipart/form-data">
            <?php if (!empty($error)) echo '<p style="color: red;">' . htmlspecialchars($error) . '</p>'; ?>
            <br>
            <div class="user-pe">
                <div class="profile-pic">
                    <div class="pic empty" id="image-preview">
                        <!-- Preview will be shown here -->
                    </div>
                    <div class="file-input">
                        <input type="file" id="upload" name="image" class="inputfile" accept="image/*" />
                        <label for="upload"><i class="fas fa-camera"></i> Upload Image</label>
                    </div>
                </div>
                <div class="user-details">
                    <?php echo '<h3>' . htmlspecialchars($email) . '</h3>'; ?>
                </div>
            </div>
            <div class="input-wrapper">
                <input type="password" class="input" id="password" name="password" placeholder=" " required>
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

<!-- SCRIPT -->
<script>
    const passwordInput = document.querySelector("#password");
    const requirementItems = document.querySelectorAll(".requirement-list li");
    const requirements = [
        { regex: /.{8,}/ },
        { regex: /\d/ },
        { regex: /[a-z]/ },
        { regex: /[!@#$%^&*(),.?":{}|<>]/ },
        { regex: /[A-Z]/ }
    ];

    passwordInput.addEventListener("input", () => {
        requirements.forEach((item, index) => {
            const isValid = item.regex.test(passwordInput.value);
            const li = requirementItems[index];
            const icon = li.querySelector("i");
            if (isValid) {
                icon.classList.remove("fa-square-xmark");
                icon.classList.add("fa-check-square");
                li.classList.add("valid");
                li.classList.remove("invalid");
            } else {
                icon.classList.remove("fa-check-square");
                icon.classList.add("fa-square-xmark");
                li.classList.remove("valid");
                li.classList.add("invalid");
            }
        });
    });

    function togglePasswordVisibility(passwordId, toggleId) {
        const input = document.getElementById(passwordId);
        const icon = document.getElementById(toggleId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
    }

    const imageInput = document.getElementById('upload');
    const imagePreview = document.getElementById('image-preview');

    imageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.classList.remove('empty');
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview">`;
            };
            reader.onerror = function () {
                alert('Error reading file.');
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.classList.add('empty');
            imagePreview.innerHTML = '';
        }
    });
</script>

<!-- INLINE CSS for preview styling (or move to signup.css) -->
<style>
.pic {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 2px solid #ccc;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.pic img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

</body>
</html>
