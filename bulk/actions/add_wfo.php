<?php

if(isset($_GET['create'])){

    // double check it doesn't exist
    $name = Name::getName($_GET['wfo']);
    if($name->getId()){
        $params = $_GET;
        $params['action'] = 'view';
        $redirect_url = 'index.php?' . http_build_query($params);
        header("Location: $redirect_url");
        exit;
    }

    // remove the rank from the name
    $proposed = Name::sanitizeNameString($_GET['proposed_name']);
    $parts = explode(' ', $proposed);
    $good_parts = array();
    $rank = false;
    foreach($parts as $p){
        if(Name::isRankWord($p)) $rank = Name::isRankWord($p);
        else $good_parts[] = $p;
    }
    $proposed = implode(' ', $good_parts);

    // create the name
    $response = Name::createName($proposed, true, false);

    $new_name = false;
    if($response->success){
        $new_name = $response->names[0];
        $new_name->setPrescribedWfoId($_GET['wfo']);
        $new_name->save();
    }else{

       

        // we have failed - probably because of homonyms
        // which we will just override
        // any other failures fall through
        foreach($response->children as $resp){
            if($resp->name == 'HomonymsFound'){

                $homonym_wfos = array();
                foreach($resp->names as $homo){
                    $homonym_wfos[] = $homo->getPrescribedWfoId();
                }
                $response = Name::createName($proposed, true, true, $homonym_wfos);
                if($response->success){
                    $new_name = $response->names[0];
                    $new_name->setPrescribedWfoId($_GET['wfo']);
                    $new_name->save();
                }
            }
        }

    }

    if(!$new_name){
        // things suck just dump the response
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        exit;
    }


}else{
    // not creating to skip it
    $table = $_GET['table'];
    $rhakhis_pk = $_GET['rhakhis_pk'];
    $mysqli->query("UPDATE `rhakhis_bulk`.`$table` SET `rhakhis_skip` = 1 WHERE `rhakhis_pk` = $rhakhis_pk;");
}

// things are cool we created them
// redirect to the search page
$params = $_GET;
$params['action'] = 'view';
$redirect_url = 'index.php?' . http_build_query($params);
header("Location: $redirect_url");
