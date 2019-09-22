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
    printf('[DEBUG] cmd_cafe("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !cafe
 *
 * Demande à Arthur de faire le café ... Mais Arthur ne fait pas le café.
 */

// Random (Arthur does not make coffee)
$cafe = array(
    'J\'suis roi de Bretagne, je ne vais pas t\'apporter un café ! Demande donc à Yvain ...',
    'Y a pas à dire, dès qu\'il y a du café, le repas est tout de suite plus chaleureux !',
    'Mais allez vous l\'chercher vot\' café ... Et sortez-vous les doigts du cul !!!',
    'J\'suis chef de guerre moi, j\'suis pas là pour agiter des drapeaux et faire du café ...',
    'J\'vais vous dire : même si le pays était à feu et à sang, il serait hors de question que j\'vous fasse un café.',
    'Le Saint-Suaire ? Vous avez foutu du café sur le Saint-Suaire ?"',
    'Je suis le Roi Arthur, je ne fais pas le café. Jamais je perds courage. Je suis un exemple pour les enfants.',
);
$stdout = $cafe[mt_rand(0, count($cafe) - 1)];

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)