<?php
header('Content-type: text/html; charset=utf-8');
mb_internal_encoding("UTF-8");
include_once("curl_stuff.php");
include_once("api_stuff.php");
include_once("image_stuff.php");
include_once("secrets/const.secret.php");
$debug = False;
$sigs_cache_location = "sigs_cache/";  // Move to const

// Importing fonts
$font = 'fonts/latin_becker_compress.ttf';
$font_big = 'fonts/GFSNeohellenic.ttf';
$font_tahoma = 'fonts/tahoma.ttf';

/* Error handling ------------------------------------------------------------*/
// TODO Errors into separate file

function get_error_background() {
    /* setting image size */
    $width = 450;
    $height = 80;
    $ratio = $width/$height;
    $error_background = 'img/error_background.png';
    /* creating background and filling it with transparent color */
    $mask = imagecreatetruecolor( $width, $height );
    $transparent = imagecolorallocatealpha( $mask, 0, 0, 0, 127 );
    imagefill( $mask, 0, 0, $transparent );
    $back = imagecreatefrompng($error_background);
    imagealphablending($back, 0);
    imagesavealpha($back, 1);
    // Merge mask and background
    imagecopyresampled($mask, $back, 0, 0, 0, 0, $width, $height, $width, $height);
    // Back was merged into mask, we won't need it anymore
    imagedestroy($back);

    return $mask;
}

function add_text_w_shadow($mask, $size, $smth, $x, $y, $font, $text) {
    // Adding text
    $black = imagecolorallocate($mask,0,0,0);
    $white = imagecolorallocate($mask,255,255,255);
    imagettftext($mask, $size, $smth, $x+1, $y+1, $black, $font, $text);
    imagettftext($mask, $size, $smth, $x, $y, $white, $font, $text);
    return $mask;
}

function add_lolsigs_com($mask) {
    // Importing fonts
    $font = 'fonts/latin_becker_compress.ttf';
    $font_big = 'fonts/GFSNeohellenic.ttf';
    $font_tahoma = 'fonts/tahoma.ttf';
    $width = 450;
    $height = 80;
    $black = imagecolorallocate($mask,0,0,0);
    $white = imagecolorallocate($mask,255,255,255);

    // Prepare rectangle for lolsigs box
    $lolsigs_box = imagecreatetruecolor(10, 10);
    $transparent_black = imagecolorallocatealpha( $lolsigs_box, 0, 0, 0, 55);
    imagefill($lolsigs_box, 0, 0, $transparent_black );
    // Set proper opacities
    imagealphablending($lolsigs_box, 0);
    imagesavealpha($lolsigs_box, 1);
    // Copy square with stats onto the image
    imagecopyresampled($mask, $lolsigs_box, $width*0.555, $height*0, 0, 0, $width*0.155,$height*(0.22), 10, 10);
    // Stat_back was merged into mask, we won't need it anymore
    imagedestroy($lolsigs_box);

    // Adding 'lolsigs.com' text
    imagettftext($mask, 8, 0, $width*(0.565)+1, $height*0.148+1, $black, $font_tahoma, "LoLsigs.com");
    imagettftext($mask, 8, 0, $width*(0.565), $height*0.148, $white, $font_tahoma, "LoLsigs.com");

    return $mask;
}

function make_error_image($message, $code) {
    $mask = get_error_background();

    // Importing fonts
    $font = 'fonts/latin_becker_compress.ttf';
    $font_big = 'fonts/GFSNeohellenic.ttf';
    $font_tahoma = 'fonts/tahoma.ttf';
    $width = 450;
    $height = 80;

    add_text_w_shadow($mask, 10, 0, 8, 30, $font_tahoma, $message);
    if ($code !== "") {
        add_text_w_shadow($mask, 32, 0, 355, 55, $font_tahoma, $code);
    }
    $mask = add_lolsigs_com($mask);
    //Serve image
    Header("Content-type: image/png");                      // Server caching
    Header("Cache-Control: max-age=0");        // Browser caching
    imagepng($mask);
    exit();
}

