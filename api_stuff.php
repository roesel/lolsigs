<?php

function get_regions() {
    // TODO convert to const
    $regions = [
        "br" => "br1",
        "eune" => "eun1",
        "euw" => "euw1",
        "jp" => "jp1",
        "kr" => "kr",
        "lan" => "la1",
        "las" => "la2",
        "na" => "na1",
        "oce" => "oc1",
        "tr" => "tr1",
        "ru" => "ru",
    ];

    return $regions;
}

function flip_region($region) {
    $flipped_regions = array_flip(get_regions());
    return $flipped_regions[$region];
}

function translate_region($region) {
    $regions = get_regions();

    if (in_array($region, $regions, true)) {
        return $region;
    }
    elseif (array_key_exists($region, $regions)) {
        return $regions[$region];
    } else {
        return False;
    }
}

function extract_simple_stats($j) {
    $stats = array(
        array("Pentas", $j->Stats->PentaKills),
        array("Winrate", round((float)$j->Stats->Winrate * 100, 1 ) . ' %'),
        array("Kills", $j->Stats->Kills),
        array("KDA", round($j->Stats->KDA, 2)),
        array("Max Spree", $j->Stats->LargestKillingSpree),
    );

    return $stats;
}

function extract_simple_ranked($j) {
    $ranked = array(
        "league" => $j->Ranked->Division,
        "tier" => $j->Ranked->Tier,
        "rank"=> $j->Ranked->LeagueNum,
        "rank_roman" => $j->Ranked->LeagueNumRoman,
        "lp" => $j->Ranked->LP,
    );

    return $ranked;
}

?>
