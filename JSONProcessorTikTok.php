<?php

class JSONProcessorTikTok
{
    private mixed $data;


    public function __construct($json)
    {
        // Take in Json data (not file!).
        $this->data = json_decode($json, true);
    }


    public function getTikTokDataAsDict(): array
    {
        if (isset($this->data['Activity'])) {
            try {
                // Declare some helper variables
                $login_list = $this->data['Activity']['Login History']["LoginHistoryList"];
                $video_list = $this->data['Activity']['Video Browsing History']['VideoList'] ?? [];
                $num_sessions = count(array_unique(array_column($login_list, 'Date'))) ?? 0;
                $comments = $this->data['Comment']["Comments"]['CommentsList'] ?? [];
                $likes = $this->data['Activity']['Like List']['ItemFavoriteList'] ?? [];
                $shares = $this->data['Activity']['Share History']['ShareHistoryList'] ?? [];
                $watch_time_array = $this->getWatchTimeData($video_list) ?? [];
                $most_shares = $this->getMostDayAndAmount(array_count_values(array_column($shares, 'Date'))) ?? [];
                $most_likes = $this->getMostDayAndAmount(array_count_values(array_column($likes, 'Date'))) ?? [];

                // Return personalized data.
                $personalized_data = array(
                    // Personal info
                    "demo" => 0,
                    "start_date" => date('d/m/Y', strtotime(end($video_list)['Date'])) ?? 'Unknown',
                    "name" => $this->data['Profile']['Profile Information']['ProfileMap']['PlatformInfo'][0]['Name'] ?? $this->data['Profile']['Profile Information']['ProfileMap']["userName"],
                    "last_vid_in_data" => date('d/m/Y', strtotime($video_list[0]['Date'])) ?? 'Unknown',

                    // Watch sessions
                    "num_videos_watched" => count($video_list),
                    "total_watch_time" => strval($watch_time_array[0]),
                    "total_watch_days" => strval(round($watch_time_array[0] / 1440)),
                    "num_watch_sessions" => $num_sessions,
                    "avg_session_length" => strval(round($watch_time_array[0] / $num_sessions)) ?? "Data missing",
                    "avg_video_length" => strval(round($watch_time_array[0] / count($video_list) * 60)),
                    "longest_watch_date" => $watch_time_array[1],
                    "longest_watch_time" => strval($watch_time_array[2]),
                    "tiktok_day" => $this->getMostCommonDay($login_list),

                    // Comments
                    "num_of_comments" => count($comments),
                    "avg_comment_length" => $this->getCommentData($comments)[0],
                    "favourite_emoji" => $this->getCommentData($comments)[1] ?? "[empty]",
                    "favourite_emoji_amount" => $this->getCommentData($comments)[2],

                    // Likes
                    "num_of_likes" => count($likes),
                    "record_of_likes_date" => $most_likes[0],
                    "record_of_likes" => $most_likes[1],

                    // Shares
                    "num_of_shares" => count($shares),
                    "most_shares_day" => $most_shares[0],
                    "most_shares_amount" => $most_shares[1],

                    // Live
                    "total_lives_viewed" => isset($this->data['Tiktok Live']['Watch Live History']) ? count($this->data['Tiktok Live']['Watch Live History']) : '0',
                    "total_comments_on_lives" => $this->data[""] ?? "Unavailable"
                );
                return $this->calculate_persona($personalized_data);
            } catch (Exception $e) {
                echo '<script>console.log("Error gathering data.")</script>';
                echo '<script>alert("An error occured: ' . $e->getMessage() . '");</script>';
                exit();
            }
        } else {
            // Return default data.
            $fake_data = array(
                // Personal info
                "demo" => 1,
                "start_date" => "01/01/2022",
                "name" => "John",
                "last_vid_in_data" => "30/10/2023",

                // Watch sessions
                "num_videos_watched" => 223982,
                "total_watch_time" => 90121,
                "total_watch_days" => 63,
                "num_watch_sessions" => 2900,
                "avg_session_length" => 99,
                "avg_video_length" => 24,
                "longest_watch_date" => "02/10/2022",
                "longest_watch_time" => 420,
                "tiktok_day" => "Friday",

                // Comments
                "num_of_comments" => 1902,
                "avg_comment_length" => 32,
                "favourite_emoji" => "❤️",
                "favourite_emoji_amount" => 1000,

                // Likes
                "num_of_likes" => 19120,
                "record_of_likes" => 130,
                "record_of_likes_date" => "03/10/2022",

                // Shares
                "num_of_shares" => 600,
                "most_shares_day" => "03/10/2022",
                "most_shares_amount" => 49,

                // Live
                "total_lives_viewed" => 1000,
                "total_comments_on_lives" => 399
            );

            return $this->calculate_persona($fake_data);
        }
    }


