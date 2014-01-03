<?php

class TrackLog extends Pix_Table
{
    public function init()
    {
        $this->_name = 'track_log';
        $this->_primary = array('track_id', 'time');

        $this->_columns['track_id'] = array('type' => 'int');
        $this->_columns['time'] = array('type' => 'int');
        $this->_columns['content'] = array('type' => 'text');
    }
}
