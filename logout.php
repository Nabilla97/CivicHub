<?php
require __DIR__ . '/includes/bootstrap.php';
unset($_SESSION['uid']);
session_regenerate_id(true);
flash('Anda sudah keluar.');
redirect('login.php');
