<?php
  session_start();

  //-------- Connect to mysql request database ---------
  require '../../secure/mysql_pass.php';
  $servername = $mySQLservername;
  $username = $mySQLusername;
  $password = $mySQLpassword;

  $conn = new mysqli($servername, $username, $password, $mySQLdatabase);
  // Check connection
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  	header("Location:index.php?error=3");
  	exit;
  }

  function closeConn(){
    $conn->close();
    exit;
  }
  //-------- core ---------
  //Check if the new password match
  if(strcmp($_POST['newPassword'], $_POST['newPasswordVerf']) !== 0){
    header("Location:../../editProfile.php?error=5");
    closeConn();
  }

  //Check if the actual password match
  $query = $conn->prepare("SELECT password FROM accounts WHERE userId=?");
  $query->bind_param("i",$_SESSION["userId"]);
  $query->execute();
  $result = $query->get_result();

  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $passwordHash = $row['password'];
    }
  }
  $query->close();

  if(password_verify($_POST['oldPassword'],$passwordHash)){
    //Change password
    $newPasswordHash = password_hash($_POST['newPassword'],PASSWORD_BCRYPT);

    $query = $conn->prepare("UPDATE accounts SET password=? WHERE userId=?");
    $query->bind_param("si",$newPasswordHash,$_SESSION["userId"]);
    if($query->execute()){
      $query->close();
      header("Location:../../editProfile.php?block=security&success=4");
      closeConn();
    }else{
      header("Location:../../editProfile.php?block=security&error=4");
      closeConn();
    }

  }else{
    header("Location:../../editProfile.php?block=security&error=3");
    closeConn();
  }
 ?>
