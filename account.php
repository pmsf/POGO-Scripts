<?php
// Do not touch this!
require __DIR__ . '/Medoo.php';
use Medoo\Medoo;

if (!file_exists('config.php')) {
    echo "Config file does not exist. Create it first with 'cp example.config.php config.php'" . PHP_EOL;
    die();
}
require 'config.php';

try {
    $rdm_db = new Medoo([
        'database_type' => DBTYPE,
        'database_name' => DBNAME,
        'server' => DBHOST,
        'username' => DBUSER,
        'password' => DBPW,
        'charset' => CHARSET,
        'option' => array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
    ]);
} catch (Exception $e) {
    $error = 1;
    echo "\033[31mDatabase connection failed with error: " . PHP_EOL . "" . $e . "" . PHP_EOL;
    die();
}

$rdm_high_level = $rdm_db->query("
    SELECT
      SUM(
          (failed IS NULL AND first_warning_timestamp is NULL) OR
          (failed IN('GPR_RED_WARNING', 'suspended'))
      ) as total,
      SUM(
          (failed IS NULL AND first_warning_timestamp is NULL) OR
          (failed = 'GPR_RED_WARNING' AND warn_expire_timestamp <= UNIX_TIMESTAMP()) OR
          (failed = 'suspended' AND failed_timestamp <= UNIX_TIMESTAMP() - 2592000)
      ) as good,
      SUM(failed = 'GPR_RED_WARNING' AND warn_expire_timestamp > UNIX_TIMESTAMP()) as warning,
      SUM(failed = 'suspended' AND failed_timestamp > UNIX_TIMESTAMP() - 2592000) as tempban,
      SUM(failed = 'invalid_credentials') as invalid,
      SUM(failed IN('banned', 'GPR_BANNED')) as banned
    FROM account WHERE level >= 30"
)->fetch();

if ($rdm_high_level['good'] > 200) {
    $color = 3066993; // green
} else if ($rdm_high_level['good'] > 100) {
    $color = 16743680; // orange
} else {
    $color = 16711680; // red
}

$high_level_data = [
    "username" => "High level Accounts",
    "embeds" => [[
        "description" => "**Total: " . $rdm_high_level['total'] . "**\n\n:white_check_mark: Good: " . $rdm_high_level['good'] . "\n:warning: Red Warning: " . $rdm_high_level['warning'] . "\n:warning: Tempban: " . $rdm_high_level['tempban'] . "\n:no_entry_sign: Invalid Credentials: " . $rdm_high_level['invalid'] . "\n:no_entry_sign: Banned: " . $rdm_high_level['banned'] . "\n",
        "color" => $color,
        "thumbnail" => [
            "url" => "https://media.discordapp.net/attachments/600314172995141632/706441275309686884/worker.png"
        ]
    ]]
];
sendToWebhook(WEBHOOK, ($high_level_data));

$rdm_low_level = $rdm_db->query("
    SELECT
      SUM(
          (failed IS NULL AND first_warning_timestamp is NULL) OR
          (failed IN('GPR_RED_WARNING', 'suspended'))
      ) as total,
      SUM(
          (failed IS NULL AND first_warning_timestamp is NULL) OR
          (failed = 'GPR_RED_WARNING' AND warn_expire_timestamp <= UNIX_TIMESTAMP()) OR
          (failed = 'suspended' AND failed_timestamp <= UNIX_TIMESTAMP() - 2592000)
      ) as good,
      SUM(failed = 'GPR_RED_WARNING' AND warn_expire_timestamp > UNIX_TIMESTAMP()) as warning,
      SUM(failed = 'suspended' AND failed_timestamp > UNIX_TIMESTAMP() - 2592000) as tempban,
      SUM(failed = 'invalid_credentials') as invalid,
      SUM(failed IN('banned', 'GPR_BANNED')) as banned
    FROM account WHERE level <= 29"
)->fetch();

$low_level_data = [
    "username" => "Low Level Accounts", 
    "embeds" => [[
        "description" => "**Total: " . $rdm_low_level['total'] . "**\n\n:white_check_mark: Good: " . $rdm_low_level['good'] . "\n:warning: Red Warning: " . $rdm_low_level['warning'] . "\n:warning: Tempban: " . $rdm_low_level['tempban'] . "\n:no_entry_sign: Invalid Credentials: " . $rdm_low_level['invalid'] . "\n:no_entry_sign: Banned: " . $rdm_low_level['banned'] . "\n",
        "color" => 3066993,
        "thumbnail" => [
            "url" => "https://media.discordapp.net/attachments/600314172995141632/706441275309686884/worker.png"
        ]
    ]]
];
sendToWebhook(WEBHOOK, ($low_level_data));


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
