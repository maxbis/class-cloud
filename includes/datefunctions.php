<?php
function formatCreationDate($created_at) {
    // Convert the created_at value into a timestamp and format it as "YYYY-MM-DD HH:MM"
    $createdTimestamp = strtotime($created_at);
    $formattedDate = date('Y-m-d H:i', $createdTimestamp);

    // Calculate the time difference between now and the creation date
    $createdDate = new DateTime($created_at);
    $now = new DateTime();
    $interval = $now->diff($createdDate);
    $days = $interval->days;

    // Determine the human-readable label based on the number of days difference
    if ($days === 0) {
        $timeLabel = 'today';
    } elseif ($days === 1) {
        $timeLabel = 'yesterday';
    } elseif ($days < 7) {
        $timeLabel = 'a few days ago';
    } elseif ($days < 14) {
        $timeLabel = 'last week';
    } elseif ($days < 21) {
        $timeLabel = 'weeks ago';
    } elseif ($days < 28) {
        $timeLabel = 'three weeks ago';
    } elseif ($days < 35) {
        $timeLabel = '4 weeks ago';
    } else {
        $timeLabel = 'a month or longer ago';
    }

    return $formattedDate . ', ' . $timeLabel;
}
?>