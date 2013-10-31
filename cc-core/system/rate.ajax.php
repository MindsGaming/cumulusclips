<?php

Plugin::triggerEvent('rate.ajax.start');

// Verify if user is logged in
$userService = new UserService();
$loggedInUser = $userService->loginCheck();
Plugin::triggerEvent('rate.ajax.login_check');

// Verify a video was selected
if (empty($_POST['video_id']) || !is_numeric ($_POST['video_id'])) App::Throw404();

// Check if video is valid
$videoMapper = new VideoMapper();
$video = $videoMapper->getVideoByCustom(array('video_id' => $_POST['video_id'], 'status' => 'approved'));
if (!$video) App::Throw404();

// Verify rating was given
if (!isset($_POST['rating']) || !in_array($_POST['rating'], array('1','0'))) App::Throw404();

// Verify user is logged in
if (!$loggedInUser) {
    echo json_encode(array('result' => 0, 'msg' => (string) Language::GetText('error_rate_login')));
    exit();
}

// Check user doesn't rate his own video
if ($loggedInUser->userId == $video->userId) {
    echo json_encode(array('result' => 0, 'msg' => (string) Language::GetText('error_rate_own')));
    exit();
}

// Submit rating
$ratingService = new RatingService();
$rating = new Rating();
$rating->rating = (int) $_POST['rating'];
$rating->videoId = $video->videoId;
$rating->userId = $loggedInUser->userId;
if ($ratingService->rateVideo($rating)) {
    Plugin::triggerEvent('rate.ajax.rate_video');
    echo json_encode(array('result' => 1, 'msg' => (string) Language::GetText('success_rated'), 'other' => Rating::GetRating ($video->video_id)));
    exit();
} else {
    Plugin::triggerEvent('rate.ajax.rate_video_duplicate');
    echo json_encode(array('result' => 0, 'msg' => (string) Language::GetText('error_rate_duplicate')));
    exit();
}