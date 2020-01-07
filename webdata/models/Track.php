<?php

class TrackRow extends Pix_Table_Row
{
    public function isTrackBy($user)
    {
        return TrackUser::search(array(
            'track_id' => $this->id,
            'user_id' => intval($user->user_id),
        ))->count();
    }

    public function needTrack()
    {
        // 最近修改過的話直接去抓不用管 tracked_at
        if ($this->updated_at > $this->tracked_at) {
            return true;
        }
        if (0 == $this->track_period) { // 每日
            $time = 86400;
        } elseif (1 == $this->track_period) { // 每五分鐘
            $time = 300;
        } elseif (2 == $this->track_period) { // 每六小時
            $time = 6 * 3600;
        } elseif (3 == $this->track_period) { // 每小時
            $time = 3600;
        } elseif (4 == $this->track_period) { // 每分鐘
            $time = 60;
        } elseif (5 == $this->track_period) { // 停止
            return false;
        }

        return time() > $this->tracked_at + $time;
    }

    public function getLatestLog()
    {
        return TrackLog::search(array('track_id' => $this->id))->max('time');
    }

    public function updateLog($content)
    {
        if ($content != $this->getLatestLog()->content) {
            $log = TrackLog::search(array('track_id' => $this->id, 'content' => $content))->order('time DESC')->first();

            TrackLog::insert(array(
                'track_id' => $this->id,
                'time' => time(),
                'content' => $content,
            ));

            return array($content, $log->time);
        }
        return false;
    }

    public function getTrackContent()
    {
        $options = json_decode($this->options);
        return $options->track_content;
    }

    public function getWay()
    {
        $options = json_decode($this->options);
        return $options->track_way;
    }

    public function getFollow301()
    {
        $options = json_decode($this->options);
        return property_exists($options, 'follow_301') ? $options->follow_301 : false;
    }

    public function getHTML()
    {
        return Track::getHTML($this->url);
    }

    public function trackContent()
    {
        switch ($this->getWay()) {
        case 2: // 追蹤 HTML + regex
            $obj = $this->getHTML();
            if (!preg_match_all($this->getTrackContent(), $obj['content'], $matches)) {
                return array(
                    'http_code' => $obj['http_code'],
                    'status' => 'notfound',
                    'content' => in_array($obj['http_code'], array(301, 302)) ? $obj['content'] : '',
                );
            }

            return array(
                'http_code' => $obj['http_code'],
                'status' => 'found',
                'content' => in_array($obj['http_code'], array(301, 302)) ? $obj['content'] : implode('', $matches[1]),
            );
        case 3: // 檔案 MD5
            $curl = curl_init();
            $download_fp = tmpfile();
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            if ($this->getFollow301()) {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
            }
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_FILE, $download_fp);
            curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            fflush($download_fp);

            $filepath = stream_get_meta_data($download_fp)['uri'];
            $ret = array(
                'http_code' => $info['http_code'],
                'status' => filesize($filepath) ? 'success' : 'failed',
                'md5' => md5_file($filepath), 
                'size' => filesize($filepath),
                'redirect_url' => in_array($info['http_code'], array(301, 302)) ? $info['redirect_url'] : '',
            );
            if ($this->getTrackContent()) {
                $ret['content'] = file_get_contents($filepath);
            }
            return $ret;
        }
    }
}

class Track extends Pix_Table
{
    public function init()
    {
        $this->_name = 'track';
        $this->_primary = 'id';
        $this->_rowClass = 'TrackRow';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['title'] = array('type' => 'text');
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['updated_at'] = array('type' => 'int');
        $this->_columns['tracked_at'] = array('type' => 'int');
        $this->_columns['url'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['options'] = array('type' => 'text');
        // 0-每日, 1-每五分鐘
        $this->_columns['track_period'] = array('type' => 'tinyint');

        $this->_relations['users'] = array('rel' => 'has_many', 'type' => 'TrackUser', 'foreign_key' => 'track_id', 'delete' => true);
        $this->_relations['logs'] = array('rel' => 'has_many', 'type' => 'TrackLog', 'foreign_key' => 'track_id', 'delete' => true);
    }

    public static function getTrackPeriods()
    {
        return array(
            0 => '每日',
            1 => '每五分鐘',
            2 => '每六小時',
            3 => '每小時',
            4 => '每分鐘',
            5 => '停用',
        );
    }

    public static function getTrackWays()
    {
        return array(
            1 => '純文字 regex 判斷',
            2 => 'HTML regex 判斷',
            3 => '檔案內容',
        );
    }

    public static function updateTrack()
    {
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
                    'content' => $log[0],
                    'last_hit' => $log[1],
                );
            }
        }

        foreach ($user_logs as $user_id => $logs) {
            if (!$user = User::find(intval($user_id))) {
                continue;
            }
            $mail = substr($user->user_name, 9);
            foreach ($logs as $log) {
                $title = 'ContentTrack 發現網頁變動 - ' . $log['track']->title;
                $content = '';
                $content .= "標題: {$log['track']->title}\n";
                $content .= "原始網址: {$log['track']->url}\n";
                $content .= "紀錄網址: https://contenttrack.ronny.tw/?id={$log['track']->id}#track-logs\n";
                if ($log['last_hit'] and $log['last_hit'] > time() - 180 * 86400) {
                    $content .= "與 " . date('Y/m/d H:i:s', $log['last_hit']) . " 內容相同\n";
                }
                $log['content'] = json_encode(json_decode($log['content']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $content .= "內容: {$log['content']}\n";
                NotifyLib::alert(
                    $title,
                    $content,
                    $mail
                );
            }
        }
    }

    public function getHTML($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($this->getFollow301()) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Mobile Safari/537.36',
        ));
        curl_setopt($curl, CURLOPT_URL, $url);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (in_array($info['http_code'], array(301, 302))) {
            $content = $info['redirect_url'];
        }
        if (preg_match('#CONTENT=["\']text/html;\s*charset=big5#i', $content)) {
            $content = iconv('big5', 'utf-8//ignore', $content);
        }
        return array(
            'http_code' => $info['http_code'],
            'content' => $content,
        );
    }
}
