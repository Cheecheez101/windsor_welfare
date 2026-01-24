<?php
// includes/role_auth.php

function require_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../member/dashboard.php");
        exit();
    }
}

function require_member() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}
?>