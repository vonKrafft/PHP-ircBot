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
 */

// Prevent direct access
if ( ! defined('ROOT_DIR')) {
    die('Direct access not permitted!');
}

/**
 * IRCBot
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
abstract class IRCBot extends IRCClient
{
    // Short history of messages
    protected $_history = array();

    // Flood timestamps
    protected $_flood = array();

    // List of channels to which the bot is connected
    protected $_joined_channels = array();

    // Administrator's nickname
    protected $_admin = NULL;

    /**
     * Construct item, opens the server connection, logs the bot in
     * @param string
     * @param mixed[]
     */
    function __construct($admin, $config = array())
    {
        parent::__construct($config);
        // Init configurations properties
        $this->_history = array();
        $this->_flood = array();
        $this->_joined_channels = is_array($this->get_config('channels')) ? $this->get_config('channels') : array();
        $this->_admin = preg_replace('/[^a-zA-Z0-9_\[\]\\\\`^\{\}-]+/', '', $admin);
        // Log in
        $this->login($this->get_config('nickname'), $this->get_config('realname'));
        $this->join($this->get_config('channels'));
    }

    /**
     * Check flood for the specified user
     * @param string
     * @param string
     * @param float
     * @param string|bool
     * @return bool
     */
    protected function flood_guard($channel, $nick, $delay = 0.5, $kick_comment = false)
    {
        if ($nick === $this->_admin) {
            return false;
        }
        $key = $nick . '@' . $channel;
        $previous_timestamp = array_key_exists($key, $this->_flood) ? floatval($this->_flood[$key]) : 0.0000;
        $this->_flood[$key] = microtime(true);
        if (abs($this->_flood[$key] - $previous_timestamp) < floatval($delay)) {
            if (($nick !== $channel) and ($kick_comment !== false)) {
                $this->kick($channel, $nick, $kick_comment);
                $this->notify('KICK ' . $channel . ' ' . $nick . ' :' . $kick_comment);
            }
            return true;
        }
        return false;
    }

    /**
     * Add a new chan to the list and join it, or delete a chan from the list
     * @param string
     * @param string
     * @return mixed
     */
    protected function chan($action, $channel)
    {
        switch (strtoupper($action)) {
            case self::JOIN:
                if ( ! in_array($channel, $this->_joined_channels)) {
                    $this->_joined_channels[] = $channel;
                    $this->join($channel);
                }
                break;
            case self::QUIT:
                $this->_joined_channels = array_diff($this->_joined_channels, array($channel));
                break;
        }
        return $this->_joined_channels;
    }

    /**
     * Get messages from history for the given source, or add a new message 
     * to history for the given source.
     *
     * @param string
     * @return string[]|NULL
     */
    protected function history($source, $message = NULL)
    {
        $this->_history = is_array($this->_history) ? $this->_history : array();
        if ($message !== NULL) {
            // Add a new message to history
            if (array_key_exists($source, $this->_history)) {
                $this->_history[$source][] = $message;
            } else {
                $this->_history[$source] = array($message);
            }
            // Limit history size to 5 messages
            if (count($this->_history[$source]) > 5) {
                $this->_history[$source] = array_slice($this->_history[$source], 5);
            }
        }
        return array_key_exists($source, $this->_history) ? $this->_history[$source] : NULL;
    }

    /**
     * Notify admin
     * @param string
     * @return IRCBot
     */
    protected function notify($message) {
        if ( ! empty($this->_admin) and ! empty($message)) {
            $this->privmsg($this->_admin, $message);
        }
        return $this;
    }

    /**
     * Get version
     * @return string
     */
    protected function version() {
        $name = empty($this->get_config('realname')) ? 'PHP IRCBot' : $this->get_config('realname');
        $version = empty($this->get_config('version')) ? '' : ' v' . $this->get_config('version');
        $url = ' (https://github.com/vonKrafft/PHP-ircBot)';
        return $name . $version . $url;
    }
}
