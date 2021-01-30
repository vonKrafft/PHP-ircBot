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
 * IRCClient
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
abstract class IRCClient
{
    // Connection Registration
    const PASS     = 'PASS';
    const NICK     = 'NICK';
    const USER     = 'USER';
    const OPER     = 'OPER';
    const MODE     = 'MODE';
    const SERVICE  = 'SERVICE';
    const QUIT     = 'QUIT';
    const SQUIT    = 'SQUIT';

    // Channel operations
    const JOIN     = 'JOIN';
    const PART     = 'PART';
    const TOPIC    = 'TOPIC';
    const NAMES    = 'NAMES';
    const LIST     = 'LIST';
    const KICK     = 'KICK';
    const INVITE   = 'INVITE';

    // Sending messages
    const PRIVMSG  = 'PRIVMSG';
    const NOTICE   = 'NOTICE';

    // Server queries and commands
    const MOTD     = 'MOTD';
    const LUSERS   = 'LUSERS';
    const VERSION  = 'VERSION';
    const STATS    = 'STATS';
    const LINKS    = 'LINKS';
    const TIME     = 'TIME';
    const CONNECT  = 'CONNECT';
    const TRACE    = 'TRACE';
    const ADMIN    = 'ADMIN';
    const INFO     = 'INFO';

    // Service Query and Commands
    const SERVLIST = 'SERVLIST';
    const SQUERY   = 'SQUERY';

    // User based queries
    const WHO      = 'WHO';
    const WHOIS    = 'WHOIS';
    const WHOWAS   = 'WHOWAS';

    // Miscellaneous messages
    const KILL     = 'KILL';
    const PING     = 'PING';
    const PONG     = 'PONG';
    const ERROR    = 'ERROR';

    // This is going to hold the TCP/IP connection
    protected $_socket = NULL;

    // Configuration
    protected $_config = array();

