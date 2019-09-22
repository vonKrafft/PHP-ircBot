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
    printf('[DEBUG] cmd_ctf("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !ctf [solved|unsolved]
 * !ctf get ID
 * !ctf category NAME [solved|unsolved]
 *
 * Affiche les infos et les statistiques du CTF en cours ainsi que le ou les
 * défis correspondant à la recherche :
 *    - Le défi d'après l'index précisé
 *    - La liste des défis d'après le nom de la catégorie précisé 
 *
 * Les filtres "solved/unsolved" peuvent être appliqués sur les commandes "!ctf"
 * et "!ctf category NAME". Si aucun CTF n'existe, un message d'erreur sera
 * retourné.
 */

// Sanitize input
$stdin = trim($stdin);

if ($debug === true) {
    printf('[DEBUG] Input => %s' . PHP_EOL, $stdin);
}

// Initialize output
$stdout = array();

// Open CTF file
$filename = 'var/ctf/data_' . preg_replace('/[^a-z0-9]+/', '', strtolower($source)) . '.json';
$data = file_exists($filename) ? json_decode(file_get_contents($filename)) : NULL;

if ($debug === true) {
    printf('[DEBUG] Filename => %s' . PHP_EOL, $filename);
}

// Retrieve CTF data
if ($data !== NULL) {
    $begin = intval($data->ctf->begin) - time();
    $end = intval($data->ctf->end) - time();
    $remaining = ($begin > 0) ? $begin : $end;
    $countdown = array(
        'd' => floor($remaining / 86400),
        'h' => floor(($remaining % 86400) / 3600),
        'm' => floor((($remaining % 86400) % 3600) / 60),
        's' => floor((($remaining % 86400) % 3600) % 60),
    );
    $countdown_str = ($begin > 0) ? '(début dans ' : '(fin dans ';
    $countdown_str .= ($countdown['d'] > 0) ? sprintf('%02dj ', $countdown['d']) : '';
    $countdown_str .= sprintf('%02d:%02d:%02d)', $countdown['h'], $countdown['m'], $countdown['s']);
    $countdown_str = ($end < 0) ? IRCColor::color('(terminé)', IRCColor::RED) : $countdown_str;
	$stdout = array(IRCColor::bold('===== ' . $data->ctf->name . ' =====') . ' ' . $countdown_str . ' ' . $data->ctf->url . '');
}

// Challenge format function
$__format_challenge__ = function ($chall_id, $chall) {
    return sprintf('#%02d %6s %7s | %-32s %8s %s', 
        $chall_id + 1, 
        IRCColor::color(sprintf('[%4s]', substr(strtoupper($chall->category), 0, 4)), IRCColor::BROWN), 
        IRCColor::color($chall->points, IRCColor::BLUE), 
        substr($chall->name, 0, 32), 
        ($chall->solved !== false) ? IRCColor::color('*solved*', IRCColor::GREEN) : '', 
        ($chall->solved !== false) ? $chall->solved : implode(',', $chall->workers)
    );
};

// Get the challenge list
if (preg_match('/^get (?P<chall_id>[0-9]+)$/i', trim($stdin), $matches)) {
    $chall_id = intval($matches['chall_id']) - 1;
    if (0 <= $chall_id and $chall_id < count($data->challenges)) {
        $chall = $data->challenges[$chall_id];
        $stdout[] = $__format_challenge__($chall_id, $chall);
    }
} elseif (preg_match('/^category (?P<chall_cat>\w+)$/i', trim($stdin), $matches)) {
    $chall_cat = $matches['chall_cat'];
    foreach ($data->challenges as $chall_id => $chall) {
        if (preg_match("/^$chall_cat/i", $chall->category)) {
            $stdout[] = $__format_challenge__($chall_id, $chall);
        }
    }
} elseif (preg_match('/^category (?P<chall_cat>\w+) (?P<filter>unsolved|solved)$/i', trim($stdin), $matches)) {
    $chall_cat = $matches['chall_cat'];
    foreach ($data->challenges as $chall_id => $chall) {
        if (preg_match("/^$chall_cat/i", $chall->category) and ! ($chall->solved === false xor $matches['filter'] == 'unsolved')) {
            $stdout[] = $__format_challenge__($chall_id, $chall);
        }
    }
} elseif (preg_match('/^solved$/i', trim($stdin), $matches)) {
    foreach ($data->challenges as $chall_id => $chall) {
        if ($chall->solved !== false) {
            $stdout[] = $__format_challenge__($chall_id, $chall);
        }
    }
} elseif (preg_match('/^unsolved$/i', trim($stdin), $matches)) {
    foreach ($data->challenges as $chall_id => $chall) {
        if ($chall->solved === false) {
            $stdout[] = $__format_challenge__($chall_id, $chall);
        }
    }
} elseif (empty(trim($stdin))) {
    $pts_total = 0;
    $pts_solve = 0;
    $chall_solved = 0;
    foreach ($data->challenges as $chall) {
        $pts_total += $chall->points;
        $pts_solve += ($chall->solved !== false) ? $chall->points : 0;
        $chall_solved += ($chall->solved !== false) ? 1 : 0;
    }
    $stdout[] = sprintf('%d/%d challenge%s résolu%s | %d/%d points, %s (Pour voir un challenge : !ctf get [1-%d])',
        $chall_solved,
        count($data->challenges),
        ($chall_solved > 1) ? 's' : '',
        ($chall_solved > 1) ? 's' : '',
        $pts_solve,
        $pts_total,
        IRCColor::color(sprintf('%.2f%%', ($pts_total === 0) ? 0.0 : floatval(($pts_solve / $pts_total) * 100)), IRCColor::GREEN),
        count($data->challenges)
    );
}

// If there is only one row in stdout, it's mean the arguments are invalid
if ($data !== NULL and count($stdout) == 1) {
    $stdout[] = 'J\'comprend pas ... Essaye donc !ctf [get ID|category NAME] [solved|unsolved]';
}

// Limit the flood
$flood_limit = 7;
if (count($stdout) > ($flood_limit + 1)) {
    $hidden_results = count($stdout) - $flood_limit;
    $stdout = array_slice($stdout, 0, $flood_limit);
    $stdout[] = sprintf('Ainsi que %d défis supplémentaire. Essaye d\'affiner ta recherche :)', $hidden_results);
}

// If there is no CTF for the current channel
if ($data === NULL) {
	$stdout = 'Oui ... Mais non ... Y\'a pas de CTF mon p\'tit pote !';
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)