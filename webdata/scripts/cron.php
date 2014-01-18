<?php

include(__DIR__ . '/../init.inc.php');

$user_logs = array();

foreach (Track::search(1) as $track) {
    if (!$track->needTrack()) {
        continue;
    }
    $track->update(array(
        'tracked_at' => time(),
    ));
    $log = $track->updateLog(json_encode($track->trackContent()));
    if (false === $log) {
        continue;
    }
    foreach (TrackUser::search(array('track_id' => $track->id)) as $track_user) {
        if (!array_key_exists($track_user->user_id, $user_logs)) {
            $user_logs[$track_user->user_id] = array();
        }
        $user_logs[$track_user->user_id][] = array(
            'track' => $track,
            'content' => $content,
        );
    }
}

foreach ($user_logs as $user_id => $logs) {
    $title = 'ContentTrack 發現網頁變動 ' . count($logs) . ' 筆';
    $content = '';
    if (!$user = User::find(intval($user_id))) {
        continue;
    }
    $mail = substr($user->user_mail, 9);
    foreach ($logs as $log) {
        $content .= "標題: {$log['track']->title}\n";
        $content .= "原始網址: {$log['track']->url}\n";
        $content .= "紀錄網址: http://contenttrack.ronny.tw/?id={$log['track']->id}#track-logs\n";
        $log = json_encode(json_decode($log), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $content .= "內容: {$log}\n";
        $content .= "==============================================\n";
    }
    NotifyLib::alert(
        $title,
        $content,
        $mail
    );
}