function return_bad_request($message) {
    make_error_image($message, "400");
}

function return_backend_error($message) {
    make_error_image($message, "go?");
}

function return_not_found($message) {
    make_error_image($message, "404");
}

function return_generic($message) {
    if (is_numeric(substr($message,0,3))) {
        $code = substr($message,0,3);
    } else {
        $code = "";
    }
    make_error_image($message, $code);
}

function return_from_cache($file) {
    Header("Content-type: image/png");
    Header("Cache-Control: max-age=".BROWSER_CACHE);       // Browser caching
    readfile($file);
    exit();
}

/* Input handling ------------------------------------------------------------*/

if (isset($_GET["region"]) && isset($_GET["name"])){
    // Get name and region from GET parameters.
    $input_region = strtolower($_GET["region"]);
    $name = str_replace(" ", "", $_GET["name"]);

    $region = translate_region($input_region);
    if ($region !== False) {
        if (isset($_GET["champnum"]) && isset($_GET["skinnum"])) {
            // TODO check if this skin actually exists.
            $champnum = $_GET["champnum"];
            $skinnum = $_GET["skinnum"];
        } else {
            // If champnum or skinnum is missing, revert to transparent background silently.
            $champnum = 0;
            $skinnum = 0;
        }

        // Prepare location for potential cached file.
        $file = $sigs_cache_location.$name."_".$region."_".$champnum."_".$skinnum.".png";

        // If Caching is allowed (and we're not debugging)...
        if (CACHE && $debug == False) {
            // If the file exists and is new enough, serve from cache and exit.
            if (file_exists($file) && (time() - filemtime($file) < SERVER_CACHE)) {
                return_from_cache($file);
            }
        }
    } else {
        return_bad_request("This region does not exist.");
    }
} else {
    return_bad_request("Region or summoner name wasn't set.");
}

/* From this point on, we assume valid input ---------------------------------*/

try {
    $json = get_data(BACKEND.'?summonername='.$name.'&region='.$region);
    if (!empty($json)) {
        $j = json_decode($json);
    } else {
        return_backend_error("Backend not available/crashed. \nConsider conacting lolsigs.com admin.\nTry /r/erthainel or @ErthyLoL.");
    }
} catch (Exception $e) {
    return_backend_error("Backend error:\n".$e->getMessage());
}

if (empty($j->Errors->PlayerToID)) {
    // basic info
    $id = $j->Summoner->id;
    $name = $j->Summoner->name;
} else {
    if ($j->Errors->PlayerToID=="404 Not Found") {
        return_not_found("Player ".$name." \nwas not found in region ".strtoupper(flip_region($region)).".\nTry doublechecking spelling and region.");
    } else {
        return_generic($j->Errors->PlayerToID);
    }
}

if (empty($j->Errors->IDToRanked)) {
    $r = extract_simple_ranked($j);
} else {
    if ($j->Errors->IDToRanked=="Player doesn't seem ranked.") {
        return_not_found("Player ".$name." \nfrom ".strtoupper(flip_region($region))." has no entries in Ranked.\nAre you sure he/she plays SoloQ?");
    } else {
        return_generic($j->Errors->IDToRanked);
    }
}

// stats
$s = extract_simple_stats($j);

/* -------------------------------------------------------------------------- */

/* setting image size */
$width = 450;
$height = 80;
$ratio = $width/$height;

/* creating background and filling it with transparent color */
$mask = imagecreatetruecolor( $width, $height );
$transparent = imagecolorallocatealpha( $mask, 0, 0, 0, 127 );
imagefill( $mask, 0, 0, $transparent );

