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
            'track_period' => intval($_POST['track_period']),
            'updated_at' => time(),
            'title' => strval($_POST['title']),
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
            'track_period' => intval($_POST['track_period']),
            'title' => strval($_POST['title']),
            'url' => strval($_POST['url']),
            'options' => json_encode(array(
                'track_way' => intval($_POST['track-way']),
                'track_content' => strval($_POST['track-content']),
            )),
        ));
        return $this->redirect('/');
    }

    public function checkAction()
    {
        if (!$track = Track::Find(intval($_GET['id']))) {
            return $this->redirect('/');
        }

        $track->update(array(
            'tracked_at' => time()
        ));
        $track->updateLog(json_encode($track->trackContent()));
        return $this->redirect('/');
    }
}
