<?php
include_once('secrets/const.secret.php');



// integer starts at 0 before counting
$i = 0;
$dir = 'sigs_cache/';
if ($handle = opendir($dir)) {
    while (($file = readdir($handle)) !== false) {
        if (!in_array($file, array('.', '..')) && !is_dir($dir . $file))
            $i++;
    }
}

$formSubmited = false;

if (isset($_POST['sub']) &&
        isset($_POST['lolname']) &&
        isset($_POST['region']) &&
        isset($_POST['champion']) &&
        (isset($_POST['skin']) || ($_POST['champion'] == 0)) // transparent bg has no skin
) {



    $name = $_POST['lolname'];
    $region = $_POST['region'];
    $champion = $_POST['champion'];
    if (!isset($_POST['skin'])) {
        $skin = 0; // transparent bg has no skin
    } else {
        $skin = $_POST['skin'];
    }

    $address = WEB . "{$name}_{$region}_{$champion}_{$skin}.png";

    $formSubmited = true;
}

$region_array = array(
    'na1' => 'NA',
    'euw1' => 'EUW',
    'eun1' => 'EUNE',
    'oc1' => 'OCE',
    'la2' => 'LAS',
    'la1' => 'LAN',
    'jp1' => 'JP,',
    'br1' => 'BR',
    'tr1' => 'TUR',
    'ru' => 'RU',
    'kr' => 'KR',
);
?>



<!DOCTYPE HTML >
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>League of Legends signature creator</title>
        <!--<meta http-equiv='Content-language' content='en'>-->
        <link href='https://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic&amp;subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link rel='stylesheet' type='text/css' href='style.css'>
        <link rel='stylesheet' type='text/css' href='form.css'>
    <!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>-->
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <script type="text/javascript" src="js/jquery.cookie.js"></script>
        <!--<script type="text/javascript" src="jquery.js"></script>-->

	<!-- Piwik -->
	<script type="text/javascript">
	  var _paq = _paq || [];
	  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
	  _paq.push(['trackPageView']);
	  _paq.push(['enableLinkTracking']);
	  (function() {
		var u="//analytics.broukej.cz/";
		_paq.push(['setTrackerUrl', u+'piwik.php']);
		_paq.push(['setSiteId', '2']);
		var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
		g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
	  })();
	</script>
	<!-- End Piwik Code -->

    </head>
    <body>
        <script type="text/javascript">

            // When document is ready...
            $(document).ready(function () {

                // If cookie is set, scroll to the position saved in the cookie.
                if ($.cookie("scroll") !== null) {
                    $(document).scrollTop($.cookie("scroll"));
                }

                // When a button is clicked...
                $('#form-submit').on("click", function () {

                    // Set a cookie that holds the scroll position.
                    $.cookie("scroll", $(document).scrollTop());

                });

            });


            $(function () {
                $("select#champion").change(function () {
                    if ($("#champion option:selected").text() !== 'Transparent') {
                        $("select#skin").removeAttr("disabled");
                    } else {
                        $("select#skin").attr("disabled", "");
                    }

                    $.getJSON("./get_skins_json.php", {id: $("#champion option:selected").text(), ajax: 'true'}, function (j) {
                        var options = '';
                        for (var i = 0; i < j.length; i++) {
                            options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
                        }
                        $("select#skin").html(options);
                    })
                })
            });


            $(document).on('click', 'pre', function () {

                if (this.select) {
                    this.select();
                }
                else if (document.selection) {
                    var range = document.body.createTextRange();
                    range.moveToElementText(this);
                    range.select();
                } else if (window.getSelection) {
                    var range = document.createRange();
                    range.selectNode(this);
                    window.getSelection().addRange(range);
                }
            });
        </script>

        <script type="text/javascript">
            function imgLoaded(img) {
                var $img = $(img);

                $img.parent().addClass('loaded');
            }
            ;
        </script>

        <div class="warning top positive">
            Whoa! No ads! Consider <strong>pledging on <a href="https://www.patreon.com/lolsigs">Patreon</a></strong> or <strong>donating via PayPal <small>(roesel@gmail.com)</small></strong>.  Thanks.
        </div>
        <div id="background-image-top">
            <div id="background-top">
                <div id="wrapper-top">
                    <div id="content-top">
                        <div id='form'>
                            <h1 id="page-title"><a href="<?php echo WEB ?>">League of Legends signature creator</a></h1>
                            <form id="data" method="POST">
                                <?php
                                if (isset($name)) {
                                    $autofocus = '';
                                } else {
                                    $autofocus = 'autofocus';
                                    $name = '';
                                }
                                print '<input class="input-xlarge" type="text" placeholder="Summoner Name" name="lolname" value="' . $name . '" ' . $autofocus . ' required="required">';
                                ?>
                                <!-- Not too sure of the other region codes -->
                                <select name="region" required="required">
                                    <option value="" disabled selected class="select-placeholder">Region</option>
                                    <?php

									if (!isset($region)) {
										$region = "";
									}

									foreach ($region_array as $reg_key => $reg_name) {
                                        $reg_selected = '';
                                        if ($reg_key == $region) {
                                            $reg_selected = 'selected';
                                        }
                                        print '<option value="' . $reg_key . '" ' . $reg_selected . '>' . $reg_name . '</option>';
                                    }
                                    ?>
                                </select>
                                <select name="champion" id="champion">
                                    <option value="0">Transparent</option>
                                    <?php
                                    include_once("champion_array.php");

									if (!isset($skin)) {
										$skin = 0;
									}
									if (!isset($champion)) {
										$champion = 0;
									}

									foreach ($champion_array as $champion_key => $id) {
                                        $champ_selected = '';
                                        if ($id[0] == $champion) {
                                            $skin_array = $id[1];
                                            $champ_selected = 'selected';
                                        }
                                        print '<option value="' . $id[0] . '" ' . $champ_selected . '>' . $champion_key . '</option>';
                                    }
                                    ?>
                                </select>
                                <?php
                                $skin_disabled = '';
                                if ($champion == 0) {
                                    $skin_disabled = 'disabled';
                                }

                                print '<select name="skin" id="skin" ' . $skin_disabled . '>';

                                foreach ($skin_array as $id) {
                                    $skin_selected = '';
                                    if ($id[1] == $skin) {
                                        $skin_selected = 'selected';
                                    }
                                    print '<option value="' . $id[1] . '" ' . $skin_selected . '>' . $id[0] . '</option>';
                                }

                                print '</select>';
                                ?>