/* if champion isn't set, leave transparent background */
if ($champnum === 0) {
    imagealphablending($mask, 0);
    imagesavealpha($mask, 1);
} else {
    $local_path = 'back_cache/'.$champnum.'_'.$skinnum.'.png';
    $remote_path = 'http://www.lolking.net/shared/images/champion_headers/'.$champnum.'_'.$skinnum.'.jpg';
    // If background is cached, use the cached version.
    if (file_exists($local_path)) {
        $back = imagecreatefrompng($local_path);
    }
    // Otherwise try to grab it from LoLKing.net
    elseif (remoteFileExists($remote_path)) {
        $back = imagecreatefromstring(get_data($remote_path));
        imagepng($back, $local_path); // Cache it for future use
    } else {
        // TODO This situation should not occur at all, it means LoLKing.net
        // doesn't have the cropped splash -> we should log this and hand-make
        // the splash ourselves.
        imagealphablending($mask, 0);
        imagesavealpha($mask, 1);
    }

    // If a background was set from anywhere
    if (isset($back)) {
        imagealphablending($back, 0);
        imagesavealpha($back, 1);

        // Merge mask and background
        imagecopyresampled($mask, $back, 0, 0, 60, 0, $width, $height, 142*$ratio, 142);
        // Back was merged into mask, we won't need it anymore
        imagedestroy($back);

        // Prepare square for stats
        $stat_back = imagecreatetruecolor(10, 10);
        $transparent_black = imagecolorallocatealpha( $stat_back, 0, 0, 0, 55);
        imagefill($stat_back, 0, 0, $transparent_black );
        // Set proper opacities
        imagealphablending($stat_back, 0);
        imagesavealpha($stat_back, 1);
        // Copy square with stats onto the image
        imagecopyresampled($mask, $stat_back, $width*0.75, $height*0.07, 0, 0, $width*0.24,$height*(1-0.12), 10, 10);
        // Stat_back was merged into mask, we won't need it anymore
        imagedestroy($stat_back);

        // Prepare rectangle for lolsigs box
        $lolsigs_box = imagecreatetruecolor(10, 10);
        $transparent_black = imagecolorallocatealpha( $lolsigs_box, 0, 0, 0, 55);
        imagefill($lolsigs_box, 0, 0, $transparent_black );
        // Set proper opacities
        imagealphablending($lolsigs_box, 0);
        imagesavealpha($lolsigs_box, 1);
        // Copy square with stats onto the image
        imagecopyresampled($mask, $lolsigs_box, $width*0.555, $height*0, 0, 0, $width*0.155,$height*(0.22), 10, 10);
        // Stat_back was merged into mask, we won't need it anymore
        imagedestroy($lolsigs_box);

        // Prepare rectangle for region
        $region_box = imagecreatetruecolor(10, 10);
        $transparent_black = imagecolorallocatealpha($region_box, 0, 0, 0, 55);
        imagefill($region_box, 0, 0, $transparent_black );
        // Set proper opacities
        imagealphablending($region_box, 0);
        imagesavealpha($region_box, 1);
        // Copy square with stats onto the image
        imagecopyresampled($mask, $region_box, $width*0.065, $height*0.03, 0, 0, $width*0.063,$height*(0.15), 10, 10);
        // Stat_back was merged into mask, we won't need it anymore
        imagedestroy($region_box);

    }
}

/* Medal loading and caching ---------------------------------------------- */
$background_filename = strtolower($r['tier']).'_'.$r['rank'].'.png';
$remote_path_meds = 'http://lkimg.zamimg.com/images/medals/'.$background_filename;
$local_path_meds =  'meds_cache/'.$background_filename;

// Check for cached image of the medal, else load it from LoLKing.net
if (file_exists($local_path_meds)) {
    $medal = imagecreatefrompng($local_path_meds);
} elseif (remoteFileExists($remote_path_meds)) {
    $medal = imagecreatefromstring(get_data($remote_path_meds));
    imagealphablending($medal, 0);
    imagesavealpha($medal, 1);
    imagepng($medal, $local_path_meds);
}

imagealphablending($medal, 0);
imagesavealpha($medal, 1);
$medal_offset = get_medal_offset(strtolower($r['tier']));
imagecopyresampled($mask, $medal, 7, 2+$medal_offset, 20, 20,$height,$height, 162, 162);
imagedestroy($medal);

/* Putting letters in image ------------------------------------------------ */
// Definition of colors
$black = imagecolorallocate($mask,0,0,0);
$white = imagecolorallocate($mask,255,255,255);

