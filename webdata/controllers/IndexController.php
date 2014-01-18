<?php

class IndexController extends Pix_Controller
{
    public function init()
    {
        $this->view->user = ($user_id = Pix_Session::get('user_id')) ? User::find(intval($user_id)) : null;
        if (!$this->view->user) {
            return $this->redraw('/index/empty.phtml');
        }

        if (!$sToken = Pix_Session::get('sToken')) {
            $sToken = crc32(uniqid());
            Pix_Session::set('sToken', $sToken);
        }
        $this->view->sToken = $sToken;
    }

    public function indexAction()
    {
        if ($_GET['id']) {
            $this->view->track = Track::find(intval($_GET['id']));
        }
    }

    public function toggletrackAction()
    {
        if (!$_POST) {
            return $this->redirect('/');
        }

        if ($_POST['sToken'] != $this->view->sToken) {
            return $this->redirect('/');
        }

        if (!$track = Track::find(intval($_GET['id']))) {
            return $this->redirect('/');
        }

        try {
            TrackUser::insert(array(
                'track_id' => $track->id,
                'user_id' => $this->view->user->user_id,
            ));
        } catch (Pix_Table_DuplicateException $e) {
            TrackUser::search(array(
                'track_id' => $track->id,
                'user_id' => $this->view->user->user_id,
            ))->delete();
        }

        return $this->redirect('/?id=' . $track->id . '#track-user');
    }
    public function edittrackAction()
    {
        if (!$_POST) {
            return $this->redirect('/');
        }

        if ($_POST['sToken'] != $this->view->sToken) {
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
        if (!$_POST) {
            return $this->redirect('/');
        }
        if ($_POST['sToken'] != $this->view->sToken) {
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
