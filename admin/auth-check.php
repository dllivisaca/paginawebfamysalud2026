<?php
require_once "session-bootstrap.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
