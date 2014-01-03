<?php

class Track extends Pix_Table
{
    public function init()
    {
        $this->_name = 'track';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['tracked_at'] = array('type' => 'int');
        $this->_columns['url'] = array('type' => 'varchar', 'size' => 255);
        $this->_columns['options'] = array('type' => 'text');
    }
}
