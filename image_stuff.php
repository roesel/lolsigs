<?php

function get_medal_offset($lowercase_league) {
    switch ($lowercase_league) {
        case 'silver':
            return -1;
        case 'platinum':
            return 0;
        case 'challenger':
            return 6;
        case 'master':
            return 5;
    }
    return 0;
}

?>
