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
    printf('[DEBUG] cmd_tg("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

// Sanitize input
$victim = preg_replace('/[^a-zA-Z0-9_\[\]\\\\`^\{\}-]+/', '', $stdin);

if ($debug === true) {
    printf('[DEBUG] Victim => %s' . PHP_EOL, $victim);
}

// Immunity
$immunity = array(strtolower(BOT_NICKNAME));

if ($debug === true) {
    printf('[DEBUG] Immunity => %s' . PHP_EOL, implode(', ', $immunity));
}

// Kick the requested nickname
if(($sender !== $source) and ! in_array(strtolower($victim), $immunity)) {
    $pattern = '/^\[' . date('Y-m-d') . ' (' . date('H:i') . '|';
    $pattern .= (new DateTime())->sub(new DateInterval('PT59S'))->format('H:i') . '|';
    $pattern .= (new DateTime())->sub(new DateInterval('PT119S'))->format('H:i') . ')';
    $pattern .= ':[0-9]{2}\] <<< .*' . $victim . '.* PRIVMSG ' . $source . ' :/i';

    $flood_level = count(preg_grep($pattern, file(ROOT_DIR . '/var/log/'. strtolower(BOT_NICKNAME) . '_' . date('y\wW') . '.log')));

    if ($debug === true) {
        printf('[DEBUG] Flood level: %d (%s)' . PHP_EOL, $flood_level, $pattern);
    }

    // Authorize KICK only if the victim flood the channel
    if ($flood_level > 6) {
        $action = 'KICK';
        $stdout = array($victim, 'Attention, le ' . $sender . ' des collines !');
    } elseif ( ! in_array(strtolower($sender), $immunity)) {
        $action = 'KICK';
        $stdout = array($sender, 'Non mais... Que vous soyez débile c\'est une chose, mais là y a de la mauvaise volonté quand même');
    }
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)
