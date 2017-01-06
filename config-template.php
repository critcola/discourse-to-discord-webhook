<?php

/*
 * Discord configuration.
 */

// Discord webhook URL.
$discord_webhook_url = '';

// Customize the color of the left side of the embed with a hexidecimal color code.
$discord_embed_color = '#ee3940';

// Define the URL for the embed's thumbnail.
$discord_embed_thumbnail_url = '';

// Define an icon URL and text for the embed's footer.
$discord_embed_footer_icon_url = $discord_embed_thumbnail_url;
$discord_embed_footer_text = '';

// Optionally include the post's timestamp in the embed's footer.
$discord_embed_timestamp = false;


/*
 * Discourse configuration.
 */

// For security, configure the webhook with a secret.
$discourse_payload_secret = '';

// Discourse URL protocol (e.g., "http" or "https").
$discourse_url_protocol = 'https';

// Discourse domain (e.g., "critcola.com").
$discourse_url_domain = '';

// Discourse path (e.g., "/community/", if you use Discourse in a subdirectory). If you don't know what this is, don't change it.
$discourse_path = '/';


/*
 * Link tracking for analytics. Optionally suffix URLs in Discord embeds. (Example: "?utm_campaign=critcola")
 */

// Suffix the title URL.
$discourse_post_url_suffix_title = '';

// Suffix the descrpition URL.
$discourse_post_url_suffix_descripiton = '';

// Suffix the author's profile URL.
$discourse_author_url_suffix = '';
