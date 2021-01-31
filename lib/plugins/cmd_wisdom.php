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
    printf('[DEBUG] cmd_wisdom("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

// Slug function
$__slugify__ = function ($str, $divider = '-') {
    $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
    $b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
    $slug = utf8_decode(trim($str));           // UTF-8 
    $slug = strtr($slug, utf8_decode($a), $b); // Remove accents
    $slug = strtolower($slug);                 // Lowercase
    return trim(preg_replace('/[^a-z0-9]+/', $divider, $slug), $divider);
};

// Open wisdom file
$filename = 'var/wisdom/data_' . preg_replace('/[^a-z0-9]+/', '', strtolower($source)) . '.json';
$wisdom = file_exists($filename) ? json_decode(file_get_contents($filename), JSON_OBJECT_AS_ARRAY) : array();

if ($debug === true) {
    printf('[DEBUG] %d items found => %s' . PHP_EOL, count($wisdom), $filename);
}

// Parse input's arguments
preg_match('/(?<method>get|add|del|search) *(?<args>.*)/', $stdin, $matches);

// Execute method
if (count($matches) == 0) {

    if (strlen(trim($stdin)) == 0) {
        if (count($wisdom) > 0) {
            $id = array_rand($wisdom);
            $stdout  = preg_replace('/(<[^>]+>)/', IRCColor::color('$1', IRCColor::BLUE), $wisdom[$id]['quote']);
            $stdout .= IRCColor::color(' (#' . $id . ', ' . $wisdom[$id]['date'] . ')', IRCColor::GRAY);
        } else {
            $stdout = 'Le grand livre de la sagesse est introuvable !';
        }
    } else {
        $stdout = 'Ah mais non mon p\'tit pote, l\'argument n\'est pas valide ...';
    }

} elseif ($matches['method'] == 'get') {

    $args = intval(preg_replace('/^([0-9]*).*/', '$1', $matches['args']));
    if ($debug === true) printf('[DEBUG] GET #%d' . PHP_EOL, $args);

    if (isset($wisdom[$args])) {
        $stdout  = preg_replace('/(<[^>]+>)/', IRCColor::color('$1', IRCColor::BLUE), $wisdom[$args]['quote']);
        $stdout .= IRCColor::color(' (#' . $args . ', ' . $wisdom[$args]['date'] . ')', IRCColor::GRAY);
    } elseif ($args > 0) {
        $stdout = 'Oui ... Mais non ... Y\'a pas de citation #' . $args . ' mon p\'tit pote !';
    } else {
        $stdout = 'Ah mais non mon p\'tit pote, l\'argument doit être un nombre positif ...';
    }

} elseif ($matches['method'] == 'add') {

    $args = preg_replace('/^"(.+)"$/', '$1', trim($matches['args']));
    if ($debug === true) printf('[DEBUG] ADD %s' . PHP_EOL, $args);

    ksort($wisdom);
    $key = key(array_slice($wisdom, -1, 1, true)) + 1;
    $wisdom[$key] = array(
        'slug'   => $__slugify__($args),
        'date'   => date('Y-m-d'),
        'author' => $sender,
        'quote'  => $args,
    );
    $stdout = 'Bien dit mon p\'tit pote ! ' . IRCColor::color('(#' . $key . ')', IRCColor::GRAY);

} elseif ($matches['method'] == 'del') {

    $args = intval(preg_replace('/^([0-9]*).*/', '$1', $matches['args']));
    if ($debug === true) printf('[DEBUG] DEL #%d' . PHP_EOL, $args);

    if (isset($wisdom[$args])) {
        unset($wisdom[$args]);
        $stdout = 'Ainsi ces sages paroles seront oubliées ...';
    } elseif ($args > 0) {
        $stdout = 'Oui ... Mais non ... Y\'a pas de citation #' . $args . ' mon p\'tit pote !';
    } else {
        $stdout = 'Ah mais non mon p\'tit pote, l\'argument doit être un nombre positif ...';
    }

} elseif ($matches['method'] == 'search') {

    $args = $__slugify__($matches['args'], '|');
    if ($debug === true) printf('[DEBUG] SEARCH %s' . PHP_EOL, $args);

    $search_results = array();
    if (strlen($args) > 0) {
        foreach ($wisdom as $id => $content) {
            if (preg_match("/($args)/", $content['slug'])) {
                $search_results[] = $id;
            }
        }
        if (count($search_results) > 0) {
            $id = $search_results[array_rand($search_results)];
            $stdout  = preg_replace('/(<[^>]+> )/', IRCColor::color('$1', IRCColor::BLUE), $wisdom[$id]['quote']);
            $stdout .= IRCColor::color(' (#' . $id . ', ' . $wisdom[$id]['date'] . ')', IRCColor::GRAY);
        } else {
            $stdout = 'Oui ... Mais non ... J\'trouve pas de citation mon p\'tit pote !';
        }
    } else {
        $stdout = 'Ah mais non mon p\'tit pote, l\'argument doit être une chaine de caractères non nulle ...';
    }

}

// Save database
file_put_contents($filename, json_encode($wisdom));

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
