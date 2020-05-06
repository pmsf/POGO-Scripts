<?php
define("DISCORDURL", "https://discordapp.com/api/webhooks/something");

$string = file_get_contents("https://pgorelease.nianticlabs.com/plfe/version");
$version = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);

$previous = file_get_contents('version.txt');

if (strcmp($previous, $version) !== 0) {
    $post = [
        "username" => "POGO-Version",
        "embeds" => [[
            "description" => "<@289764580014686209>\nPrevious version: " . $previous . "\nNew version: **" . $version . "**\n",
            "color" => 16711680
        ]]
    ];
    sendToWebhook(DISCORDURL, ($post));
    file_put_contents('version.txt', $version);
}

function sendToWebhook($webhookUrl, $webhook)
{
    $c = curl_init($webhookUrl);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-type: application/json', 'User-Agent: python-requests/2.18.4']);
    curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($webhook));
    curl_exec($c);
    curl_close($c);
}
