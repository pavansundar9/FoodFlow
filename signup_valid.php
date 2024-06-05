<?php
    session_start();
    $name=$email=$type=$address=$number='';

    //Connecting to the Database
    $servername ="localhost";
    $username ="root";
    $password ="";
    $dbname = "sdp";
    $conn =mysqli_connect($servername, $username, $password,$dbname);
    if ($conn){
        // echo "<script>consol.log('Connection ok');</scripy>";

        $name =$_POST["name"];
        $email = $_POST["email"];
        $type = $_POST["type"];
        $address = $_POST['address'];
        $county =$_POST["country-code"]; 
        $number = $_POST["phone-number"];
        
        if(empty($name)||empty($email)||empty($type)||empty($address)||empty($number)){
            $_SESSION['error_message']="Please fill all the fields";
            header("Location: signup.php");
        }
        elseif (!preg_match("/^[a-zA-Z-' ]*$/",$name)) {
            $_SESSION['error_message']="Name: Please use only letters, numbers and spaces";
            header("Location: signup.php");
        }
        elseif (!preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/",$email)) {
            $_SESSION['error_message']="Email: Please use only letters, numbers, dots and dashes";
            header("Location: signup.php");
        }
        elseif (!preg_match("/^[0-9]+$/",$number)) {
            $_SESSION['error_message']="Number: Please use only numbers";
            header("Location: signup.php");
        }else{
            $_SESSION['error_message']="";
            $_SESSION['name']=$name;
            $_SESSION['email']=$email;
            $_SESSION['type']=$type;
            $_SESSION['address']=$address;
            $_SESSION['county']=$county;
            $_SESSION['number']=$number;

            if($type=='donor'){
                //insert into donors table
                $sql="SELECT * FROM fooddoners WHERE email='$email'";
                $donerTable=mysqli_query($conn,$sql);
                if (!$donerTable) {
                    // Handle query error
                    echo "Query failed: " . mysqli_error($conn);
                    exit; // Stop further execution
                }
            }else{
                $sql="SELECT * FROM foodreceivers WHERE email='$email'";
                $receiverTable=mysqli_query($conn,$sql);
                if (!$receiverTable) {
                    // Handle query error
                    echo "Query failed: " . mysqli_error($conn);
                    exit; // Stop further execution
                }
            }

            if ($donerTable !== null && mysqli_num_rows($donerTable) > 0) {
                $_SESSION['error_message'] = "User already exists. Please login instead.";
                header("Location: signup.php");
            } elseif ($receiverTable !== null && mysqli_num_rows($receiverTable) > 0) {
                $_SESSION['error_message'] = "User already exists. Please login instead.";
                header("Location: signup.php");
            } 
            else{
                if($type=="donor"){
                    
                    $query = "INSERT INTO fooddoners (name, email, user_type, phone,address) VALUES ('$name', '$email', '$type', '$number','$address)";

                    $donerTable = mysqli_query($conn, $query);

                    if ($donerTable) {
                        echo "<script>alert('Data Inserted');</script>";
                        
                        $sql = "SELECT * FROM fooddoners";
                        $donerTable = mysqli_query($conn, $sql);
                    } else {
                        echo "<script>alert('failed');</script>";
                    }

                    header("Location: create-password.php");
                }
                else{
                    $query = "INSERT INTO foodreceivers (name, email, user_type, c_number,address) VALUES ('$name', '$email', '$type', '$number','$address')";

                    $receiverTable = mysqli_query($conn, $query);

                    if ($receiverTable) {
                        echo "<script>alert('Data Inserted');</script>";
                        
                        $sql = "SELECT * FROM foodreceivers";
                        $receiverTable = mysqli_query($conn, $sql);
                    } else {
                        echo "<script>alert('failed');</script>";
                    }

                    header("Location: create-password.php");
                }
            }


        }
    }
    else{
        echo "Connection failed ".mysqli_connect_error();
    }

    
?>

