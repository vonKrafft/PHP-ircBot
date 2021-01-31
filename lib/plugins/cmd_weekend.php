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
$debug = true;

// Initialize inputs
$stdin   = isset($stdin)   ? $stdin   : '';      // The message received by the bot, without the command keyword
$sender  = isset($sender)  ? $sender  : '';      // The sender's username
$source  = isset($source)  ? $source  : '';      // The source channel (the sender's username in case of private message)
$history = isset($history) ? $history : array(); // The message history

if ($debug === true) {
    printf('[DEBUG] cmd_weekend("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !weekend
 *
 * Pour savoir si c'est l'heure du week-end.
 * Utilise le site Web https://estcequecestbientotleweekend.fr/
 */

// Query the website and parse the HTML response
if (intval(date('w')) === 3) {
    $stdout = '[SFW] C\'est mercredi ! https://i.imgur.com/IkSoidc.png :)';
} else {
    $response = file_get_contents('https://estcequecestbientotleweekend.fr/');

    if ($debug === true) {
        printf('[DEBUG] $response = %s' . PHP_EOL, strstr($response, "\n", true));
    }

    preg_match_all('/<p class="msg">([^<]*)<\/p>/', $response, $matches);
    // $matches = array( 
    //     0 => array('json'),
    //     1 => array(json_decode($response, true)['text'])
    // );

    if ($debug === true) {
        printf('[DEBUG] $matches = %s' . PHP_EOL, var_export($matches, true));
    }

    $msg = count($matches[1]) > 0 ? implode('', $matches[1]) : '';
    $msg = trim(html_entity_decode($msg));
    $msg = str_replace('&#039;', '\'', $msg);
    $msg = preg_replace('/\s\s+/', ' ', $msg);

    $stdout = strlen($msg) > 0 ? $msg : null;
}

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
