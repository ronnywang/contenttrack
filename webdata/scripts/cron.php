<?php

include(__DIR__ . '/../init.inc.php');

foreach (Track::search(1) as $track) {
    if (!$track->needTrack()) {
        continue;
    }
    $track->update(array(
        'tracked_at' => time(),
    ));
    $track->updateLog(json_encode($track->trackContent()));
}
