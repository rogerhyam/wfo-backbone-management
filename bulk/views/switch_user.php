<h2>Switch User</h2>
<p><strong style="color: red;">Use with caution! Only for debugging access issues.</strong></p>
<p>This is where you can switch to another user. It is like the Linux command 'su' but a one way trip.</p>
<p>
    If you change to being a user who doesn't have role 'god' then you will lose access to this page!
    It is recommended you do any su actions in an incognito browser window so that you can 
    maintain admin access in your normal browser window.
</p>
<p>You are currently:
<strong>    
<?php
 $current_user = unserialize($_SESSION['user']); 
 echo $current_user->getName();
?>
</strong>
</p>

<form method="GET" action="index.php">
    <input type="hidden" name="action" value="switch_user" />
    <select name="new_user_id">
<?php
   
    $users = User::getAllUsers();
    foreach($users as $u){
        $disabled = "";
        if($u->getId() == $current_user->getId()) $disabled = 'disabled';
        echo "<option value=\"{$u->getId()}\" $disabled >{$u->getName()} [{$u->getRole()}]</option>";
    }
?>
    &nbsp;
    <input type="submit" value="Switch User" />
    </select>
</form>