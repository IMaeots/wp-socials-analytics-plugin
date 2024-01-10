<?php

class JSONProcessorX
{
    private array $dictdata;
    private array $filePaths;
    private array $files_to_match = ['manifest.js', 'tweets.js'];


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
        if ($this->filePaths !== NULL) {
            // Extract basenames from full paths
            $basenames = array_map('basename', $this->filePaths);

            // Find matching basenames
            $matching_basenames = array_intersect($basenames, $this->files_to_match) ?? [];

            // Get the full paths of matching basenames
            $matching_full_paths = array_intersect_key($this->filePaths, $matching_basenames) ?? [];
        }

        if (isset($matching_full_paths) and count($matching_full_paths) === 2) {
            foreach ($matching_full_paths as $filePath) {
                $file_name = basename($filePath);  // Extract the file name from the path.

                if ($file_name === 'tweets.js') {
                    // Make js into json.
                    $js_content = file_get_contents($filePath);
                    $pattern = '/\[.*?\]/s';
                    if (preg_match($pattern, $js_content, $matches) === 1) {
                        $json_data = json_decode($matches[0], true);
                    }
                } elseif ($file_name === 'manifest.js') {
                    // Make js into json.
                    $js_content = file_get_contents($filePath);
                    $pattern = '/window\.__THAR_CONFIG = ({.*})/s';
                    if (preg_match($pattern, $js_content, $matches) === 1) {
                        $json_data = json_decode($matches[1], true);

                        $this->dictdata['name'] = isset($json_data['userInfo']['displayName']) ? $json_data['userInfo']['displayName'] : "Unknown";

                        function getCountFromData($data, $key) {
                            return (isset($data['dataTypes'][$key]['files']) && is_array($data['dataTypes'][$key]['files'])) ?
                                array_sum(array_map(fn($item) => intval($item['count']), $data['dataTypes'][$key]['files'])) :
                                0;
                        }

                        $this->dictdata['following_count'] = getCountFromData($json_data, "following");
                        $this->dictdata['follower_count'] = getCountFromData($json_data, "follower");
                        $this->dictdata['tweets_liked'] = getCountFromData($json_data, "like");
                        $this->dictdata['tweets_posted'] = getCountFromData($json_data, "tweets");
                        $this->dictdata['ad_impressions_count'] = getCountFromData($json_data, "adImpressions");
                        $this->dictdata['ad_engagements_count'] = getCountFromData($json_data, "adEngagements");
                        $this->dictdata['suspensions_count'] = getCountFromData($json_data, "accountSuspension");
                        $this->dictdata['blocked_count'] = getCountFromData($json_data, "block");
                        $this->dictdata['community_notes_posted'] = getCountFromData($json_data, "communityNote");
                        $this->dictdata['community_tweets_posted'] = getCountFromData($json_data, "communityTweet");
                        $this->dictdata['direct_messages_count'] = getCountFromData($json_data, "directMessages");
                        $this->dictdata['direct_group_messages_count'] = getCountFromData($json_data, "directMessagesGroup");
                        $this->dictdata['lists_created_count'] = getCountFromData($json_data, "listsCreated");
                        $this->dictdata['lists_member_count'] = getCountFromData($json_data, "listsMember");
                        $this->dictdata['moment_count'] = getCountFromData($json_data, "moment");
                        $this->dictdata['broadcast_count'] = getCountFromData($json_data, "periscopeBroadcastMetadata");
                        $this->dictdata['broadcast_comments_count'] = getCountFromData($json_data, "periscopeCommentsMadeByUser");
                        $this->dictdata['search_count'] = getCountFromData($json_data, "savedSearch");
                    }
                }
            }
        } // If there are not enough of correct files then get the demo data.
        else {
            $this->dictdata = array(
                'demo' => 1,
                'name' => "John Doe",
                'following_count' => 1800,
                'follower_count' => 422,
                'tweets_liked' => 1100,
                'tweets_posted' => 99,
                'ad_impressions_count' => 92,
                'ad_engagements_count' => 21,
                'suspensions_count' => 0,
                'blocked_count' => 10,
                'community_notes_posted' => 3,
                'community_tweets_posted' => 21,
                'direct_messages_count' => 14,
                'direct_group_messages_count' => 4,
                'lists_created_count' => 1,
                'lists_member_count'=> 7,
                'moment_count' => 0,
                'broadcast_count' => 60,
                'broadcast_comments_count' => 144,
                'search_count' => 812
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

        $tweetsInfo = "You have written <span class='slideshow-larger-bolder'>{$this->dictdata['tweets_posted']}</span> tweets in X<br>and you have liked <span class='slideshow-larger-bolder'>{$this->dictdata['tweets_liked']} tweets!</span>";

        $searchInfo = "<span class='slideshow-larger-bolder'>{$this->dictdata['search_count']}</span><br>Do you recognize this number?<br>This is the amount of searches you've made!";

        if ($this->dictdata['follower_count'] > $this->dictdata['following_count']) {
            $followInfo = "Wow, you have more followers <span class='slideshow-bolder'>({$this->dictdata['follower_count']})</span> than you follow <span class='slideshow-bolder'>({$this->dictdata['following_count']})</span>";
        } elseif ($this->dictdata['follower_count'] < $this->dictdata['following_count']) {
            $num = $this->dictdata['following_count'] - $this->dictdata['follower_count'];
            $followInfo = "Great, you love to follow!<br> You follow <span class='slideshow-bolder'>{$num}</span> accounts more than you have follwers.";
        } else {
            $followInfo = "Nice, well balanced! You follow <span class='slideshow-bolder'>{$this->dictdata['follower_count']} accounts</span> - just as many as you have followers!";
        }

        $adInfo = "Hehe, <span class='slideshow-larger-bolder'>{$this->dictdata['ad_engagements_count']} ads</span> have gotten you engaged!</span>";

        $listsNbroadcasts = "You are a member in <span class='slideshow-larger-bolder'>{$this->dictdata['lists_member_count']}</span> lists<br>and you've watched <span class='slideshow-larger-bolder'>{$this->dictdata['broadcast_count']}</span> broadcasts from X...";

        $personaInfo = "Your X persona is <span class='slideshow-larger-bolder'>{$this->dictdata['persona']}</span><br>{$this->dictdata['persona_description']}";

        // CSS needs to have defined slideshow-x-larger-bolder, slideshow-larger-bolder, slideshow-x-larger, slideshow-larger.
        return json_encode(array(
            $nameIntro,
            $tweetsInfo,
            $searchInfo,
            $followInfo,
            $listsNbroadcasts,
            $adInfo,
            $personaInfo
        ));
    }

    /* Start of Private Functions. */
    // Calculate persona from personalized data
    private function calculate_persona(): array
    {
        switch (true) {
            case ($this->dictdata['tweets_posted'] > 5000 and $this->dictdata['tweets_liked'] > 10000):
                $this->dictdata['persona'] = "Nolifer";
                $this->dictdata['persona_description'] = "X is life, life is X!";
                break;
            case ($this->dictdata['tweets_posted'] > 1000 and $this->dictdata['tweets_liked'] > 2000):
                $this->dictdata['persona'] = "The Enthusiast";
                $this->dictdata['persona_description'] = "X seems to be like a friend of yours!";
                break;
            case ($this->dictdata['tweets_liked'] > 5000):
                $this->dictdata['persona'] = "Superman";
                $this->dictdata['persona_description'] = "You are supportive and honest!";
                break;
            case ($this->dictdata['tweets_liked'] > 5000):
                $this->dictdata['persona'] = "Superman";
                $this->dictdata['persona_description'] = "You are supportive and honest!";
                break;
            case ($this->dictdata['broadcast_comments_count'] > 250 and $this->dictdata['tweets_posted'] > 500):
                $this->dictdata['persona'] = "The Charmer";
                $this->dictdata['persona_description'] = "You like to smooth the path with comments!";
                break;
            case ($this->dictdata['following_count'] > 1000):
                $this->dictdata['persona'] = "Adventurer";
                $this->dictdata['persona_description'] = "You like to explore X!";
                break;
            case ($this->dictdata['following_count'] > 100 and $this->dictdata['tweets_liked'] > 1000):
                $this->dictdata['persona'] = "Lifestyle admirer";
                $this->dictdata['persona_description'] = "You like to keep an eye on your favourites!";
                break;
            case ($this->dictdata['following_count'] < 25 and $this->dictdata['tweets_liked'] < 100 and $this->dictdata['tweets_posted'] < 20):
                $this->dictdata['persona'] = "Lurker";
                $this->dictdata['persona_description'] = "You like to scroll without interacting!";
                break;
            default:
                $this->dictdata['persona'] = "Chill Guru";
                $this->dictdata['persona_description'] = "You just scroll for fun and interest!";
                break;
        }

        return $this->dictdata;
    }
}