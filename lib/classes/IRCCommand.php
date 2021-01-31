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
    private $bin = null;
    private $args = null;
    private $result = null;
    private $sendto = null;
    private $action = null;
    
    /**
     * Construct the IRC command
     */
    function __construct(
        protected object $message, 
        protected array $history = array()
    ) {
        if ( ! preg_match('/^!(?<bin>[a-z]+)(?: (?<args>.*))?$/', $message->get_content(), $matches)) {
            throw new UnexpectedValueException('Input "' . $message->get_content() . '" is not recognized as a command.');
        }
        $this->bin = $matches['bin'];
        $this->args = array_key_exists('args', $matches) ? $matches['args'] : '';
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
            $source  = $this->message->get_source();  // The source channel (the sender's username in case of private message)
            $sender  = $this->message->get_sender();  // The sender's username
            $history = $this->history;                // The message history
            $stdin   = $this->args;                   // The message received by the bot, without the command keyword
            // Call external library
            include $filename;
            // Get results
            $this->result = isset($stdout) ? $stdout : null; // The message to send, if null the robot will remain silent
            $this->sendto = isset($sendto) ? $sendto : null; // The channel on which to send the IRC command
            $this->action = isset($action) ? $action : null; // The desired command (PRIVMSG if null)
        }
        return $this;
    }

    /**
     * Get the requested IRC operation
     */
    public function response_command() : ?string {
        return $this->action !== null ? strtoupper($this->action) : null;
    }

    /**
     * Get the destination to send the result
     */
    public function reply_to(array $allowed_channels = array()) : string {
        return in_array($this->sendto, $allowed_channels) ? $this->sendto : $this->message->get_source();
    }

    /**
     * Get the result of the execution the command.
     */
    public function get_result() : mixed {
        return $this->result;
    }

    /**
     * Get the result of the execution the command as an array.
     */
    public function get_result_array(int $flood_limit = 10) : array {
        switch (gettype($this->result)) {
            case 'array': return array_slice($this->result, 0, intval($flood_limit));
            case 'null' : return array();
            default     : return array($this->result);
        }
    }
}
