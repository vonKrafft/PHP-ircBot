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
    printf('[DEBUG] cmd_perceval("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !perceval
 *
 * Cette commande n'a pas vocation a être appelée telle quelle. Elle est
 * implicitement appelée lorsqu'un message débute par "C'est pas faux".
 */

// Search the longest word in the previous message
if (is_array($history) and ! empty($history)) {
     $content = array_pop($history);
     $words = explode(' ', preg_replace('/[^\wÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ]/', ' ', $content));
     $lengths = array_map('strlen', $words);
     $unknown = $words[array_search(max($lengths), $lengths)];
     if (strlen($unknown) > 0) {
         $stdout = sprintf('C\'est quoi qu\'vous n\'avez pas compris ? %s ?', $unknown);
     }
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)