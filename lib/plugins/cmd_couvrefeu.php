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
    printf('[DEBUG] cmd_couvrefeu("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !couvrefeu
 *
 * #Covid-19 returns
 */

// Dates
$date_begin = 1602885600; // 2020-10-17 00:00:00 GMT+02:00
$date_end = $date_begin+86400*28;

// Compute remaining time
$lockdown_days = ceil((time() - $date_begin) / 86400);
$remaining_days = ceil(($date_end - time()) / 86400);

// Build message
if ($remaining_days > 0 and $lockdown_days > 0) {
    $stdout = sprintf('%s - Courage, il reste %s ... (https://www.gouvernement.fr/info-coronavirus)', 
        IRCColor::color(sprintf('Couvre-feu JOUR #%d', $lockdown_days), IRCColor::RED),
        IRCColor::bold(sprintf('%d jour%s', $remaining_days, ($remaining_days > 1) ? 's' : ''))
    );
    if (6 > idate('H') or idate('H') >= 21) {
        $stdout = array($stdout, sprintf('Attention, il est %s, j\'espère que tu es chez toi !', date('H\hi')));
    }
} elseif ($lockdown_days <= 0) {
    $stdout = sprintf('Le couvre-feu n\'est pas encore imposé, il le sera le %s à %s, mais reste prudent ! (https://www.gouvernement.fr/info-coronavirus)',
        date('d/m/Y', $date_begin), date('H\hi', $date_begin)
    );
} else {
    $stdout = 'Le couvre-feu est terminé, mais reste prudent ! (https://www.gouvernement.fr/info-coronavirus)';
}

// Update
$stdout = sprintf('Mais on s\'en carre du couvre-feu, %s ! (https://www.gouvernement.fr/info-coronavirus)',
    IRCColor::color('C\'EST LE (RE)CONFINEMENT', IRCColor::RED)
);

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
