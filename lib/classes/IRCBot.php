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
    /**
     * Construct the IRC Bot
     */
    public function __construct(
        protected ?string $_admin = null,
        protected array $_chanlist = array(),
        protected array $_history = array(),
        protected array $_flood = array(),
        protected array $_config = array(),
        protected mixed $_socket = false,
    ) {
        parent::__construct($this->_config, $this->_socket);
    }

    /**
     * Check flood for the specified user.
     */
    protected function flood_guard(string $channel, string $nick, float $delay = 0.5, string|bool $kick_comment = false) : bool {
        if ($nick !== $this->_admin) {
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
        }
        return false;
    }

    /**
     * Append a new chan to the list.
     */
    protected function append_chan(string $channel) : array {
        if ( ! in_array($channel, $this->_chanlist)) {
            $this->_chanlist[] = $channel;
        }
        return $this->_chanlist;
    }

    /**
     * Delete a chan from the list.
     */
    protected function remove_chan(string $channel) : array {
        $this->_chanlist = array_diff($this->_chanlist, array($channel));
        return $this->_chanlist;
    }

    /**
     * Add a new message to history for the given source.
     */
    protected function add_history_event(string $source, string $message) : array {
        $this->_history = is_array($this->_history) ? $this->_history : array();
        $this->_history[$source][] = $message;
        $this->_history[$source] = array_slice($this->_history[$source], -5, 5);
        return $this->_history[$source];
    }

    /**
     * Get messages from history for the given source.
     */
    protected function get_history(string $source) : array {
        $this->_history = is_array($this->_history) ? $this->_history : array();
        return array_key_exists($source, $this->_history) ? $this->_history[$source] : array();
    }

    /**
     * Notify the Administrator with a private query.
     */
    protected function notify(string $message) : object {
        if ( ! empty($this->_admin) and ! empty($message)) {
            $this->privmsg($this->_admin, $message);
        }
        return $this;
    }
}