    /**
     * Construct item, opens the server connection
     * @param mixed[]
     */
    function __construct($config = array())
    {
        // Init configurations properties
        $this->_config = array(
            'server'   => filter_var($config['server'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME),
            'port'     => filter_var($config['port'], FILTER_VALIDATE_INT, array('options' => array('default' => 6667, 'min_range' => 0, 'max_range' => 65535))),
            'channels' => is_array($config['channels']) ? $config['channels'] : array(),
            'nickname' => preg_match('/^[a-zA-Z][a-zA-Z0-9_\[\]\\\\`^\{\}-]*$/', $config['nickname']) ? $config['nickname'] : false,
            'realname' => preg_match('/^[^\s@]+$/', $config['realname']) ? $config['realname'] : $config['nickname'],
            'version'  => array_key_exists('version', $config) ? $config['version'] : '1.0.0',
        );
        // Check config
        foreach ($this->_config as $key => $value) {
            if ($value === false) {
                $v = array_key_exists($key, $config) ? $config[$key] : 'NULL';
                throw new UnexpectedValueException('Invalid configuration: ' . $key . ' => ' . $v, 1);
            }
        }
        // Create socket
        $this->_socket = fsockopen($this->get_config('server'), $this->get_config('port'), $errno, $errstr);
        if ( ! $this->_socket) {
            throw new RuntimeException('Socket creation failed (' . $errno . '): ' . $errstr, 1);
        }
    }

    /**
     * This is the workhorse function, grabs messages from the IRC server, 
     * processes them if needed, and returns a result
     */
    abstract protected function loop();

    /**
     * Log received or sent messages
     *
     * @param string
     * @param bool
     * @return int|bool
     */
    protected function log($message, $sent = false)
    {
    	$message = strval($message);
        if (strlen($message) > 0) {
            $line = sprintf('[%s] %s %s' . PHP_EOL, date('Y-m-d H:i:s'), ($sent ? '>>>' : '<<<'), $message);
            printf('%s', $line); // Print to stdout
            $filename = preg_replace('/[^a-z0-9_]+/', '', strtolower($this->get_config('nickname', 'phpbot'))) . '_' . date('y\wW') . '.log';
            return file_put_contents(ROOT_DIR . '/var/log/' . $filename, $line, FILE_APPEND);
        }
        return 0;
    }

    /**
     * Get configuration property by key
     * @param string
     * @param mixed
     * @return mixed
     */
    protected function get_config($key, $default = NULL)
    {
    	return array_key_exists($key, $this->_config) ? $this->_config[$key] : $default;
    }

    /**
     * Set all configuration properties
     * @param string
     * @param mixed
     * @return mixed[]
     */
    protected function set_config($key, $value)
    {
    	$this->_config[$key] = $value;
    	return $this->_config;
    }

    /**
     * Displays stuff to the broswer and sends data to the server.
     *
     * @param string
     * @param string
     * @param bool
     * @return IRCClient
     */
    protected function send_data($cmd, $params = NULL, $logged = false)
    {
    	if ($this->_socket !== NULL) {
	        if ($params === NULL) {
	            fputs($this->_socket, $cmd . "\r\n");
	            if ($logged === true) $this->log($cmd, true);
	        } else {
	            fputs($this->_socket, $cmd . ' ' . $params . "\r\n");
	            if ($logged === true) $this->log($cmd . ' ' . $params, true);
	        }
        }
        return $this;
    }

    /**
     * Logs the client in on the server
     *
     * @param string
     * @param string
     * @return IRCClient
     */
    protected function login($nickname, $realname) {
        $this->user($nickname, $realname, 8);
        $this->nick($nickname);
        return $this;
    }

    /**
     * USER <user> <mode> <unused> <realname>
     *
     * user       =  1*( %x01-09 / %x0B-0C / %x0E-1F / %x21-3F / %x41-FF )
     *                 ; any octet except NUL, CR, LF, " " and "@"
     * mode       =  ( "0" / "4" / "8" / "12" )
     *                 ; if the bit 2 is set, the user mode 'w' will be set
     *                 ; if the bit 3 is set, the user mode 'i' will be set
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.1.3
     * @param string
     * @param string
     * @param int
     * @return IRCClient
     */
    protected function user($user, $realname = NULL, $mode = 0)
    {
        $user = preg_match('/^[^\s,@]+(,[^\s,@]+)*$/', strval($user)) ? strval($user) : NULL;
        $mode = preg_match('/^(0|4|8|12)$/', strval($mode)) ? strval($mode) : '0';
        if (isset($user, $realname, $mode)) {
        	$this->send_data(self::USER, $user . ' ' . $mode . ' * :' . $realname, true);
        } elseif (isset($user, $mode)) {
        	$this->send_data(self::USER, $user . ' ' . $mode . ' * :' . $user, true);
        }
        return $this;
    }

    /**
     * NICK <nickname>
     *
     * nickname   =  ( letter / special ) *8( letter / digit / special / "-" )
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.1.2
     * @param string
     * @return IRCClient
     */
    protected function nick($nickname)
    {
        $nickname = preg_match('/^[a-z\x5B-\x60\x7B-\x7D]\w*$/i', strval($nickname)) ? strval($nickname) : NULL;
        if (isset($nickname)) {
            $this->send_data(self::NICK, $nickname, true);
        }
        return $this;
    }

    /**
     * JOIN ( <channel> *( "," <channel> ) [ <key> *( "," <key> ) ] ) / "0"
     *
     * channel    =  ( "#" / "+" / "&" ) chanstring
     * chanstring =  %x01-07 / %x08-09 / %x0B-0C / %x0E-1F / %x21-2B
     * chanstring =/ %x2D-39 / %x3B-FF
     *                 ; any octet except NUL, BELL, CR, LF, " ", "," and ":"
     * key        =  1*23( %x01-05 / %x07-08 / %x0C / %x0E-1F / %x21-7F )
     *                 ; any 7-bit US_ASCII character,
     *                 ; except NUL, CR, LF, FF, h/v TABs, and " "
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.2.1
     * @param string|array
     * @return IRCClient
     */
    protected function join($channels, $keys = array())
    {
        $channels = is_array($channels) ? implode(',', $channels) : strval($channels);
        $channels = preg_match('/^[#&+][^\s,:]+(,[#&+][^\s,:]+)*$/', $channels) ? $channels : NULL;
        $keys     = is_array($keys) ? implode(',', $keys) : strval($keys);
        $keys     = preg_match('/^[^\s,]{1,23}(,[^\s,]{1,23})*$/', $keys) ? $keys : NULL;
        if (isset($channels, $keys)) {
            $this->send_data(self::JOIN, $channels . ' ' . $keys, true);
        } elseif (isset($channels)) {
            $this->send_data(self::JOIN, $channels, true);
        }
        return $this;
    }

    /**
     * PONG :<server>
     *
     * server     =  shortname *( "." shortname )
     * shortname  =  ( letter / digit ) *( letter / digit / "-" )
     *                 ; as specified in RFC 1123 [HNAME]
     *
     * @param string 
     * @return IRCClient
     */
    protected function pong($server)
    {
        $server = preg_replace('/^:/', '', strval($server));
        $server = preg_match('/^[a-z0-9][a-z0-9-]*(.[a-z0-9][a-z0-9-]*)*$/i', strval($server)) ? strval($server) : NULL;
        if (isset($server)) {
            $this->send_data(self::PONG, ':' . $server);
        }
        return $this;
    }

    /**
     * PRIVMSG <msgtarget> <text to be sent>
     *
     * msgtarget  =  msgto *( "," msgto )
     * msgto      =  channel / nickname
     * channel    =  ( "#" / "+" / "&" ) chanstring
     * chanstring =  %x01-07 / %x08-09 / %x0B-0C / %x0E-1F / %x21-2B
     * chanstring =/ %x2D-39 / %x3B-FF
     *                 ; any octet except NUL, BELL, CR, LF, " ", "," and ":"
     * nickname   =  ( letter / special ) *8( letter / digit / special / "-" )
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.3.1
     * @param string
     * @param string
     * @return IRCClient
     */
    protected function privmsg($msgtarget, $text)
    {
        $msgtarget = is_array($msgtarget) ? implode(',', $msgtarget) : strval($msgtarget);
        $msgtarget = preg_match('/^([#&+][^\s,:]+|[a-z\x5B-\x60\x7B-\x7D]\w*)(,([#&+][^\s,:]+|[a-z\x5B-\x60\x7B-\x7D]\w*))*$/i', $msgtarget) ? $msgtarget : NULL;
        if (isset($msgtarget)) {
            $this->send_data(self::PRIVMSG, $msgtarget . ' :' . strval($text), true);
        }
        return $this;
    }

    /**
     * TOPIC <channel> [:<topic>]
     *
     * channel    =  ( "#" / "+" / "&" ) chanstring
     * chanstring =  %x01-07 / %x08-09 / %x0B-0C / %x0E-1F / %x21-2B
     * chanstring =/ %x2D-39 / %x3B-FF
     *                 ; any octet except NUL, BELL, CR, LF, " ", "," and ":"
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.2.4
     * @param string
     * @param string
     * @return IRCClient
     */
    protected function topic($channel, $topic = NULL)
    {
        $channel = preg_match('/^[#&+][^\s,:]+$/', strval($channel)) ? strval($channel) : NULL;
        if (isset($channel, $topic)) {
            $this->send_data(self::TOPIC, $channel .  ' :' . strval($topic), true);
        } elseif (isset($channel)) {
            $this->send_data(self::TOPIC, $channel, true);
        }
        return $this;
    }

    /**
     * KICK <channel> <user> [:<comment>]
     *
     * channel    =  ( "#" / "+" / "&" ) chanstring
     * chanstring =  %x01-07 / %x08-09 / %x0B-0C / %x0E-1F / %x21-2B
     * chanstring =/ %x2D-39 / %x3B-FF
     *                 ; any octet except NUL, BELL, CR, LF, " ", "," and ":"
     * user       =  1*( %x01-09 / %x0B-0C / %x0E-1F / %x21-3F / %x41-FF )
     *                 ; any octet except NUL, CR, LF, " " and "@"
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.2.8
     * @param string
     * @param string
     * @param string
     * @return IRCbot
     */
    protected function kick($channel, $user, $comment = NULL)
    {
        $channel = preg_match('/^[#&+][^\s,:]+(,[#&+][^\s,:]+)*$/', strval($channel)) ? strval($channel) : NULL;
        $user    = preg_match('/^[^\s,@]+(,[^\s,@]+)*$/', strval($user)) ? strval($user) : NULL;
        if (isset($channel, $user, $comment)) {
            $this->send_data(self::KICK, $channel . ' ' . $user . ' :' . $comment, true);
        } elseif (isset($channel, $user)) {
            $this->send_data(self::KICK, $channel . ' ' . $user . ' :' . $user, true);
        }
        return $this;
    }
}
