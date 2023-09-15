<?php


/**
 * 
 * How to store non-config variables that need to persist with the data.
 * 
 */
class variables{

    public static function get($key, $default = null){

        global $mysqli;

        $key_safe = $mysqli->real_escape_string($key);
        $response = $mysqli->query("SELECT `value` FROM `variables` WHERE `key` = '$key_safe'");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();

        if($rows){
            // we have a value for the key so return it
            return $rows[0]['value'];
        }else{
            // no value for the ke
            if($default !== null){
                // but there is a default so set that
                Variables::set($key, $default);
                // and return it
                return Variables::get($key);
            }else{
                // no value and no default so return nothing
                return null;
            }
        }
        

    }

    public static function set($key, $value){
        
        global $mysqli;

        $key_safe = $mysqli->real_escape_string($key);
        $value_safe = $mysqli->real_escape_string($value);
        $response = $mysqli->query("SELECT `value` FROM `variables` WHERE `key` = '$key_safe';");
        $rows = $response->fetch_all(MYSQLI_ASSOC);
        $response->close();
        if($rows){
            $mysqli->query("UPDATE `variables` SET `value` = '$value_safe' WHERE `key` = '$key_safe';");
        }else{
            $mysqli->query("INSERT INTO `variables`  (`key`, `value`) VALUES ('$key_safe','$value_safe');");
        }
    }

    public static function unset($key){
        global $mysqli;
        $key_safe = $mysqli->real_escape_string($key);
        $response = $mysqli->query("DELETE FROM `variables` WHERE `key` = '$key_safe'");
    }

}