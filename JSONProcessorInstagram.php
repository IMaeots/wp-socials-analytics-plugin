<?php

class JSONProcessorInstagram
{
    private array $dictdata;
    private array $filePaths;
    private array $files_to_match = ["personal_information.json", "suggested_accounts_viewed.json",
        "quizzes.json", "polls.json", "story_likes.json", "liked_posts.json", "liked_comments.json", "post_comments_1.json",
        "reels_comments.json", "account_information.json", "saved_posts.json", "posts_1.json", "stories.json"];


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
                "last_login_timestamp" => date('d/m/Y', "1641732904"),
                "last_logout_timestamp" => date('d/m/Y', "1604074420"),
                "first_ever_story_timestamp" => date('d/m/Y', "1497087452"),
                "suggested_accounts_viewed_alltime" => 127,
                "story_interactions_quizzes_current" => 12,
                "story_interactions_polls_current" => 23,
                "story_interactions_likes_current" => 65,
                "post_likes_current" => 223,
                "comment_likes_current" => 23,
                "post_comments_alltime" => 12,
                "reels_comments_alltime" => 18,
                "saved_posts_thisyear" => 300,
                "posts_thisyear" => 5,
                "stories_thisyear" => 42
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

        $commentsInfo = "You have written <span class='slideshow-larger-bolder'>{$this->dictdata['post_comments_alltime']}</span> comments in Instagram<br>and this year you liked <span class='slideshow-larger-bolder'>{$this->dictdata['comment_likes_current']} comments...</span>";

        $savedInfo = "You saved <span class='slideshow-x-larger-bolder'>{$this->dictdata['saved_posts_thisyear']}</span> posts this year!<br><span class='slideshow-larger'>Whoop-Whoop!</span>";

        $personaInfo = "Your Instagram persona is <span class='slideshow-larger-bolder'>{$this->dictdata['persona']}</span><br>{$this->dictdata['persona_description']}";

        // CSS needs to have defined slideshow-x-larger-bolder, slideshow-larger-bolder, slideshow-x-larger, slideshow-larger.
        return json_encode(array(
            $nameIntro,
            $commentsInfo,
            $savedInfo,
            $personaInfo
        ));
    }

    /* Start of Private Functions. */
    // Process data according to filename
    private function processDataBasedOnFileName($file_name, $json_data): void
    {
        switch ($file_name) {
            case 'personal_information.json':
                $this->dictdata["name"] = isset($json_data['profile_user'][0]['string_map_data']['Name']['value']) ? $json_data['profile_user'][0]['string_map_data']['Name']['value'] : "Unknown";
                break;
            case 'account_information.json':
                $this->dictdata["last_login_timestamp"] = isset($json_data['profile_account_insights'][0]['string_map_data']['Last Login']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['Last Login']['timestamp']) : "Data missing";
                $this->dictdata["last_logout_timestamp"] = isset($json_data['profile_account_insights'][0]['string_map_data']['Last Logout']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['Last Logout']['timestamp']) : "Data missing";
                $this->dictdata["first_ever_story_timestamp"] = isset($json_data['profile_account_insights'][0]['string_map_data']['First Story Time']['timestamp']) ? date('d/m/Y', $json_data['profile_account_insights'][0]['string_map_data']['First Story Time']['timestamp']) : "Data missing";
                break;
            case 'suggested_accounts_viewed.json':
                $this->dictdata["suggested_accounts_viewed_alltime"] = count($json_data['impressions_history_chaining_seen']) ?? 0;
                break;
            case 'quizzes.json':
                $this->dictdata["story_interactions_quizzes_current"] = count($json_data['story_activities_quizzes']) ?? 0;
                break;
            case 'polls.json':
                $this->dictdata["story_interactions_polls_current"] = count($json_data['story_activities_polls']) ?? 0;
                break;
            case 'story_likes.json':
                $this->dictdata["story_interactions_likes_current"] = count($json_data['story_activities_story_likes']) ?? 0;
                break;
            case 'liked_posts.json':
                $this->dictdata["post_likes_current"] = count($json_data['likes_media_likes']) ?? 0;
                break;
            case 'liked_comments.json':
                $this->dictdata["comment_likes_current"] = count($json_data['likes_comment_likes']) ?? 0;
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
            default:
                // Extra file that came through...
                break;
        }
    }

    // Calculate persona from personalized data
    private function calculate_persona(): array
    {

        switch (true) {
            case ($this->dictdata['post_likes_current'] > 1000 and $this->dictdata['comment_likes_current'] > 750 and $this->dictdata['story_interactions_likes_current'] > 100 and $this->dictdata['story_interactions_polls_current'] > 50 and $this->dictdata['story_interactions_quizzes_current'] > 50):
                $this->dictdata['persona'] = "The Enthusiast";
                $this->dictdata['persona_description'] = "Whatever is on Instagram, you love it!";
                break;
            case ($this->dictdata['posts_thisyear'] > 250 and $this->dictdata['post_likes_current'] > 800):
                $this->dictdata['persona'] = "Nolifer";
                $this->dictdata['persona_description'] = "Instagram loves you and you love Instagram.";
                break;
            case ($this->dictdata['post_likes_current'] > 1000 and $this->dictdata['comment_likes_current'] > 750 and $this->dictdata['story_interactions_likes_current'] > 100 and $this->dictdata['post_comments_alltime'] > 100):
                $this->dictdata['persona'] = "Superman";
                $this->dictdata['persona_description'] = "You are supportive and love to show what you like!";
                break;
            case ($this->dictdata['comment_likes_current'] > 50 and $this->dictdata['post_comments_alltime'] > 100):
                $this->dictdata['persona'] = "The Charmer";
                $this->dictdata['persona_description'] = "You write the longest and most charming comments!";
                break;
            case ($this->dictdata['suggested_accounts_viewed_alltime'] > 10000):
                $this->dictdata['persona'] = "Adventurer";
                $this->dictdata['persona_description'] = "You like to explore Instagram!";
                break;
            case ($this->dictdata['story_interactions_likes_current'] > 100 and $this->dictdata['story_interactions_polls_current'] > 50 and $this->dictdata['story_interactions_quizzes_current'] > 10):
                $this->dictdata['persona'] = "Lifestyle admirer";
                $this->dictdata['persona_description'] = "You like to keep an eye on your favourites!";
                break;
            case ($this->dictdata['post_likes_current'] < 25 and $this->dictdata['comment_likes_current'] < 10 and $this->dictdata['post_comments_alltime'] < 20):
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