    // Define helper functions.
    private function calculate_persona(array $personalized_data): array
    {
        switch (true) {
            case ($personalized_data['num_videos_watched'] > 50000 and $personalized_data['avg_session_length'] > 20 and $personalized_data['num_of_likes'] > 2000):
                $personalized_data['persona'] = "The Enthusiast";
                $personalized_data['persona_description'] = "\"Why not love everything about TikTok?\" - You";
                break;
            case ($personalized_data['num_videos_watched'] > 50000 and $personalized_data['avg_session_length'] > 30):
                $personalized_data['persona'] = "Nolifer";
                $personalized_data['persona_description'] = "TikTok may as well be life at this rate...";
                break;
            case ($personalized_data['num_of_likes'] > ($personalized_data['num_videos_watched'] / 2) and ($personalized_data['num_of_comments'] > 200)):
                $personalized_data['persona'] = "Superman";
                $personalized_data['persona_description'] = "You are supportive and love to show what you like!";
                break;
            case ($personalized_data['num_of_comments'] > 2000 and $personalized_data['avg_comment_length'] > 25):
                $personalized_data['persona'] = "The Charmer";
                $personalized_data['persona_description'] = "You write the longest and most charming comments!";
                break;
            case ($personalized_data['num_of_comments'] > 2000 and $personalized_data['num_of_shares'] > 1000 and $personalized_data['num_of_likes'] > 2000):
                $personalized_data['persona'] = "Interactive bunny";
                $personalized_data['persona_description'] = "You love to interact and jump around!";
                break;
            case ($personalized_data['num_videos_watched'] > 50000):
                $personalized_data['persona'] = "Adventurer";
                $personalized_data['persona_description'] = "You like to explore the TikTok!";
                break;
            case ($personalized_data['num_watch_sessions'] > 500 and $personalized_data['avg_session_length'] < 20):
                $personalized_data['persona'] = "Lifestyle admirer";
                $personalized_data['persona_description'] = "You like to keep an eye on your favourites!";
                break;
            case ($personalized_data['num_videos_watched'] > 2500 and $personalized_data['num_of_likes'] < 50 and $personalized_data['num_of_comments'] < 15):
                $personalized_data['persona'] = "Lurker";
                $personalized_data['persona_description'] = "You like to watch videos without interacting!";
                break;
            case ($personalized_data['num_watch_sessions'] < 200):
                $personalized_data['persona'] = "Trend Follower";
                $personalized_data['persona_description'] = "You like to scroll TikTok to see the world!";
                break;
            default:
                $personalized_data['persona'] = "Calm Guru";
                $personalized_data['persona_description'] = "Chilling, watching TikTok - that is you!";
                break;
        }

        return $personalized_data;
    }


