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
    printf('[DEBUG] cmd_confinement("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !confinement
 *
 * #Covid-19
 */

/*****************************************************************************/
/* >>> Premier confinement <<<                                               */
/*****************************************************************************/
// // Dates
// $date_begin = 1584399600; // 2020-03-17 00:00:00 GMT+01:00
// $date_end = $date_begin+86400*55;

// // Compute remaining time
// $lockdown_days = ceil((time() - $date_begin) / 86400);
// $remaining_days = ceil(($date_end - time()) / 86400);

// // Build message
// if ($remaining_days > 0) {
//     $stdout = sprintf('%s - Courage, il reste %s ... (https://www.gouvernement.fr/info-coronavirus)', 
//         IRCColor::color(sprintf('Confinement JOUR #%d', $lockdown_days), IRCColor::RED),
//         IRCColor::bold(sprintf('%d jour%s', $remaining_days, ($remaining_days > 1) ? 's' : ''))
//     );
// } else {
//     $stdout = sprintf('Le confinement est terminé, mais reste prudent ! (https://www.gouvernement.fr/info-coronavirus)');
// }
/*****************************************************************************/

/*****************************************************************************/
/* >>> Second confinement <<<                                               */
/*****************************************************************************/
// Dates
$date_begin = 1604012400; // 2020-10-30 00:00:00 GMT+01:00
$date_end = $date_begin+86400*32;

// Compute remaining time
$lockdown_days = ceil((time() - $date_begin) / 86400);
$remaining_days = ceil(($date_end - time()) / 86400);

// Build message
if ($remaining_days > 0 and $lockdown_days > 0) {
    $stdout = sprintf('%s - Courage, il reste %s ... (https://www.gouvernement.fr/info-coronavirus)', 
        IRCColor::color(sprintf('Confinement v2.0 JOUR #%d', $lockdown_days), IRCColor::RED),
        IRCColor::bold(sprintf('%d jour%s', $remaining_days, ($remaining_days > 1) ? 's' : ''))
    );
} elseif ($lockdown_days <= 0) {
    $stdout = sprintf('Le (re)confinement n\'est pas encore effectif, il le sera le %s à %s, mais reste prudent ! (https://www.gouvernement.fr/info-coronavirus)',
        date('d/m/Y', $date_begin), date('H\hi', $date_begin)
    );
} else {
    $stdout = 'Le confinement v2.0 est terminé ! En attendant le prochain, reste prudent ! (https://www.gouvernement.fr/info-coronavirus)';
}
/*****************************************************************************/

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
