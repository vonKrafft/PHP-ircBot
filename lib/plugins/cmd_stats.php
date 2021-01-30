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
	die('Direct access not premitted!');
}

// Debug variable: use `if ($debug === true) { ... }` to print any data in stdout
$debug = true;

// Initialize inputs
$stdin   = isset($stdin)   ? $stdin   : '';      // The message received by the bot, without the command keyword
$sender  = isset($sender)  ? $sender  : '';      // The sender's username
$source  = isset($source)  ? $source  : '';      // The source channel (the sender's username in case of private message)
$history = isset($history) ? $history : array(); // The message history

if ($debug === true) {
    printf('[DEBUG] cmd_stats("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

// Commands
$commands = array('apero', 'cafe', 'madame', 'quote', 'tg', 'weekend', 'wisdom');

// Sanitize
$stdin = preg_replace('/[^a-zA-Z0-9_\[\]\\\\`^\{\}-]/i', '', $stdin);
$sender = preg_replace('/[^\w]/', '', strtolower($sender));
$channel = preg_replace('/[^\w]/', '', strtolower($source));
$username = strlen($stdin) > 0 ? $stdin : $sender;

// Retrieve stats
if ($sender !== $channel) {
    $cmd_regex = '/^\[20.*<<< :.*' . $username . '.*PRIVMSG #*' . $channel . ' :![^\w]*(?P<command>' . implode('|', $commands) . ')/im';
    $kick_regex = '/>>> KICK #*' . $channel . ' ' . $username . '/im';
    $stats = array();
    $kick = 0;
    foreach (scandir(ROOT_DIR . '/var/log') as $logfile) {
        // Scan only log files for the current bot
        if ( ! preg_match('/^arthouur_([0-9]{2})w([0-9]{2})\.log$/', $logfile)) continue;
        // Parse the file
        preg_match_all($cmd_regex, file_get_contents(ROOT_DIR . "/var/log/$logfile"), $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ( ! array_key_exists($match['command'], $stats)) $stats[$match['command']] = 0;
            $stats[$match['command']]++;
        }
        // Kick stats
        preg_match_all($kick_regex, file_get_contents(ROOT_DIR . "/var/log/$logfile"), $matches, PREG_SET_ORDER);
        $kick += count($matches);
    }
    $stdout = 'Statistiques pour ' . $username . ' : ';
    foreach($stats as $cmd => $counter) $stdout .= IRCColor::color('!' . $cmd, IRCColor::ROYAL) . ' (' . IRCColor::color($counter, IRCColor::LIME) . ') ';
    $stdout .= (intval($kick) > 0) ? IRCColor::color('KICK', IRCColor::RED) . ' (' . IRCColor::color($kick, IRCColor::LIME) . ')' : '';
} else {
    $stdout = NULL;
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)

