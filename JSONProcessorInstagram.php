<?php

class JSONProcessorInstagram
{
    private array $dictdata;
    private array $filePaths;
    private array $files_to_match = ["personal_information.json",
        "quizzes.json", "polls.json", "story_likes.json", "liked_posts.json", "liked_comments.json", "post_comments_1.json",
        "reels_comments.json", "account_information.json", "saved_posts.json", "posts_1.json", "stories.json", "following.json",
        "followers_1.json", "recent_follow_requests.json", "advertisers_using_your_activity_or_information.json",
        "signup_information.json"];


    public function __construct(array $filePaths)
    {
        // Take in an array of json files.
        $this->filePaths = $filePaths;
        $this->dictdata = array("demo" => 0);
    }

    /**
     * Get Instagram Data as a dictionary. You have to initialize the class with json filepaths.
     */
    public function getInstagramDataAsDict(): array
    {
        // Get files basenames (with '.json')
        $basenames = array_map('basename', $this->filePaths);
        // Check if all necessary files available.
        if (count(array_intersect($this->files_to_match, $basenames)) === count($this->files_to_match)) {
            // Start collecting data
            foreach ($this->filePaths as $filePath) {
                $file_name = basename($filePath);  // Extract the file name from the path.

                // Check if the current file matches the desired file names.
                if (in_array($file_name, $this->files_to_match)) {
                    // Process the matched JSON file.
                    $json_contents = file_get_contents($filePath);
                    $json_data = json_decode($json_contents, true);

                    // Dynamically call functions based on the file name.
                    $this->processDataBasedOnFileName($file_name, $json_data);
                }
            }
        } // If there are not enough of correct files then get the demo data.
        else {
            $this->dictdata = array(
                "demo" => 1,
                "name" => "John Doe",
                "signup_date" => date('d/m/Y', "1481732904"),
                "last_login_date" => date('d/m/Y', "1641732904"),
                "last_logout_date" => date('d/m/Y', "1604074420"),
                "first_story_date" => date('d/m/Y', "1497087452"),
                "last_story_date" => date('d/m/Y', "1700000000"),
                "first_close_friends_story_date" => date('d/m/Y', "1699000000"),
                "story_interactions_quizzes_thisyear" => 12,
                "story_interactions_polls_thisyear" => 23,
                "story_interactions_likes_thisyear" => 65,
                "post_likes_thisyear" => 223,
                "comment_likes_thisyear" => 23,
                "post_comments_alltime" => 12,
                "reels_comments_alltime" => 18,
                "saved_posts_thisyear" => 300,
                "posts_thisyear" => 5,
                "stories_thisyear" => 42,
                "following_total_thisyear" => 21,
                "follows_total_thisyear" => 44,
                "advertisers_using_your_activity_count" => 222
            );
        }

        return $this->calculate_persona();
    }

