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
    printf('[DEBUG] cmd_help("%s", "%s", "%s", array("%s"))' . PHP_EOL, $stdin, $sender, $source, implode('", "', $history));
}

/**
 * !help [command]
 *
 * Affiche la liste des commandes disponibles. Si une commande est précisée en
 * argument, n'affiche que l'aide associée à cette commande.
 */

// Sanitize input
$stdin = strtolower(trim($stdin));

if ($debug === true) {
    printf('[DEBUG] Input => %s' . PHP_EOL, $stdin);
}

// Initialize output
$stdout = NULL;

// Help format function
$__format_help__ = function ($command, $args, $description = NULL, $padding = 40) {
    $helptext = IRCColor::color('!' . $command, IRCColor::PINK);
    $helptext .= empty(trim($args)) ? '' : ' ' . IRCColor::color($args, IRCColor::ROYAL);
    $helptext .= ($description !== NULL) ? ' - ' . trim($description) : '';
    return $helptext;
};

// Available help texts
$commands_list = array(
    'ctf' => array(
        $__format_help__('ctf', '', 'Affiche les infos et les stats du CTF (avancement, score, temps restant ...)'),
        $__format_help__('ctf', '[solved|unsolved]', 'Affiche la liste des défis, résolus ou non'),
        $__format_help__('ctf', 'get ID', 'Affiche le défi correspondant à l\'ID placé en argument'),
        $__format_help__('ctf', 'category NAME [solved|unsolved]', 'Affiche la liste des défis d\'une catégorie précise, résolus ou non'),
    ),
    'chall' => array(
        $__format_help__('chall', 'ID', 'Sélectionner un défi pour dire aux autres que l\'on travaille dessus'),
    ),
    'flag' => array(
        $__format_help__('flag', 'ID', 'Marquer un défi comme complété'),
    ),
    'quote' => array(
        $__format_help__('quote', '', 'Affiche une citation de Kaamelott en utilisant les 5 derniers messages comme contexte de recherche'),
        $__format_help__('quote', 'NAME', 'Affiche une citation aléatoire de Kaamelott parmi les dialogues du personnage précisé en argument'),
        $__format_help__('quote', '[NAME] WORD [WORD...]', 'Recherche une citation de Kaamelott en se basant sur les mots précisés en argument'),
    ),
    'apero' => array(
        $__format_help__('apero', '', 'Pour savoir si c\'est l\'heure de l\'apéro'),
    ),
    'weekend' => array(
        $__format_help__('weekend', '', 'Pour savoir si c\'est l\'heure du week-end'),
    ),
    'madame' => array(
        $__format_help__('madame', '', '[NSFW] Donne un lien aléatoire vers une image du site "Bonjour Madame"'),
    ),
    'cafe' => array(
        $__format_help__('cafe', '', 'Demande à Arthur de faire le café'),
    ),
);

// Help
if (empty($stdin)) {
    $stdout = array(
        IRCColor::bold('Commandes disponibles') . ' : ' . implode(', ', array_keys($commands_list)),
        'Essaye donc ' . $__format_help__('help', '[command]') . ' pour avoir plus de détails sur une commande spécifique.',
    );
} elseif (array_key_exists($stdin, $commands_list)) {
    $stdout = $commands_list[$stdin];
} else {
    $stdout = 'Aucune description n\'est disponible pour la commande "' . $stdin . '". Es-tu sûr de son existence ?';
}

// Outputs
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)