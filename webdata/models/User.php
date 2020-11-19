<?php

class UserRow extends Pix_Table_Row
{
    public function notify($title, $content)
    {
        $uc = json_decode(UserConfig::find($this->user_id)->config);
        if ($uc->slack_token) {
            $curl = curl_init("https://slack.com/api/chat.postMessage?token=" . urlencode($uc->slack_token) . '&channel=%23' . urlencode($uc->slack_channel) . '&username=contenttrack');
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'text=' . urlencode($content));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_exec($curl);
        }

        $mail = substr($this->user_name, 9);
        NotifyLib::alert(
            $title,
            $content,
            $mail
        );
    }
}

class User extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user';
        $this->_rowClass = 'UserRow';

        $this->_primary = 'user_id';

        $this->_columns['user_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['user_name'] = array('type' => 'varchar', 'size' => 64);

        $this->addIndex('user_name', array('user_name'), 'unique');

        $this->_relations['user_teams'] = array('rel' => 'has_many', 'type' => 'TeamMember', 'foreign_key' => 'user_id');
    }
}