$name_margin = 0;

// Adding 'lolsigs.com' text
imagettftext($mask, 8, 0, $width*(0.565)+1, $height*0.148+1, $black, $font_tahoma, "LoLsigs.com");
imagettftext($mask, 8, 0, $width*(0.565), $height*0.148, $white, $font_tahoma, "LoLsigs.com");

// Preparing ranked data
$division_rank_lp = ucfirst(strtolower($r['tier']))." ".$r['rank'].", ".$r['lp']." LP";

// Adding ranked data shadows
imagettftext($mask, 17, 0, $height*(0.75)+1, $height*(0.8)+1, $black, $font, $r['rank_roman']); // roman division number
imagettftext($mask, 16, 0, $height*(1.1)+1+$name_margin, 23+1, $black, $font_big, $name); // name of the player
imagettftext($mask, 13, 0, $height*(1.1)+1, 47+1, $black, $font_big, $division_rank_lp); // division tier
imagettftext($mask, 13, 0, $height*(1.1)+1+1, 64+1, $black, $font_big, $r['league']); // league name

// Adding ranked data
imagettftext($mask, 17, 0, $height*(0.75), $height*(0.8), $white, $font, $r['rank_roman']); // roman division number
imagettftext($mask, 16, 0, $height*(1.1)+$name_margin, 23, $white, $font_big, $name); // name of the player
imagettftext($mask, 13, 0, $height*(1.1), 47, $white, $font_big, $division_rank_lp); // division tier
imagettftext($mask, 13, 0, $height*(1.1)+1, 64, $white, $font_big, $r['league']); // league name

// Retarded centering of region above medal
$region_img = flip_region($region);
for ($i=strlen($region_img);$i<4;$i++) {
    if (strlen($region_img)==2) {
        $region_img = $region_img." ";
    }
    $region_img = " ".$region_img;
}
imagettftext($mask, 7, 0, $width*(0.073)+1, 11+1, $black, $font_tahoma, strtoupper($region_img));
imagettftext($mask, 7, 0, $width*(0.073), 11, $white, $font_tahoma, strtoupper($region_img));

if (empty($j->Errors->IDToStats)) {
    $s = extract_simple_stats($j);
} else {
    if ($j->Errors->IDToStats=="rito: wait") {
        $s = array(
            array("Statistics for this", ""),
            array("player are still", ""),
            array("loading. Please", ""),
            array("check back in a", ""),
            array("few minutes.", ""),
        );
    } else {
        $s = array(
            array("", ""),
            array("", ""),
            array($j->Errors->IDToStats, ""),
            array("", ""),
            array("", ""),
        );
    }
}

// Adding stats
for ($i=0;$i<5;$i++) {
    imagettftext($mask, 8, 0, $width*(0.77), 20+$i*12, $white, $font_tahoma, $s[$i][0]);
    imagettftext($mask, 8, 0, $width*(0.90), 20+$i*12, $white, $font_tahoma, $s[$i][1]);
}

if (empty($j->Errors->IDToStats)) {
    // Cache the created image
    imagepng($mask, $file);
}

if (!$debug) {
    Header("Content-type: image/png");                      // Server caching
    Header("Cache-Control: max-age=".BROWSER_CACHE);        // Browser caching
    imagepng($mask);
} else {
    print("<br/>I would dump image...");
}
imagedestroy($mask);

/* Old and iffy cleanup --------------------------------------------------- */
if (filesize($file) <= 30000) {
    if (is_file($file)) {
        unlink($file);
    }
}

$path = 'sigs_cache/';
if ($handle = opendir($path)) {
    while (false !== ($file = readdir($handle))) {
        if (file_exists($path.$file) && (time()-filectime($path.$file)) > 86400) {
            if (preg_match('/\.png$/i', $file)) {
               unlink($path.$file);
           }
        }
        // Probably mopping of failed imgs?
        if (is_file($path.$file)) {
            if (filesize($path.$file) <= 30000) {
                if (preg_match('/\.png$/i', $file)) {
                    unlink($path.$file);
                }
            }
        }
    }
}
?>