    /**
     * You must call getInstagramDataAsDict() before calling getSlideshowTexts()
     */
    public function getSlideshowTexts(): bool|string
    {
        $nameIntro = "<span class='slideshow-larger-bolder'>{$this->dictdata['name']}</span>, right?<br>Let's take a look at your activity on Instagram, shall we.";

        $likeInfo = "This year you liked<br><span class='slideshow-x-larger-bolder'>{$this->dictdata['post_likes_thisyear']} posts</span> and <span class='slideshow-x-larger-bolder'>{$this->dictdata['story_interactions_likes_thisyear']} stories!</span>";

        $commentSum = intval($this->dictdata['post_comments_alltime']) + intval($this->dictdata['reels_comments_alltime']);
        $commentsInfo = "Over the years you have written <span class='slideshow-larger-bolder'>{$commentSum}</span> comments<br>and this year you liked <span class='slideshow-larger-bolder'>{$this->dictdata['comment_likes_thisyear']} comments...</span>";

        $postedInfo = "You posted <span class='slideshow-x-larger-bolder'>{$this->dictdata['posts_thisyear']} posts</span> and <span class='slideshow-x-larger-bolder'>{$this->dictdata['stories_thisyear']} stories</span>";

        if ($this->dictdata['saved_posts_thisyear'] > 100) {
            $storyMessage = "Whoop-whoop!";
        } else {
            $storyMessage = "Yahoo-yippee!";
        }
        $savedInfo = "You saved <span class='slideshow-x-larger-bolder'>{$this->dictdata['saved_posts_thisyear']}</span> posts this year!<br><span class='slideshow-larger'>{$storyMessage}</span>";

        if ($this->dictdata['story_interactions_quizzes_thisyear'] < 10 and $this->dictdata['story_interactions_polls_thisyear'] < 10) {
            $storyInfo = "Not a fan of interacting on stories?<br>You took part in <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_quizzes_thisyear']} quizes</span> and <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_polls_thisyear']} polls</span>";
        } elseif ($this->dictdata['story_interactions_quizzes_thisyear'] < 75 and $this->dictdata['story_interactions_polls_thisyear'] < 75) {
            $storyInfo = "Stories are cool, aren't they?<br>You interacted with <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_quizzes_thisyear']} quizes</span> and <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_polls_thisyear']} polls</span>";
        } else {
            $storyInfo = "You seem to love stories!<br>Participating in <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_quizzes_thisyear']} quizes</span> and <span class='slideshow-larger-bolder'>{$this->dictdata['story_interactions_polls_thisyear']} polls</span>";
        }

        if ($this->dictdata['following_total_thisyear'] > $this->dictdata['follows_total_thisyear']) {
            $followText = "followed more than you were followed!<br> You are a giver!";
        } elseif ($this->dictdata['following_total_thisyear'] < $this->dictdata['follows_total_thisyear']) {
            $followText = "gained more followers than you followed yourself!<br> Must feel nice!";
        } else {
            $followText = "gained as much follows as you gave them!<br> Nice job!";
        }
        $followInfo = "This year you {$followText}";

        $adInfo = "Did you know that <span class='slideshow-larger-bolder'>{$this->dictdata['advertisers_using_your_activity_count']} advertisers</span> used your activity?<br> Well, happens to be so...";

        $personaInfo = "Your Instagram persona is <span class='slideshow-larger-bolder'>{$this->dictdata['persona']}</span><br>{$this->dictdata['persona_description']}";

        // CSS needs to have defined slideshow-x-larger-bolder, slideshow-larger-bolder, slideshow-x-larger, slideshow-larger.
        return json_encode(array(
            $nameIntro,
            $likeInfo,
            $commentsInfo,
            $postedInfo,
            $savedInfo,
            $storyInfo,
            $followInfo,
            $adInfo,
            $personaInfo
        ));
    }

    /* Start of Private Functions. */
    // Process data according to filename
    private function processDataBasedOnFileName($file_name, $json_data): void
    {
        switch ($file_name) {
            case 'personal_information.json':
                $this->dictdata["name"] = isset($json_data['profile_user'][0]['string_map_data']['Name']['value']) ? utf8_decode($json_data['profile_user'][0]['string_map_data']['Name']['value']) : "Unknown";
                break;
            case 'signup_information.json':
                $this->dictdata['signup_date'] = isset($json_data['account_history_registration_info'][0]['string_map_data']['Time']['timestamp']) ? date('d/m/Y', $json_data['account_history_registration_info'][0]['string_map_data']['Time']['timestamp']) : "Unknown";
                break;
            case 'account_information.json':
                $this->dictdata["last_login_date"] = isset($json_data['profile_account_insights'][0]['string_map_data']['Last Login']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['Last Login']['timestamp']) : "None";
                $this->dictdata["last_logout_date"] = isset($json_data['profile_account_insights'][0]['string_map_data']['Last Logout']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['Last Logout']['timestamp']) : "None";
                $this->dictdata["first_story_date"] = isset($json_data['profile_account_insights'][0]['string_map_data']['First Story Time']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['First Story Time']['timestamp']) : "None";
                $this->dictdata["last_story_date"] = isset($json_data['profile_account_insights'][0]['string_map_data']['Last Story Time']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['Last Story Time']['timestamp']) : "None";
                $this->dictdata["first_close_friends_story_date"] = isset($json_data['profile_account_insights'][0]['string_map_data']['First Close Friends Story Time']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['First Close Friends Story Time']['timestamp']) : "None";
                break;
            case 'quizzes.json':
                $this->dictdata["story_interactions_quizzes_thisyear"] = count($json_data['story_activities_quizzes']) ?? 0;
                break;
            case 'polls.json':
                $this->dictdata["story_interactions_polls_thisyear"] = count($json_data['story_activities_polls']) ?? 0;
                break;
            case 'story_likes.json':
                $this->dictdata["story_interactions_likes_thisyear"] = count($json_data['story_activities_story_likes']) ?? 0;
                break;
            case 'liked_posts.json':
                $this->dictdata["post_likes_thisyear"] = count($json_data['likes_media_likes']) ?? 0;
                break;
            case 'liked_comments.json':
                $this->dictdata["comment_likes_thisyear"] = count($json_data['likes_comment_likes']) ?? 0;
                break;
            case 'post_comments_1.json':
                $this->dictdata["post_comments_alltime"] = count($json_data) ?? 0;
                break;
            case 'reels_comments.json':
                $this->dictdata["reels_comments_alltime"] = count($json_data['comments_reels_comments']) ?? 0;
                break;
            case 'saved_posts.json':
                $this->dictdata["saved_posts_thisyear"] = count($json_data['saved_saved_media']) ?? 0;
                break;
            case 'posts_1.json':
                $this->dictdata["posts_thisyear"] = count($json_data) ?? 0;
                break;
            case 'stories.json':
                $this->dictdata["stories_thisyear"] = count($json_data['ig_stories']) ?? 0;
                break;
            case 'following.json':
                $this->dictdata["following_total_thisyear"] = count($json_data['relationships_following']) ?? 0;
                break;
            case 'followers_1.json':
                $this->dictdata["follows_total_thisyear"] = count($json_data) ?? 0;
                break;
            case 'advertisers_using_your_activity_or_information.json':
                $this->dictdata['advertisers_using_your_activity_count'] = count($json_data['ig_custom_audiences_all_types']) ?? 0;
                break;
            default:
                // Extra file that came through...
                break;
        }
    }

