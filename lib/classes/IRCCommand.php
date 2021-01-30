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
 * Parse and execute the commands sent to the IRC bot.
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
class IRCCommand
{
    private $source = NULL;
    private $sender = NULL;
    private $history = array();
    private $bin = NULL;
    private $args = NULL;
    private $result = NULL;
    private $sendto = NULL;
    private $action = NULL;
    
    /**
     * Construct item, parse message
     * @param IRCMessage
     * @param string[]
     * @param string
     */
    function __construct($message = '', $history = array())
    {
        $this->source = $message->get_source();
        $this->sender = $message->get_sender();
        $this->history = is_array($history) ? $history : array();
        if (($ex = explode(' ', $message->get_content(), 2)) === false) {
            throw new UnexpectedValueException('Input "' . $message->get_content() . '" is not recognized as a command.');
        }
        $this->bin = count($ex) > 0 ? preg_replace('/^!/', '', $ex[0]) : NULL;
        $this->args = count($ex) > 1 ? $ex[1] : NULL;
    }

    /**
     * Include the file "cmd_[$bin].php" to run the command.
     * If arguments are provided, they will be stored in the global variable
     * STD_IN (string) so that they can be used in the external PHP file.
     * The result should be stored in a global variable STD_OUT.
     * @return Command
     */
    public function run()
    {
        $plugin_name = preg_replace('/[^a-zA-Z0-9_]+/', '', $this->bin);
        $filename = ROOT_DIR . '/lib/plugins/cmd_' . $plugin_name . '.php';
        if (file_exists($filename) and $plugin_name === $this->bin) {
            // Global variable
            $source  = $this->source;  // The message received by the bot, without the command keyword
            $sender  = $this->sender;  // The sender's username
            $history = $this->history; // The source channel (the sender's username in case of private message)
            $stdin   = $this->args;    // The message history
            // Call external library
            include $filename;
            // Get results
            $this->result = isset($stdout) ? $stdout : NULL; // The message to send, if NULL the robot will remain silent
            $this->sendto = isset($sendto) ? $sendto : NULL; // The channel on which to send the IRC command
            $this->action = isset($action) ? $action : NULL; // The desired command (PRIVMSG if NULL)
        }
        return $this;
    }

    /**
     * Get the requested IRC operation
     * @param string
     * @return string
     */
    public function response_command($default)
    {
        return strtoupper(($this->action !== NULL) ? $this->action : $default);
    }

    /**
     * Get the destination to send the result
     * @param string
     * @return string
     */
    public function reply_to($allowed_channels = array())
    {
        return in_array($this->sendto, $allowed_channels) ? $this->sendto : $this->source;
    }

    /**
     * Get the result of the execution the command.
     * @return mixed
     */
    public function get_result()
    {
        return $this->result;
    }

    /**
     * Get the result of the execution the command as an array.
     * @param int
     * @return mixed[]
     */
    public function get_result_array($flood_limit = 10)
    {
        switch (gettype($this->result)) {
            case 'array': return array_slice($this->result, 0, intval($flood_limit));
            case 'NULL' : return array();
            default     : return array($this->result);
        }
    }
}