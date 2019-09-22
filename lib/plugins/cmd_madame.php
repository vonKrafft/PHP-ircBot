<?php
/**
 * Copyright (c) 2019 vonKrafft <contact@vonkrafft.fr>
 * 
 * This file is part of PHP-ircBot (Awesome PHP Bot for IRC)
 * Source code available on https://github.com/vonKrafft/PHP-ircBot
 * 
 * This file may be used under the terms of the GNU General Public License
 * version 3.0 as published by the Free Software Foundation and appearing in
 * the file LICENSE included in the packaging of this file. Please review the
 * following information to ensure the GNU General Public License version 3.0
 * requirements will be met: http://www.gnu.org/copyleft/gpl.html.
 * 
 * This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING THE
 * WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @author vonKrafft <contact@vonkrafft.fr>
 * @see ./skeleton.php
 */

// Prevent direct access
if ( ! defined('ROOT_DIR')) {
    die('Direct access not permitted!');
}

// Debug variable: use `if ($debug === true) { ... }` to print any data in stdout
$debug = false;

// Initialize inputs
$stdin   = isset($stdin)   ? $stdin   : '';      // The message received by the bot, without the command keyword
$sender  = isset($sender)  ? $sender  : '';      // The sender's username
$source  = isset($source)  ? $source  : '';      // The source channel (the sender's username in case of private message)
$history = isset($history) ? $history : array(); // The message history

if ($debug === true) {
    printf('[DEBUG] cmd_madame("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

// Initialize output
$stdout = NULL;

// The oldest post was publish on 2018-12-10
$max = intval((new DateTime())->diff(new DateTime("2018-12-10"))->format('%a')); 

// Choose a random date
do {
    $random_date = new DateTime();
    $random_date->sub(new DateInterval('P' . mt_rand(1, $max) . 'D'));
} while (intval($random_date->format('N')) >= 6);

if ($debug === true) {
    printf('[DEBUG] Selected date : %s' . PHP_EOL, $random_date->format('Y-m-d'));
}

// Get the image URL
$response = file_get_contents('http://www.bonjourmadame.fr/' . $random_date->format('Y/m/d'));
preg_match('/<img.*wp-image.*src="(https?:\/\/[^"]+\.(?:png|jpg|jpeg|gif))[^"]*"[^>]*>/', $response, $matches);
$url = count($matches) > 1 ? strip_tags($matches[1]) : NULL;

if ($debug === true) {
    printf('[DEBUG] URL : %s' . PHP_EOL, $url);
}

// Sort the URL
if ($url !== NULL) {
    $response = file_get_contents('https://tinyurl.com/create.php?url=' . urlencode($url));
    preg_match('/data-clipboard-text="(https:\/\/tinyurl.com\/\w{5,})"/', $response, $matches);
    $stdout = count($matches) > 1 ? '[NSFW] ' . strip_tags($matches[1]) : NULL;

    if ($debug === true) {
        printf('[DEBUG] Shorted URL : %s' . PHP_EOL, $stdout);
    }
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)