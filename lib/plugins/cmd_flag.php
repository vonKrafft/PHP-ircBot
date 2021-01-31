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
    printf('[DEBUG] cmd_flag("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !flag ID
 *
 * Marquer un défi comme complété. Si le défi désiré est déjà résolu ou
 * n'existe pas, un message d'erreur sera retourné.
 */

// Messages
$answers = array(
	'CAPTURE_FLAG'   => 'GG %s ! Tu as gagné %d points pour "%s".',
	'RELEASE_FLAG'   => 'C\'est noté ! Le défi %02d n\'est plus marqué comme résolu.',
	'ALREADY_SOLVED' => 'Oui ... Mais non ... Le défi #%02d a déjà été résolu par %s mon p\'tit pote !',
	'OUT_OF_BOUND'   => 'Oui ... Mais non ... Y\'a pas de défi #%02d mon p\'tit pote !',
	'CTF_IS_OVER'    => 'Oui ... mais non ... Le CTF est terminé mon p\'tit pote !',
	'EMPTY_INDEX'    => 'Si tu ne me dis pas quel défi tu as terminé, je ne vais pas le deviner pour toi ... Essaye avec !flag [0-9]+',
);

// Sanitize input
$stdin = preg_replace('/[^0-9]+/', '', $stdin);
$chall_id = (strlen($stdin) > 0) ? intval($stdin) - 1 : null;

if ($debug === true) {
    printf('[DEBUG] Challenge ID => %s' . PHP_EOL, $chall_id);
}

// Initialize output
$stdout = null;

// Open CTF file
$filename = ROOT_DIR . '/var/ctf/data_' . preg_replace('/[^a-z0-9]+/', '', strtolower($source)) . '.json';
$data = file_exists($filename) ? json_decode(file_get_contents($filename)) : null;

if ($debug === true) {
    printf('[DEBUG] Filename => %s' . PHP_EOL, $filename);
}

// Flag a challenge
if ($data !== null) {
	if (intval($data->ctf->end) - time() < 0) {
		$stdout = sprintf($answers['CTF_IS_OVER']);
	} elseif ($chall_id === null) {
		$stdout = sprintf($answers['EMPTY_INDEX']);
	} elseif ($chall_id < 0 or $chall_id >= count($data->challenges)) {
		$stdout = sprintf($answers['OUT_OF_BOUND'], $chall_id+1);
	} else {
		$chall = $data->challenges[$chall_id];
		if ($chall->solved === false) {
			$chall->solved = $sender;
			$stdout = sprintf($answers['CAPTURE_FLAG'], $sender, $chall->points, $chall->name);
		} elseif ($chall->solved === $sender) {
			$chall->solved = false;
			$stdout = sprintf($answers['RELEASE_FLAG'], $chall_id+1);
		} else {
			$stdout = sprintf($answers['ALREADY_SOLVED'], $chall_id+1, $chall->solved);
		}
	}	
}

// Save CTF
if ($data !== null) {
	file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)