    private function getWatchTimeData(mixed $video_list): array
    {
        if (empty($video_list)) {
            return ['No videos', 0, 0];
        }

        $overall_total_watch_time = 0;
        $day_total_watch_time = 0;
        $longest_watch_time = 0;
        $longest_watch_date = "";
        $prev_date = strtotime($video_list[0]["Date"]);
        $current_day = date('d/m/Y', $prev_date);

        foreach ($video_list as $video) {
            $date_start = strtotime($video["Date"]);
            $day = date('d/m/Y', $date_start);

            if ($day !== $current_day) {
                // New day, check if last was the longest.
                if ($day_total_watch_time > $longest_watch_time) {
                    $longest_watch_date = $current_day;
                    $longest_watch_time = $day_total_watch_time;
                }

                $overall_total_watch_time += $day_total_watch_time;

                // Reset for the new day.
                $current_day = $day;
                $day_total_watch_time = 0;
            }

            $time_difference_minutes = abs($prev_date - $date_start) / 60;

            if ($time_difference_minutes > 5) {
                $day_total_watch_time += 0.5;  // Default watch time for last video of session.
            } else {
                $day_total_watch_time += $time_difference_minutes;
            }

            $prev_date = $date_start;
        }
        // Check the last day.
        if ($day_total_watch_time > $longest_watch_time) {
            $longest_watch_date = $current_day;
            $longest_watch_time = $day_total_watch_time;
        }

        $overall_total_watch_time += $day_total_watch_time;

        return [
            round($overall_total_watch_time),
            $longest_watch_date,
            round($longest_watch_time)];
    }


    private function getMostDayAndAmount($dataArray): array
    {
        if (!empty($dataArray)) {
            $dayCount = [];

            foreach ($dataArray as $date => $amount) {
                $day = date("d/m/Y", strtotime($date));
                if (isset($dayCount[$day])) {
                    $dayCount[$day] += $amount;
                } else {
                    $dayCount[$day] = $amount;
                }
            }

            $mostDay = array_search(max($dayCount), $dayCount);
            $mostAmount = $dayCount[$mostDay];
        } else {
            $mostDay = 'No data found';
            $mostAmount = 0;
        }

        return [$mostDay, $mostAmount]; //  Array where index 0 represents the day and index 1 the amount.
    }


    private function getCommentData(mixed $comments): array
    {
        $totalCommentLength = 0;
        $commentCount = 0;
        $emojiCount = [];

        // Loop through the comments
        foreach ($comments as $comment) {
            $commentText = $comment['Comment'];

            $commentLength = mb_strlen($commentText, 'UTF-8');
            $totalCommentLength += $commentLength;
            $commentCount++;

            // Check for emojis and count them
            preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{1FB00}-\x{1FBFF}\x{1FC00}-\x{1FCFF}\x{1F004}\x{1F0CF}]/u', $commentText, $matches);

            if (!empty($matches[0])) {
                foreach ($matches[0] as $emoji) {
                    if (array_key_exists($emoji, $emojiCount)) {
                        $emojiCount[$emoji]++;
                    } else {
                        $emojiCount[$emoji] = 1;
                    }
                }
            }
        }

        // Calculate average comment length
        $avgCommentLength = ($commentCount > 0) ? $totalCommentLength / $commentCount : 0;

        // Find fav emoji and count
        arsort($emojiCount);
        $favEmoji = key($emojiCount);
        $favEmojiCount = 0;
        $favEmojiCount = current($emojiCount);

