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
    printf('[DEBUG] cmd_madame("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

// Initialize output
$stdout = null;

// The oldest post was publish on 2018-12-10
$max = intval((new DateTime())->diff(new DateTime("2018-12-10"))->format('%a')); 

// Sanitize input
$stdin = preg_replace('/[^0-9-top]/i', '', $stdin);
$nickname = preg_replace('/[^\w]/', '', strtolower($sender));
$channel = preg_replace('/[^\w]/', '', strtolower($source));

// Load the list of known URL
$path = ROOT_DIR . '/var/madames.json';
$madames = file_exists($path) ? json_decode(file_get_contents($path), true) : array();

// Anti-spam
$madame_regex = '/' . date('Y-m-d') . '.*' . $nickname . '.* PRIVMSG #*' . $channel . ' :!madame.*/i';
preg_match_all($madame_regex, file_get_contents(ROOT_DIR . '/var/log/arthouur_' . date('y\wW') . '.log'), $spam, PREG_SET_ORDER);
if ($debug === true) {
    printf('[DEBUG] [SPAM] Regex : %s' . PHP_EOL, $madame_regex);
    printf('[DEBUG] [SPAM] Number of requested !madame : %d' . PHP_EOL, count($spam));
}

// Select a picture
if (preg_match('/^top$/i', $stdin) and count($spam) <= 2) {

    if ($debug === true) {
        printf('[DEBUG] Request most viewed' . PHP_EOL);
    }

    // Retrieve the most viewed picture
    $selected_madame = array('counter' => 0, 'link' => '', 'date' => '1970-01-01');
    foreach ($madames as $date => $madame) {
        if (intval($madame['counter']) > intval($selected_madame['counter'])) {
            $selected_madame = $madame;
            $selected_madame['date'] = $date;
        }
    }

    // Build the IRC response
    $stdout = '[NSFW] ' . strip_tags($selected_madame['link']) . IRCColor::color(' (' . $selected_madame['date'] . ', vue ' . $selected_madame['counter'] . ' fois)', IRCColor::GRAY);

} elseif (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $stdin) and count($spam) <= 2) {

    // Check if a date is supplied
    $selected_date = $stdin;

    if ($debug === true) {
        printf('[DEBUG] Selected date : %s' . PHP_EOL, $selected_date);
    }

    // Get the image URL
    if (array_key_exists($selected_date, $madames)) {
        $madames[$selected_date]['counter'] += 2; # A requested picture is more valuable
        $selected_madame = $madames[$selected_date];
        
        // Write updated list
        ksort($madames);
        file_put_contents($path, json_encode($madames, JSON_PRETTY_PRINT));

        // Build the IRC response
        $stdout = '[NSFW] ' . strip_tags($selected_madame['link']) . IRCColor::color(' (' . $selected_date . ', vue ' . $selected_madame['counter'] . ' fois)', IRCColor::GRAY);
    } else {
        $stdout = 'Je n\'ai pas de lien pour le ' . strip_tags($selected_date);
    }

} elseif (count($spam) <= 2) {

    // Choose a random date
    do {
        $random_date = new DateTime();
        $random_date->sub(new DateInterval('P' . mt_rand(1, $max) . 'D'));
    } while (intval($random_date->format('N')) >= 6);

    $selected_date = $random_date->format('Y-m-d');

    if ($debug === true) {
        printf('[DEBUG] Selected random date : %s' . PHP_EOL, $selected_date);
    }

    // Get the image URL
    if (array_key_exists($selected_date, $madames)) {
        $madames[$selected_date]['counter']++;
        $selected_madame = $madames[$selected_date];
    } else {
        $response = file_get_contents('http://www.bonjourmadame.fr/' . $random_date->format('Y/m/d'));
        preg_match('/<img.*wp-image.*src="(https?:\/\/[^"]+\.(?:png|jpg|jpeg|gif)(?:[?&][^="]+=[^&"]*)*)[^"]*"[^>]*>/', $response, $matches);
        $url = count($matches) > 1 ? strip_tags($matches[1]) : null;

        if ($debug === true) {
            printf('[DEBUG] URL : %s' . PHP_EOL, $url);
        }

        // Sort the URL
        if ($url !== null) {
            $response = file_get_contents('https://tinyurl.com/create.php?url=' . urlencode($url));
            preg_match('/data-clipboard-text="(https:\/\/tinyurl.com\/\w{5,})"/', $response, $matches);
            $short_url = count($matches) > 1 ? strip_tags($matches[1]) : null;
        }

        // Add short URL if exist
        if ($short_url !== null) {
            $selected_madame = array( 'link' => $short_url, 'counter' => 1 );
            $madames[$selected_date] = $selected_madame;
        }
    }

    // Build the IRC response
    if ($selected_madame !== null) {
        $stdout = '[NSFW] ' . strip_tags($selected_madame['link']) . IRCColor::color(' (' . $selected_date . ', vue ' . $selected_madame['counter'] . ' fois)', IRCColor::GRAY);
        if ($debug === true) {
            printf('[DEBUG] Shorted URL : %s' . PHP_EOL, $selected_madame['link']);
        }
    }

    // Write updated list
    ksort($madames);
    file_put_contents($path, json_encode($madames, JSON_PRETTY_PRINT));

} elseif (count($spam) < 10) {
    $stdout = chr(0x01) . 'ACTION informe les autorités de la libido débordante de ' . $sender . chr(0x01);
} else {
    $action = 'KICK';
    $stdout = array($sender, 'Alors les femmes ! [...] Donnez-nous les femmes ! [...] Tous les femmes !!! [...] AAAAAAHH !!!');
}

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
