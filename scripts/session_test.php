<?php

//fixed session:
session_id("fixed");
session_start();

$_SESSION['banana'] = 'cake';

echo "\nBanana: " . $_SESSION['banana'];

echo_fruit();

function echo_fruit(){
    echo "\nFruit: " . $_SESSION['banana'];
}