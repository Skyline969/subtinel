<?php
	// Shut the script up by flipping this to false.
	$debug = true;
	
	// Whether to show URL previews in the post or not
	$show_url_previews = true;
	
	// The rate limit that Discord keeps track of before killing our connection. Default: 5
	$rate_limit = 5;

	// The URL of the subreddit to monitor. Make sure you point it to /new/.
	$reddit_url = "https://www.reddit.com/r/all/new/";
	
	// The URL for the Discord webhook.
	// See https://support.discordapp.com/hc/en-us/articles/228383668 for more info on creating webhooks.
	$discord_webhook = "";
	
	// The timezone to use when calculating when posts were created.
	$timezone = "UTC";
	
	// ##################################################
	// ###### DO NOT EDIT ANYTHING BELOW THIS LINE ######
	// ##################################################
	
	// Set the timezone for determining post dates.
	date_default_timezone_set($timezone);
	
	// First, let's get data from reddit
	$ch = curl_init($reddit_url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_VERBOSE, $debug);
	// Spoof the useragent, otherwise Reddit gets mad.
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
	
	// Go!
	$result = curl_exec($ch);
	
	// Let's parse the document we received
	$doc = new DOMDocument();
	$doc->loadXML(preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $result));
	
	// Store posts temporarily in an array so we can manipulate them later
	$post_ary = array();
	
	// We want to get the body element of the document. In there, there's all of the posts in a simple
	// JSON format. If we can capture that, we can parse it and easily get post information.
	foreach($doc->documentElement->childNodes as $node)
	{
		if ($node->nodeName == "body")
		{
			// We found the body, now look for the JSON string which will be on one line
			$body = explode("\n", $node->nodeValue);
			foreach($body as $line)
			{
				// This is what the line containing the JSON starts with.
				if (preg_match("/window\.___r/", $line))
				{
					// Got it! Now strip out the stuff before and after it that isn't JSON.
					preg_match("/(window.___r = )(.*$)/", $line, $matches);
					
					// Parse the JSON and load the posts into our array.
					$posts = json_decode(rtrim($matches[2], ";"));
					foreach($posts->posts->models as $post)
						$post_ary[] = $post;
				}
			}
		}
	}
	
	// Be good and disconnect.
	curl_close($ch);
	
	// Ok, fun part's over. Now we need to get the URL of our last post.
	$last_url_posted = trim(file_get_contents(dirname(__FILE__) . "/last_post.txt"));
	
	// Our array of posts we're actually going to post
	$posts_to_post = array();
	
	// Loop through the posts until we find the last post
	foreach($post_ary as $post)
	{
		if ($post->permalink == $last_url_posted)
			break;
		else
			$posts_to_post[] = $post;
	}
	
	// Only proceed if there's something to post
	if (count($posts_to_post) > 0)
	{
		// Now let's flip them so the most recent post will be on the bottom
		$posts_to_post = array_reverse($posts_to_post);
		
		// Keep track of how many posts we have as we cannot exceed the rate limit
		$discord_posts = array();
		foreach($posts_to_post as $post)
		{
			if (count($discord_posts) >= $rate_limit)
				break;
			else
			{
				// Generate the payload based on whether or not we should show URL previews
				if ($show_url_previews)
					$discord_posts[$post->permalink] = '{"content":' . json_encode($post->author . ": " . $post->title . " [" . date("Y-m-d H:i:s", $post->created / 1000) . "]\n" . $post->permalink) . '}';
				else
					$discord_posts[$post->permalink] = '{"content":' . json_encode($post->author . ": " . $post->title . " [" . date("Y-m-d H:i:s", $post->created / 1000) . "]\n<" . $post->permalink . ">") . '}';
			}
		}
		
		// Finally, let's post to Discord.
		$ch = curl_init($discord_webhook);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, $debug);
		curl_setopt($ch, CURLOPT_POST, true);
		
		$last_successful_post = "";
		foreach($discord_posts as $link => $post)
		{
			if ($debug)
				print $link . ": " . $post . "\n";
			
			// Set the payload and update the headers
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($post))
			);
			
			// Go!
			curl_exec($ch);

			// Store the URL of our last post, but only do so if we successfully posted.
			$result = curl_getinfo($ch);
			if ($result["http_code"] == "204")
			{
				$last_successful_post = $link;
				if ($debug)
					print "Posted OK.\n";
			}
		}
		
		// If we've posted something, save the last thing that was posted so we know where to pick up next time.
		if ($last_successful_post != "")
			file_put_contents(dirname(__FILE__) . "/last_post.txt", $last_successful_post);
		
		// Be good and close the connection
		curl_close($ch);
	}
	else if ($debug)
		print "No new posts.\n";
	
	if ($debug)
		print "Done.\n";
?>