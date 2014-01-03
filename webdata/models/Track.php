<?php

class TrackRow extends Pix_Table_Row
{
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
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['tracked_at'] = array('type' => 'int');
        $this->_columns['url'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['options'] = array('type' => 'text');
    }

    public static function getTrackWays()
    {
        return array(
            1 => '純文字 regex 判斷',
            2 => 'HTML regex 判斷',
        );
    }
}
