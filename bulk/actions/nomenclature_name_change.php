<?php

/*
    This is a simple script that changes the name string for 
    all the wfo id supplied then redirects back to the 
    scan form.
*/

foreach($_POST as $wfo_id => $name_string){

    if(!preg_match('/^wfo-[0-9]{10}$/', $wfo_id)) continue;


    
    $name = Name::getName($wfo_id);
    if(!$name || !$name->getId()) continue;

    $name->setNameString(trim($name_string));
    $name->save();

}
header('Location: index.php?action=view&phase=nomenclature&task=nomenclature_name_check');
exit;