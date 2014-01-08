<?php

class TrackRow extends Pix_Table_Row
{
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
            TrackLog::insert(array(
                'track_id' => $this->id,
                'time' => time(),
                'content' => $content,
            ));

            NotifyLib::alert(
                'ContentTrack發現網頁變動: ' . $this->title,
                "標題: {$this->title}\n內容：{$content}\n網址：{$this->url}\n：首頁：http://contenttrack.ronny.tw"
            );
        }
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

    public function getHTML()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        return curl_exec($curl);
    }

    public function trackContent()
    {
        switch ($this->getWay()) {
        case 2: // 追蹤 HTML + regex
            $content = $this->getHTML();
            if (!preg_match($this->getTrackContent(), $content, $matches)) {
                return array('notfound');
            }

            return array('found', $matches[1]);
        case 3: // 檔案 MD5
            $curl = curl_init();
            $download_fp = tmpfile();
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_FILE, $download_fp);
            curl_exec($curl);
            curl_close($curl);
            fflush($download_fp);

            $filepath = stream_get_meta_data($download_fp)['uri'];
            $ret = array(
                'md5' => md5_file($filepath), 
                'size' => filesize($filepath),
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
    }

    public static function getTrackPeriods()
    {
        return array(
            0 => '每日',
            1 => '每五分鐘',
            2 => '每六小時',
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
}
