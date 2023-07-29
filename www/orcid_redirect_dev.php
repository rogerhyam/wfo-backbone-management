<?php

/*

    There is an issue in development in that the ORCID login process requires
    the redirect page to be under HTTPS. This is a complete pain because you can't
    run the Rhakhis API server using the php internal server. You'd need to set up
    certificates on your dev machine to get it working.

    The solution is to use on to the deployed instances to redirect to the local host.
    
    This will do nothing for anyone else!

*/
$redirect_to = "http://localhost:1756/orcid_redirect.html?" . http_build_query($_GET);
header("Location: $redirect_to", true, 302);
exit();


