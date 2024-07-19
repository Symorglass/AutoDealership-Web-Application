<?php

    /* destroy session data */
    session_start();
    session_destroy();
    $_SESSION = array();

    /* redirect to search_vehicles public page */
    header('Location: search_vehicles.php');

?>