        return [$avgCommentLength, $favEmoji, $favEmojiCount];
    }


    private function getMostCommonDay($login_list): string
    {
        $dayOfWeekCounts = [
            'Sunday' => 0,
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0
        ];

        foreach ($login_list as $login) {
            try {
                $date = new DateTime($login["Date"]);
                $dayOfWeek = $date->format("l"); // Get the day of the week

                if (array_key_exists($dayOfWeek, $dayOfWeekCounts)) {
                    $dayOfWeekCounts[$dayOfWeek]++;
                }
            } catch (Exception) {
                continue;
            }
        }

        arsort($dayOfWeekCounts);
        return key($dayOfWeekCounts);
    }

    public function getTikTokSlideshowTexts($dictData): bool|string
    {
        // Fill dynamic variables included in slideshow text
        if ($dictData['total_watch_time'] <= 1000) {
            $remark = "You are not addicted - and that is a good thing!";
            $actions = ["watched a few educational videos or tutorials.", "started a simple workout routine or yoga practice.", "explored a new hobby or activity, such as drawing or knitting."];
        } elseif ($dictData['total_watch_time'] <= 20000) {
            $remark = "That is cool - you watch to keep yourself entertained and updated!";
            $actions = ["gained proficiency in cooking various cuisines or dishes.", "completed a short online course or several smaller courses.", "learnt a new musical instrument at a basic level."];
        } elseif ($dictData['total_watch_time'] <= 100000) {
            $remark = "Well, I guess we found out that you like TikTok...";
            $actions = ["completed an extensive certification program.", "learnt a new language.", "developed mediocre skills for playing a musical instrument."];
        } else {
            $remark = "Okay... Maybe you should go and see someone?";
            $actions = ["written your own song and performed it.", "launched a small business or side project.", "trained for a marathon or triathlon."];
        }
        $action = $actions[array_rand($actions)];

        if ($dictData['num_of_comments'] <= 15) {
            $chattingDescription = "You are quiet..";
        } else if ($dictData['num_of_comments'] <= 100) {
            $chattingDescription = "You like to give your opinion - that is good!";
        } else {
            $chattingDescription = "You must be popular in comment sections!";
        }

        if ($dictData['num_of_likes'] <= 25) {
            $likesDescription = "Like button misses you...";
        } else if ($dictData['num_of_likes'] <= 500) {
            $likesDescription = "Seems okay, but do not be afraid to show the creators you like their content!";
        } else {
            $likesDescription = "Do not worry - the like button is just fine - keep smashing it!";
        }

        // Make slideshow text from data.
        // CSS needs to have defined slideshow-x-larger-bolder, slideshow-larger-bolder, slideshow-x-larger, slideshow-larger.
        $nameIntro = "<span class='slideshow-larger-bolder'>{$dictData['name']}</span>, right?<br>Let's take a look at your activity on TikTok, shall we.";

        $remarkInfo = "since <span class='slideshow-larger'>{$dictData['start_date']}</span><br>you've watched <span class='slideshow-larger-bolder'>{$dictData['num_videos_watched']}</span> videos<br$remark";

        $watchSessions = "you've had<br><span class='slideshow-x-larger-bolder'>{$dictData['num_watch_sessions']}</span><br>watch sessions";

        $averageSessionLength = "When you open TikTok,<br>on average<br>you spend <span class='slideshow-larger-bolder'>{$dictData['avg_session_length']}</span> minutes watching videos...";

        $totalWatchTime = "with a total watch time of <b><span class='slideshow-x-larger'>{$dictData['total_watch_time']}</span> minutes.</b><br>That's <span class='slideshow-bolder'>{$dictData['total_watch_days']}</span> days!";

        $actionInfo = "<span class='slideshow-larger'>In that time you could've<br>$action<br></span>But you didn't...";

        $longestWatchSession = "Your longest watch session was on <span class='slideshow-larger'>{$dictData['longest_watch_date']}</span><br>and lasted <span class='slideshow-bolder'>{$dictData['longest_watch_time']}</span> minutes<br>Must have been a hard day.";

        $tikTokDayInfo = "You use TikTok the most on <span class='slideshow-x-larger-bolder'>{$dictData['tiktok_day']}</span>";

        $chattingInfo = "You wrote <span class='slideshow-larger-bolder'>{$dictData['num_of_comments']}</span> comments<br>and used the {$dictData['favourite_emoji']} emoji <span class='slideshow-bolder'>{$dictData['favourite_emoji_amount']}</span> times<br$chattingDescription";

        $likesInfo = "You liked <span class='slideshow-x-larger-bolder'>{$dictData['num_of_likes']}</span> videos<br>and<br>set a record by liking <span class='slideshow-larger-bolder'>{$dictData['record_of_likes']}</span> videos on <span class='slideshow-bolder'>{$dictData['record_of_likes_date']}</span><br$likesDescription";

        $personaInfo = "Your TikTok persona is <span class='slideshow-larger-bolder'>{$dictData['persona']}</span><br>{$dictData['persona_description']}";

        $dataForSlideshowText = array(
            $nameIntro,
            $remarkInfo,
            $watchSessions,
            $averageSessionLength,
            $totalWatchTime,
            $actionInfo,
            $longestWatchSession,
            $tikTokDayInfo,
            $chattingInfo,
            $likesInfo,
            $personaInfo
        );

        return json_encode($dataForSlideshowText);
    }
}