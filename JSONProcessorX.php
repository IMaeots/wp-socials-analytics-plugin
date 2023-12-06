<?php

class JSONProcessorX
{
    private array $dictdata;
    private array $filePaths;
    private array $files_to_match = ['account.js', 'like.js'];


    public function __construct(array $filePaths)
    {
        // Take in an array of js files.
        $this->filePaths = $filePaths;
        $this->dictdata = array('demo' => 0);
    }

    /**
     * Get X Data as a dictionary. You have to initialize the class with json filepaths.
     */
    public function getXDataAsDict(): array
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
                    // Get the content of the JavaScript file
                    $js_content = file_get_contents($filePath);

                    // Process the matched JS file.
                    $pattern = '/\[.*?\]/s';

                    if (preg_match($pattern, $js_content, $matches) === 1) {
                        // Process $matched_content further if needed
                        // Extracted JSON-like content found, decode it as JSON
                        $json_data = json_decode($matches[0], true);

                        $this->processDataBasedOnFileName($file_name, $json_data);
                    } else {
                        // No match found
                        error_log("Pattern did not match the content.");
                    }
                }
            }
        } // If there are not enough of correct files then get the demo data.
        else {
            $this->dictdata = array(
                'demo' => 1,
                'name' => "John Doe",
                'tweets_liked' => 223
            );
        }

        return $this->calculate_persona();
    }

    /**
     * You must call getXDataAsDict() before calling getSlideshowTexts()
     */
    public function getSlideshowTexts(): bool|string
    {
        $nameIntro = "<span class='slideshow-larger-bolder'>{$this->dictdata['name']}</span>, right?<br>Let's take a look at your activity on X, shall we.";

        $tweetsInfo = "You have written <span class='slideshow-larger-bolder'>missing</span> tweets in X<br>and this year you liked <span class='slideshow-larger-bolder'>{$this->dictdata['tweets_liked']} tweets...</span>";

        $personaInfo = "Your X persona is <span class='slideshow-larger-bolder'>{$this->dictdata['persona']}</span><br>{$this->dictdata['persona_description']}";

        // CSS needs to have defined slideshow-x-larger-bolder, slideshow-larger-bolder, slideshow-x-larger, slideshow-larger.
        return json_encode(array(
            $nameIntro,
            $tweetsInfo,
            $personaInfo
        ));
    }

    /* Start of Private Functions. */
    // Process data according to filename
    private function processDataBasedOnFileName($file_name, $json_data): void
    {
        switch ($file_name) {
            case 'account.js':
                error_log(print_r($json_data, true));
                $this->dictdata['name'] = isset($json_data[0]['account']['accountDisplayName']) ? $json_data[0]['account']['accountDisplayName'] : "Unknown";
                break;
            case 'like.js':
                $this->dictdata['tweets_liked'] = count($json_data[0]) ?? 0;
            default:
                // Extra file that came through...
                break;
        }
    }

    // Calculate persona from personalized data
    private function calculate_persona(): array
    {   /*
        switch (true) {
            case ($this->dictdata['post_likes_current'] > 1000 and $this->dictdata['comment_likes_current'] > 1000 and $this->dictdata['story_interactions_likes_current'] > 100 and $this->dictdata['story_interactions_polls_current'] > 100 and $this->dictdata['story_interactions_quizzes_current'] > 100):
                $this->dictdata['persona'] = "The Enthusiast";
                $this->dictdata['persona_description'] = "Whatever is on Instagram, you love it!";
                break;
            case ($this->dictdata['posts_thisyear'] > 250 and $this->dictdata['post_likes_current'] > 800):
                $this->dictdata['persona'] = "Nolifer";
                $this->dictdata['persona_description'] = "Instagram loves you and you love Instagram.";
                break;
            case ($this->dictdata['post_likes_current'] > 1000 and $this->dictdata['comment_likes_current'] > 750 and $this->dictdata['story_interactions_likes_current'] > 200 and $this->dictdata['post_comments_alltime'] > 100):
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
        } */
        $this->dictdata['persona'] = "Chiller";
        $this->dictdata['persona_description'] = "You just like to chill!";

        return $this->dictdata;
    }
}