    // Calculate persona from personalized data
    private function calculate_persona(): array
    {

        switch (true) {
            case ($this->dictdata['post_likes_thisyear'] > 1500 and $this->dictdata['comment_likes_thisyear'] > 250 and $this->dictdata['story_interactions_likes_thisyear'] > 100 and $this->dictdata['story_interactions_polls_current'] > 50 and $this->dictdata['story_interactions_quizzes_current'] > 50):
                $this->dictdata['persona'] = "The Enthusiast";
                $this->dictdata['persona_description'] = "Whatever is on Instagram, you love it!";
                break;
            case ($this->dictdata['posts_thisyear'] > 250 and $this->dictdata['post_likes_thisyear'] > 2000):
                $this->dictdata['persona'] = "Nolifer";
                $this->dictdata['persona_description'] = "Instagram loves you and you love Instagram.";
                break;
            case ($this->dictdata['post_likes_thisyear'] > 1000 and $this->dictdata['comment_likes_thisyear'] > 250 and $this->dictdata['story_interactions_likes_thisyear'] > 50 and $this->dictdata['post_comments_alltime'] > 500):
                $this->dictdata['persona'] = "Superman";
                $this->dictdata['persona_description'] = "You are supportive and love to show what you like!";
                break;
            case ($this->dictdata['comment_likes_thisyear'] > 100 and $this->dictdata['post_comments_alltime'] > 5000):
                $this->dictdata['persona'] = "The Charmer";
                $this->dictdata['persona_description'] = "You write the longest and most charming comments!";
                break;
            case ($this->dictdata['post_likes_thisyear'] > 250 and $this->dictdata['story_interactions_likes_current'] > 100 and $this->dictdata['story_interactions_polls_current'] > 50 and $this->dictdata['story_interactions_quizzes_current'] > 25):
                $this->dictdata['persona'] = "Lifestyle admirer";
                $this->dictdata['persona_description'] = "You like to keep an eye on your favourites!";
                break;
            case ($this->dictdata['saved_posts_thisyear'] > 75 and $this->dictdata['following_total_thisyear'] > 50):
                $this->dictdata['persona'] = "Adventurer";
                $this->dictdata['persona_description'] = "You like to explore Instagram!";
                break;
            case ($this->dictdata['post_likes_thisyear'] < 50 and $this->dictdata['comment_likes_thisyear'] < 10 and $this->dictdata['post_comments_alltime'] < 20):
                $this->dictdata['persona'] = "Lurker";
                $this->dictdata['persona_description'] = "You like to scroll without interacting!";
                break;
            default:
                    $this->dictdata['persona'] = "Chiller";
                    $this->dictdata['persona_description'] = "You just like to chill!";
                    break;
        }

        return $this->dictdata;
    }
}