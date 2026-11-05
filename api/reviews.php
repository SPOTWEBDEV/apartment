<?php
require __DIR__ . '/_bootstrap.php';
$data = require __DIR__ . '/../includes/data.php';
respond(['ok'=>true,'reviews'=>$data['reviews']]); // TODO: load from DB
