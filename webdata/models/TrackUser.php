<?php

class TrackUser extends Pix_Table
{
    public function init()
    {
        $this->_name = 'track_user';
        $this->_primary = array('track_id', 'user_id');

        $this->_columns['track_id'] = array('type' => 'int');
        $this->_columns['user_id'] = array('type' => 'int');
    }
}
