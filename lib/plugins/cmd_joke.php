<?php
/**
 * Copyright (c) 2021 vonKrafft <contact@vonkrafft.fr>
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
    printf('[DEBUG] skeleton("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !joke [ID|CATEGORY]
 * 
 * Une blague, en anglais, depuis le site https://v2.jokeapi.dev/
 */

// Initialize output
$stdout = null;

// Parse user's input
$category = preg_match('/^(Programming|Miscellaneous|Dark|Pun|Spooky|Christmas)+$/i', $stdin) ? $stdin : 'any';
$id = preg_match('/^[0-9]+$/i', $stdin) ? '?idRange=' . $stdin : '';

// Build URL
$url = 'https://v2.jokeapi.dev/joke/' . $category . $id;
if ($debug === true) {
    printf('[DEBUG] Requested URL: %s' . PHP_EOL, $url);
}

// Get the joke
$answer = json_decode(file_get_contents($url));

// Process the answer
if ($answer->type === 'single') {
    $stdout = sprintf('%s "' . $answer->joke . '" %s',
        IRCColor::color('<' . $answer->category . '>', IRCColor::LIME),
        IRCColor::color('(#' . $answer->id . ')', IRCColor::GRAY)
    );
} elseif ($answer->type === 'twopart') {
    $stdout = sprintf('%s "' . $answer->setup . '" [...] "' . $answer->delivery . '" %s',
        IRCColor::color('<' . $answer->category . '>', IRCColor::LIME),
        IRCColor::color('(#' . $answer->id . ')', IRCColor::GRAY)
    );
}

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
