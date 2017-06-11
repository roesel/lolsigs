<?php
include_once("champion_array.php");
 
$name = $_GET['id'];
$output = "[";     
foreach ($champion_array[$name][1] as $skin) {
    $output .= '{"optionValue": '.$skin[1].', "optionDisplay": "'.$skin[0].'"},';
}     
$output = rtrim($output, ',');
$output .= "]";
echo $output;    
?>