<?php
    session_start();
    if (empty($_SESSION['username']) ){
        header("Location: search_vehicles.php");
        die();
    }else{
        header("Location: search_vehicles_privileged.php");
        die();
    }
?>