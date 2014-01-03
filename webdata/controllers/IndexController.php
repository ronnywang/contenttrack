<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
        if ($_GET['id']) {
            $this->view->track = Track::find(intval($_GET['id']));
        }
    }

    public function edittrackAction()
    {
        // TODO: check sToken
        if (!$_POST) {
            return $this->redirect('/');
        }

        if (!$track = Track::find(intval($_GET['id']))) {
            return $this->redirect('/');
        }

        $track->update(array(
            'url' => strval($_POST['url']),
            'options' => json_encode(array(
                'track_way' => intval($_POST['track-way']),
                'track_content' => strval($_POST['track-content']),
            )),
        ));

        return $this->redirect('/');
    }

    public function addtrackAction()
    {
        // TODO: check sToken
        if (!$_POST) {
            return $this->redirect('/');
        }

        Track::insert(array(
            'created_at' => time(),
            'tracked_at' => 0,
            'url' => strval($_POST['url']),
            'options' => json_encode(array(
                'track_way' => intval($_POST['track-way']),
                'track_content' => strval($_POST['track-content']),
            )),
        ));
        return $this->redirect('/');
    }
}
