# Subtinel
A simple script to obtain new posts on a specific subreddit and post them in a specific Discord channel. It supports two methods of posting - either a rate-limited or a character-limited variant. Both can be customized based on the limits on your Discord server.

The script writes to a single file called last_post.txt which contains the URL of the last post it successfully shared. All new posts up to the one contained within this file will be posted, up to the limit defined within the script. If the script cannot post everything it finds, it will post up to the limit and then remember where it left off so it can continue the next time it runs. By default, if this file does not exist it will retrieve the last 25 posts and work on posting them before monitoring for new ones.

Please note that if you want to monitor multiple subreddits, you will need multiple copies of the script. However, in the current implementation the script will need to be placed in multiple subdirectories since otherwise it will be overwriting last_post.txt with a URL that will not exist in the other subreddit you wish to monitor. This will cause the script to keep posting the same five posts from each subreddit every time it is run.

## Requirements
* PHP, tested and verified on both 5.3 and 7.0
* php-curl
* php-dom

## Configuration
Configuration is minimal, but you will need to obtain a webhook for your Discord server. Please visit https://support.discordapp.com/hc/en-us/articles/228383668 for more info on how to create a webhook for subtinel.

After you have your webhook, all you need to configure is the following (located at the top of either script):

* show_url_previews: Whether URL previews should be shown in subtinel's posts. It looks much nicer in the rate-limited variant as the previews will be under each individual post. In the character-limited variant, all posts will be made and then all previews will show up underneath.
* rate_limit/char_limit: The post or character limit for each time the script runs. These are defaulted to the standard values Discord provides in their documentation. Adjust as necessary.
* reddit_url: The URL of the subreddit to monitor. Make sure you specify /new/!
* discord_webhook: The URL to the Discord webhook.
* timezone: Used to calculating when posts were created. Default is UTC, but adjust to your liking, for instance if you have a local server and all posts would be relevant to your local timezone.

## Installation
Installation is as simple as installing the dependencies, configuring the script, and then running it. The script could be run via a cron job to automatically retrieve new information.

## Donations
If you like Subtinel, feel free to buy me a beer. Donations are never mandatory or even expected but are always appreciated.

<a href="https://www.paypal.me/skyline969"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="Donate"/></a>
