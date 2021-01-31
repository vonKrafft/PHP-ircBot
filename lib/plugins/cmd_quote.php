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
$debug = true;

// Initialize inputs
$stdin   = isset($stdin)   ? $stdin   : '';      // The message received by the bot, without the command keyword
$sender  = isset($sender)  ? $sender  : '';      // The sender's username
$source  = isset($source)  ? $source  : '';      // The source channel (the sender's username in case of private message)
$history = isset($history) ? $history : array(); // The message history


if ($debug === true) {
    printf('[DEBUG] cmd_quote("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !quote [name] [search]
 *
 * Affiche une citation de Kaamelott (par défaut, utilise les 5 derniers
 * messages pour la recherche). Si un nom de personnage est donné, la recherche
 * sera effectuée uniquement parmi ses dialogues. Si le motif de recherche est
 * vide, la citation sera choisie aléatoirement parmi tous les dialogues, ou
 * parmi les dialogues d'un personnage si celui-ci est précisé en argument.
 */

if ($debug === true) {
    printf('[DEBUG] INPUTS : stdin => %s' . PHP_EOL, $stdin);
}

// Use history if stdin is empty
if (empty($stdin) and is_array($history)) {
    $search = implode(' ', $history);
    if ($debug === true) {
        printf('[DEBUG] INPUTS : Use history => %s' . PHP_EOL, $search);
    }
} else {
    $search = preg_replace('/^ +$/', '.*', $stdin);
}

// Normalize
$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
$b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
$search = utf8_decode($search);                              // UTF-8 
$search = strtr($search, utf8_decode($a), $b);              // Remove accents
$search = strtolower($search);                              // Lowercase
$search = preg_replace('/[^\w\s\.*+"]+/', '', $search);    // Remove non-word or non-space characters
$search = trim($search);                                    // Trim spaces

if ($debug === true) {
    printf('[DEBUG] INPUTS : Normalized input => %s' . PHP_EOL, $search);
}

// Kaamelott characters
$characters = array(
    'angharad',  'perceval', 'appius',   'arthur', 'attila',        'blaise', 
    'bohort',    'breccan',  'burgonde', 'caius',  'calogrenant',   'capito', 
    'dagonet',   'damelac',  'demetra',  'elias',  'galessin',      'gauvain', 
    'guenievre', 'goustan',  'grudu',    'venec',  'jurisconsulte', 'kadoc', 
    'karadoc',   'lancelot', 'leodagan', 'loth',   'maitredarmes',  'lucius', 
    'meleagant', 'merlin',   'mevanwi',  'anna',   'repurgateur',   'rinel', 
    'roparzh',   'sefriane', 'spurius',  'seli',   'tavernier',     'urgan', 
    'guethenoc', 'ygerne',   'yvain'
);

// Select character (all characters if not provided)
$character_name = preg_replace('/^(\w+).*$/', '$1', trim($search));
if (in_array($character_name, $characters)) {
    $characters = array($character_name);
    $search = trim(preg_replace("/^$character_name/", '', $search));

    if ($debug === true) {
        printf('[DEBUG] INPUTS : Requested name => %s' . PHP_EOL, $character_name);
    }
}
shuffle($characters);

// Build a search array according to words length
$searches = array(
    3 => preg_replace('/\b(\w{1,2}|\w{4,})\b/', '', $search),
    4 => preg_replace('/\b(\w{1,3}|\w{5,})\b/', '', $search),
    5 => preg_replace('/\b(\w{1,4}|\w{6,})\b/', '', $search),
    6 => preg_replace('/\b(\w{1,5}|\w{7,})\b/', '', $search),
    7 => preg_replace('/\b\w{1,6}\b/', '', $search),
);

// Convert words into regular expressions
foreach ($searches as $key => $value) {
    $value = preg_replace('/[^\w" ]|" *"/', '', $value);    // Remove quoted blanks and special chars
    $value = preg_replace('/ *" */', '"', trim($value));        // Trim spaces around quotes
    $value = preg_replace('/ +/', '|', $value);                 // Replace space by regex divider
    $value = preg_replace('/"/', '\b', $value);                 // Replace quote by word boundary
    $value = str_replace('||', '|', $value);                    // Remove double dividers
    $value = str_replace('\b\b', '', $value);                   // Remove double word boundary
    $searches[$key] = utf8_encode($value);                      // Encode the regular expression

    if ($debug === true) {
        printf('[DEBUG] INPUTS : Regex search (%d-letter words) => %s' . PHP_EOL, $key, $value);
    }
}
$searches[8] = $search;

// Search
$len = 8;
$quotes = array();
while (array_key_exists($len, $searches) and empty($quotes)) {
    $current_search = $searches[$len--];
    if ( ! empty($current_search)) {
        foreach ($characters as $character_name) {
            $filename = ROOT_DIR . '/var/quotes/' . $character_name . '.json';
	        $content = file_exists($filename) ? file_get_contents($filename) : '[]';
        	foreach (json_decode($content) as $q) {
	            if (preg_match("/($current_search)/i", $q->slug)) {
                    $quotes[] = sprintf(
                        '%s "%s" %s',
                        IRCColor::color('<' . $q->author . '>', IRCColor::GREEN),
                        $q->content,
                        IRCColor::color('(' . $q->saison . ', ' . $q->episode . ')', IRCColor::GRAY)
                    );
    	        }
	        }
        }
        if ($debug === true) {
            printf('[DEBUG] RESULT : Search %d-letter words (%s) => %d' . PHP_EOL, $len+1, $current_search, count($quotes));
        }
    }
}

// Empty search
if (empty($quotes) and (empty($stdin) or count($characters) === 1)) {
    foreach ($characters as $character_name) {
        $filename = ROOT_DIR . '/var/quotes/' . $character_name . '.json';
        $content = file_exists($filename) ? file_get_contents($filename) : '[]';
        foreach (json_decode($content) as $q) {
            $quotes[] = sprintf(
                '%s "%s" %s',
                IRCColor::color('<' . $q->author . '>', IRCColor::GREEN),
                $q->content,
                IRCColor::color('(' . $q->saison . ', ' . $q->episode . ')', IRCColor::GRAY)
            );
        }
        if ( ! empty($quotes)) {
            break;
        }
    }
    if ($debug === true) {
        printf('[DEBUG] RESULT : Empty search => %d' . PHP_EOL, count($quotes));
    }
} elseif (empty($quotes)) {
    if (empty($search)) {
        $quotes = array(sprintf(
            'Hum ... Faut préciser ta recherche (%s %s)',
            IRCColor::color('!help', IRCColor::PINK),
            IRCColor::color('quote', IRCColor::ROYAL)
        ));
    } else {
        $quotes = array(sprintf(
            'Personne n\'a jamais dit "%s" mon p\'tit pote !', 
            $search
        ));
    }
}

// Random quote among the results
$stdout = empty($quotes) ? null : $quotes[array_rand($quotes)];

// Outputs
$stdout = empty($stdout) ? null : $stdout; // The message to send, if null the robot will remain silent
$sendto = empty($sendto) ? null : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? null : $action; // The desired command (PRIVMSG if null)
