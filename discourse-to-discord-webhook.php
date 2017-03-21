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
$discourse_payload = json_decode($discourse_payload_raw, true)["post"];

// Disregard posts without content, such as lock and sticky post types.
if ($discourse_payload["post_type"] != 1) {
	return ', disregarded (post_type = ' . $discourse_payload["post_type"] . ')';
}

// Begin building the payload for the Discord webhook.
$discord_payload = array();

// Credit the post's author in the embed.
$discord_payload['embeds'][0]['author']['name'] = $discourse_payload["username"];
$discord_payload['embeds'][0]['author']['icon_url'] = $discourse_url_protocol . '://' . $discourse_url_domain . str_replace('{size}', '45', $discourse_payload["avatar_template"]);
$discord_payload['embeds'][0]['author']['url'] = $discourse_url_protocol . '://' . $discourse_url_domain . $discourse_path . 'users/' . $discourse_payload["username"] . $discourse_author_url_suffix;

// Build the body of the embed.
$discourse_post_url = $discourse_url_protocol . '://' . $discourse_url_domain . $discourse_path . 't/' . $discourse_payload["topic_slug"] . '/' . $discourse_payload["topic_id"] . ($discourse_payload["post_number"] > 1 ? '/' . $discourse_payload["post_number"] : '');
$discord_payload['embeds'][0]['type'] = 'rich';
$discord_payload['embeds'][0]['color'] = hexdec(ltrim($discord_embed_color));
$discord_payload['embeds'][0]['url'] = $discourse_post_url . $discourse_post_url_suffix_title;
$discord_payload['embeds'][0]['title'] = $discourse_payload["topic_slug"];
$discord_payload['embeds'][0]['description'] = "**@" . $discourse_payload["username"] . "** " . ($discourse_payload["post_number"] == 1 ? "created" : "replied to") . " this topic with:\n" . $discourse_payload["cooked"] . "\n**[[Read more]](" . $discourse_post_url . $discourse_post_url_suffix_descripiton . ")**";
$discord_payload['embeds'][0]['thumbnail']['url'] = $discord_embed_thumbnail_url;

// Add a footer to the embed.
$discord_payload['embeds'][0]['footer']['icon_url'] = $discord_embed_footer_icon_url;
$discord_payload['embeds'][0]['footer']['text'] = $discord_embed_footer_text;

// Possibly include a timestamp in the embed's footer.
if ($discord_embed_timestamp) {
	$discord_payload['embeds'][0]['timestamp'] = $discourse_payload["created_at"];
}

// Send the payload to the Discord webhook.
$curl = curl_init($discord_webhook_url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($discord_payload));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
return curl_exec($curl);