<!--<select name = "logo">
<option value = "logo">zG Logo</option>
<option value = "text">zG Text</option>
<option value = "nozg">No zG Watermark</option>
</select> -->
                                <input class="blue" type="submit" name="sub" id="form-submit" value="Generate Signature">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id = "background-center">
            <div id = "wrapper-center">
                <div id = "content-center">

                    <?php
                    if ($formSubmited) {
                        ?>
                        <div id="result">
                            <?php // image loading http://www.barrelny.com/blog/taking-control-of-imageloading/     ?>
                            <div id="signature">
                                <div class="img_wrapper">
                                    <div class="css_spinner">
                                        <div class="half left">
                                            <div class="band"></div>
                                        </div>
                                        <div class="half right">
                                            <div class="band"></div>
                                        </div>
                                    </div>
                                    <img src="<?php echo $address ?>" alt="signature" onload="imgLoaded(this)" title="<?php print $name . '@'. $region; ?>">
                                </div>
                            </div>
                            <p>Your image will be displayed above in a few seconds.</p>
                            <p><strong>URL: </strong>
                            <pre id="url" ><?php echo $address ?></pre></p>
                            <p><strong>BBCode: </strong>
                            <pre>[CENTER][URL='<?php echo WEB ?>'][IMG]<?php echo $address ?>[/IMG][/URL][/CENTER]</pre>
                            </p>
                            <div>
                                <div style="display:inline-block; margin: 0 64px;">
                                    <h3>Need more info?</h3>
                                    <p>Have a look at the post <a href='https://redd.it/4zsv1y'>on reddit</a>!</p>
                                </div>
                                <div style="display:inline-block; margin: 0 64px;">
                                    <h3>You might also like:</h3>
                                    <p>See how you beat the #1 player in your region: <a href='<?php print 'http://lol-beat-best.com/#!'.$region.'/'.$name; ?>'>www.lol-beat-best.com</a></p>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <p id="introduction"><strong>Welcome summoner</strong>, you have found yourself on the LoL signature maker, where you can make your own signature
                            featuring the stats you have managed to achieve in the ranked games. It will look just like this:</p>
                        <br>
                        <div id="signature"><img src="<?php print(WEB); ?>Ruzgud_eune_238_2.png" title="Ruzgud@EUNE" alt="Example signature"/></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div id="background-image-bottom">
            <div id="background-bottom">
                <div id="wrapper-bottom">

                    <div id="content-bottom">
                        <div class="flexcontainer-center">
                            <div style="width:800px;">
                                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                                <!-- Lolsigs -->
                                <ins class="adsbygoogle"
                                     style="display:block"
                                     data-ad-client="ca-pub-7860058313368973"
                                     data-ad-slot="6054124582"
                                     data-ad-format="auto"></ins>
                                <script>
                                (adsbygoogle = window.adsbygoogle || []).push({});
                                </script>
                            </div>
                        </div>
                        <div class="flexcontainer-center">
                            <div>
                                <h4>TIP:</h4><p> To find a champion quicker, open the selectbox and press the first few letters.</p>
                            </div>
                            <div>
                                <h4>INFO:</h4><p> It will only work if you are placed in Season 7 ranked!
                            </div>
                            <div>
                                <h4>STATS:</h4><p> There have been <?php print($i) ?> signatures generated in the last 24 hours!</p>
                            </div>
                            <div>
                                <h4>REDDIT:</h4><p> If you have doubts, questions, ideas or bugs to report, do so here: <br/><a href="https://redd.it/4zsv1y">on reddit</a>!</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div id="background-footer">
                <div id="wrapper-footer">
                    <div id="content-footer">
                        <div id="footer">
                            Signature creator itself was written by <strong><a href="https://twitter.com/erthylol">Erthy</a></strong>
                            (<a href="http://www.lolking.net/summoner/eune/26174422">Erthainel</a>@EUNE). Web interface design by <strong><a href="<?php print htmlspecialchars('mailto:ondrian.t[at]gmail[dot]com');?>">Ondrian</a></strong>.<br>
                            Huge thanks to <strong>1lann</strong> for insane amounts of help with the Go backend.<br>
                            Others: interface by <strong>Sun</strong>, props to <strong>[zG]Woods</strong>, champion and skin numbers by <strong>Hobbesclone</strong>, <strong>Viitrexx</strong> and <strong>Carlxz</strong>.<br>
                            <br>The LoL Signature Generator isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends © Riot Games, Inc.
                            <br>
                            <p style="color:gray;">DJ4pw1ue4qD84QN5ZeG7hvL8YZEqHHynNW<br/>give doge? many thanks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
