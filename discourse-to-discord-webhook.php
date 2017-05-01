<?php

// Include the config.
require_once(dirname(__FILE__) . '/config.php');

// Immediately verify the authenticity of the request.
if (array_key_exists('HTTP_X_DISCOURSE_EVENT_SIGNATURE', $_SERVER)) {
    $discourse_payload_raw = file_get_contents('php://input');
    $discourse_payload_sha256 = substr($_SERVER['HTTP_X_DISCOURSE_EVENT_SIGNATURE'], 7);
    
    // Verify that the request was sent from an authorized webhook.
    if (hash_hmac('sha256', $discourse_payload_raw, $discourse_payload_secret) == $discourse_payload_sha256) {
        echo 'received';
    }
    else {
        die('authentication failed');
    }
}
else {
    die('access denied');
}

// Process only "post_created" events.
if ((array_key_exists('HTTP_X_DISCOURSE_EVENT', $_SERVER) && $_SERVER['HTTP_X_DISCOURSE_EVENT'] == 'post_created')) {
	echo ', processing';
}
else {
	die(', did not receive a "post_created" webhook event');
}

// Prepare the payload for use in the PHP script.
$discourse_payload = json_decode($discourse_payload_raw);

// Disregard posts without content, such as lock and sticky post types.
$discourse_post = $discourse_payload->post;
if ($discourse_post->post_type != 1) {
	return ', disregarded (post_type = ' . $discourse_post->post_type . ')';
}

// Begin building the payload for the Discord webhook.
$discord_payload = array();

// Credit the post's author in the embed.
$discord_payload['embeds'][0]['author']['name'] = $discourse_post->username;
$discord_payload['embeds'][0]['author']['icon_url'] = $discourse_url_protocol . '://' . $discourse_url_domain . str_replace('{size}', '45', $discourse_post->avatar_template);
$discord_payload['embeds'][0]['author']['url'] = $discourse_url_protocol . '://' . $discourse_url_domain . $discourse_path . 'users/' . $discourse_post->username . $discourse_author_url_suffix;

// Build the body of the embed.
$discourse_topic = $discourse_payload->topic;
$discourse_post_url = $discourse_url_protocol . '://' . $discourse_url_domain . $discourse_path . 't/' . $discourse_post->topic_slug . '/' . $discourse_post->topic_id . ($discourse_post->post_number > 1 ? '/' . $discourse_post->post_number : '');
$discord_payload['embeds'][0]['type'] = 'rich';
$discord_payload['embeds'][0]['color'] = hexdec(ltrim($discord_embed_color));
$discord_payload['embeds'][0]['url'] = $discourse_post_url . $discourse_post_url_suffix_title;
$discord_payload['embeds'][0]['title'] = $discourse_post->topic_title;
$discord_payload['embeds'][0]['title'] = 'New post';
$discord_payload['embeds'][0]['description'] = "**@" . $discourse_post->username . "** " . ($discourse_post->post_number == 1 ? "created" : "replied to") . " this topic.\n\n**[[Read more]](" . $discourse_post_url . $discourse_post_url_suffix_descripiton . ")**";
$discord_payload['embeds'][0]['thumbnail']['url'] = $discord_embed_thumbnail_url;

// Add a footer to the embed.
$discord_payload['embeds'][0]['footer']['icon_url'] = $discord_embed_footer_icon_url;
$discord_payload['embeds'][0]['footer']['text'] = $discord_embed_footer_text;

// Possibly include a timestamp in the embed's footer.
if ($discord_embed_timestamp) {
	$discord_payload['embeds'][0]['timestamp'] = $discourse_post->created_at;
}

// Send the payload to the Discord webhook.
$curl = curl_init($discord_webhook_url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($discord_payload));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
return curl_exec($curl);
