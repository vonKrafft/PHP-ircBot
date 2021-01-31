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
 * @version 1.2.2
 *
 */

############################## EDIT CONFIG HERE ##############################

define('ADM_NICKNAME', ''); // The administrator's nickname (leave empty to not use the admin restrictions)

define('IRC_SERVER', 'chat.freenode.net');      // Hostname of the IRC server
define('IRC_PORT', 6667);                       // Remote port of the IRC server
define('IRC_CHANNELS', array());                // List of IRC channel for auto-join

define('BOT_NICKNAME', 'Arthouur');             // Bot nickname
define('BOT_REALNAME', 'Le-Roi-Arthur');        // Bot name (nickname is used if empty)
define('BOT_VERSION', '1.2.2');                 // Version of the bot

############ DON'T EDIT CODE BELLOW IF YOU DON'T KNOW WHAT YOU DO ############

// So the bot doesn't stop.
set_time_limit(0);
error_reporting(E_ALL);

// Locales
setlocale(LC_TIME, "fr_FR");
date_default_timezone_set('Europe/Paris');

// Check-up before running the bot
if (php_sapi_name() != 'cli') {
    echo 'The bot must be run from the terminal!';
    exit(127);
} elseif (function_exists('posix_getuid') && posix_getuid() === 0) {
    echo 'Running the bot as root is not allowed.' . PHP_EOL;
    exit(128);
} elseif (version_compare(PHP_VERSION, '7', '<')) {
    echo 'The PHP version you are running (' . PHP_VERSION . ') is not sufficient for the bot. Sorry.';
    echo 'Please use PHP 7.0.0 or later.';
    exit(129);
}

// Settings
define('ROOT_DIR', __DIR__);

// Dependencies
require_once(ROOT_DIR . '/lib/classes/IRCColor.php');
require_once(ROOT_DIR . '/lib/classes/IRCMessage.php');
require_once(ROOT_DIR . '/lib/classes/IRCCommand.php');
require_once(ROOT_DIR . '/lib/classes/IRCClient.php');
require_once(ROOT_DIR . '/lib/classes/IRCBot.php');

/**
 * Arthouur
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
class Arthouur extends IRCBot
{
    /**
     * Construct item, opens the server connection, logs the bot in.
     *
     * @param mixed[]
     */
    function __construct($admin, $config = array())
    {
        parent::__construct($admin, $config);
    }

    /**
     * This is the workhorse function, grabs messages from the IRC server, 
     * processes them if needed, and returns a result
     */
    public function loop()
    {
        if ($this->_socket === NULL) {
            throw new ErrorException('Socket is not initialized!', 1);
        }

        $data = str_replace(array("\r\n", "\n", "\r"), '', fgets($this->_socket, 256));
        $message = new IRCMessage($data);

        switch ($message->command) {
            case self::PING:    $this->__on_ping($message);     break;
            case self::PRIVMSG: $this->__on_privmsg($message);  break;
            case self::INVITE:  $this->__on_invite($message);   break;
            case self::KICK:    $this->__on_kick($message);     break;
            case self::QUIT:    $this->__on_quit($message);     break;
            default:            $this->log($message);           break;
        }
    }

    /**
     * Process a private message.
     *
     * @param IRCMessage
     */
    private function __on_privmsg($message)
    {
        $this->log($message);

        // special command when user send "c'est pas faux"
        if (preg_match('/^c\'est pas faux/i', $message->get_content())) {
            $message->set_content('!perceval');
        }

        // Process command if necessary
        if (preg_match('/^!/', $message->get_content())) {
            $is_flood = $this->flood_guard($message->get_source(), $message->get_sender(), 5, 'Il commence à doucement me faire chier celui là aussi !');

            if ($is_flood === false) {
                $cmd = new IRCCommand($message, $this->history($message->get_source()));
                switch ($cmd->run()->response_command(self::PRIVMSG)) {
                    case self::PRIVMSG:
                        foreach ($cmd->get_result_array(10) as $result) {
                            $this->privmsg($cmd->reply_to($this->_joined_channels), $result);
                        }
                        break;
                    case self::TOPIC:
                        $this->topic($cmd->reply_to($this->_joined_channels), $cmd->get_result());
                        break;
                    case self::KICK:
                        $results = $cmd->get_result_array(2);
                        $victim = count($results) > 0 ? $results[0] : NULL;
                        $reason = count($results) > 1 ? $results[1] : NULL;
                        $this->kick($cmd->reply_to($this->_joined_channels), $victim, $reason);
                        break;
                    case self::NICK:
                        $this->nick($cmd->get_result());
                }
            }
        }
        
        // Reply to the the Freenode VERSION command
        elseif (strtoupper($message->get_content()) === 'VERSION') {
            $this->privmsg($message->get_source(), $this->version());
        }

        // Default behavior
        else {
            $this->history($message->get_source(), $message->get_content());
        }
    }

    /**
     * The bot has to reply to ping message.
     *
     * @param IRCMessage
     */
    private function __on_ping($message)
    {
        $this->pong(':' . $message->trailing);
    }

    /**
     * The bot automatically joins the channel on which it is invited 
     * and notifies the administrator by query IRC.
     *
     * @param IRCMessage
     */
    private function __on_invite($message)
    {
        $this->log($message);
        if ($message->get_sender() === $this->_admin or empty($this->_admin)) {
            $this->chan(self::JOIN, $message->get_content());
        }
        $this->notify($message);
    }

    /**
     * If the kicked one is the bot himself, he quits the channel and 
     * notifies the administrator by query IRC.
     *
     * @param IRCMessage
     */
    private function __on_kick($message)
    {
        $this->log($message);
        if ($message->get_content() === $this->get_config('nickname')) {
            $this->chan(self::QUIT, $message->get_source());
            $this->notify($message);
        }
    }

    /**
     * Auto-reconnect on timeout.
     *
     * @param IRCMessage
     */
    private function __on_quit($message)
    {
        if (strpos(strtolower($message->get_content()), 'timeout') !== false and $message->get_sender() === $this->get_config('nick')) {
            $this->login($this->get_config('nick'), $this->get_config('user'), $this->_joined_channels);
            $this->notify($message);
        }
    }
}

// Create the bot
$bot = new Arthouur(ADM_NICKNAME, array(
    'server'   => IRC_SERVER,
    'port'     => IRC_PORT,
    'channels' => IRC_CHANNELS,
    'nickname' => BOT_NICKNAME,
    'realname' => BOT_REALNAME,
    'version'  => BOT_VERSION,
));

// Loop
while (true) {
    $bot->loop();
}
