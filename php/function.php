<?php
session_start();

// REDIS CONFIGURATION
// ini_set("session.save_handler", "redis");
// ini_set("session.save_path", "tcp://localhost:6379");

require '../vendor/autoload.php';
$conn = mysqli_connect("localhost", "root", "", "mydb");

// IF
if(isset($_POST["action"])){
  if($_POST["action"] == "register"){
    register();
  }
  else if($_POST["action"] == "login"){
    login();
  }
  else if($_POST["action"] == "update"){
    update();
  }
}

// UPDATE
function update(){

    global $conn;

    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $usersCollection = $mongoClient->mydb->users;

    $name = $_POST["name"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $dob = $_POST["dob"];
    $contact = $_POST["contact"];

    $filter = [ "username" => $username ];

    $update = [
        '$set' => [
            "name" => $name,
            "password" => $password,
            "email" => $email,
            "dob" => $dob,
            "contact" => $contact
        ]
    ];

    $result = $usersCollection->updateOne($filter, $update);

    $stmt = $conn->prepare('UPDATE users SET name=?, password=?, email=?, dob=?, contact=? WHERE username=?');

    // Bind the parameters
    $stmt->bind_param('ssssss', $name, $password, $email, $dob, $contact, $username);

    // Execute the SQL statement
    if ($stmt->execute()) {
        echo 'User details updated successfully';
    } else {
        echo 'Error updating user details: ' . $stmt->error;
    }

    // Close the prepared statement and database connection
    $stmt->close();
    $conn->close();
    exit;

}

// REGISTER
function register(){

  global $conn;

  $client = new MongoDB\Client("mongodb://localhost:27017");
  $collection = $client->mydb->users;

  $name = $_POST["name"];
  $username = $_POST["username"];
  $password = $_POST["password"];
  $email = $_POST["email"];
  $dob = $_POST["dob"];
  $contact = $_POST["contact"];

  if(empty($name) || empty($username) || empty($password)){
    echo "Please Fill Out The Form!";
    exit;
  }

  $user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
  if(mysqli_num_rows($user) > 0){
    echo "Username Has Already Taken";
    exit;
  }
  else{
    $stmt = $conn->prepare('INSERT INTO users (name, username, email, password, dob, contact) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $name, $username, $email, $password, $dob, $contact);
    $user = [
      'name' => $name,
      'username' => $username,
      'email' => $email,
      'password' => $password,
      'dob' => $dob,
      'contact' => $contact
    ];
    $result = $collection->insertOne($user);
    if($stmt->execute()){
      echo "Registration Successful";
    }
    else{
      echo "Error: Unable to register user";
    }
    
    $stmt->close();
    $conn->close();
  }
}


// LOGIN
function login(){
  global $conn;

  $username = $_POST["username"];
  $password = $_POST["password"];

  $user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

  if(mysqli_num_rows($user) > 0){

    $row = mysqli_fetch_assoc($user);

    if($password == $row['password']){
      echo "Login Successful";
      $_SESSION["login"] = true;
      $_SESSION["id"] = $row["id"];
    }
    else{
      echo "Wrong Password";
      exit;
    }
  }
  else{
    echo "User Not Registered";
    exit;
  }
}
?>
