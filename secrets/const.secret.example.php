<?php
/* -- Defining server specific constants ------------------------------------------------------ */
// Fill the following, then rename the file to "const.secret.php"
const WEB = "https://lolsigs.com/"; // From which address is the site served?
const BACKEND = "http://localhost:9090/"; // Address of the golang backend.
const CACHE = True; // Should we or should we not cache?
const SERVER_CACHE = 43200; // How long should we cache images on the server? (12 hours)
const BROWSER_CACHE = 10800; // How long should we ask the browser to cache our images? (3 hours)
?>