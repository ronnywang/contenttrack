<?php

class UserConfig extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user_config';
        $this->_primary = 'user_id';

        $this->_columns['user_id'] = array('type' => 'int');
        $this->_columns['config'] = array('type' => 'text');
    }
}
