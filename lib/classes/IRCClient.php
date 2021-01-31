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

    /**
     * Construct the IRC client
     */
    public function __construct(
        protected array $_config = array(),
        protected mixed $_socket = false,
    ) { }

    /**
     * This is the workhorse function, grabs messages from the IRC server, 
     * processes them if needed, and returns a result
     */
    abstract protected function loop();

    /**
     * Get configuration property.
     */
    public function __get(string $property) : mixed {
        return array_key_exists($property, $this->_config) ? $this->_config[$property] : null;
    }

    /**
     * Set all configuration properties.
     */
    public function __set(string $property, mixed $value) : void {
        if ($property === 'server' and filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $this->_config['server'] = filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        } elseif ($property === 'port' and filter_var($value, FILTER_VALIDATE_INT, array('options' => array('default' => 6667, 'min_range' => 0, 'max_range' => 65535)))) {
            $this->_config['port'] = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('default' => 6667, 'min_range' => 0, 'max_range' => 65535)));
        } elseif ($property === 'channels' and is_array($value)) {
            $this->_config['channels'] = $value;
        } elseif ($property === 'nickname' and preg_match('/^[a-zA-Z][a-zA-Z0-9_\[\]\\\\`^\{\}-]*$/', $value)) {
            $this->_config['nickname'] = $value;
        } elseif ($property === 'realname' and preg_match('/^[^\s@]+$/', $value)) {
            $this->_config['realname'] = $value;
        } elseif ($property === 'version' and preg_match('/^[0-9]\.[0-9]\.[0-9](-[a-z0-9_-]+)?$/', $value)) {
            $this->_config['version'] = $value;
        } else {
            throw new UnexpectedValueException('Invalid configuration: ' . $property . ' => ' . $value, 1);
        }
    }

    /**
     * Log received or sent messages.
     */
    public function log(string $message, string $prefix = '>>>') : int|bool {
        if (strlen($message) > 0) {
            $row = sprintf('[%s] %s %s' . PHP_EOL, date('Y-m-d H:i:s'), $prefix, $message);
            $botname = strtolower($this->nickname ? $this->nickname : 'phpbot');
            printf('<%s> %s', $botname, $row); // Print to stdout
            $filename = preg_replace('/[^a-z0-9_]+/', '', $botname) . '_' . date('y\wW') . '.log';
            return file_put_contents(ROOT_DIR . '/var/log/' . $filename, $row, FILE_APPEND);
        }
        return false;
    }

    /**
     * Display stuff to the broswer and send data to the server.
     */
    private function __send_data(string $cmd, ?string $args = null, bool $log = false) : object {
    	if ($this->_socket !== null) {
            $data = ($args === null) ? $cmd : $cmd . ' ' . $args;
            fputs($this->_socket, $data . "\r\n");
            if ($log) $this->log($data, '<<<');
        }
        return $this;
    }

    /**
     * Set server/port and create the socket
     */
    protected function connect(string $server, int $port = 6667) : object {
        $this->server = $server;
        $this->port = $port;
        $this->_socket = fsockopen($this->server, $this->port, $errno, $errstr);
        if ( ! $this->_socket) {
            throw new RuntimeException('Socket creation failed (' . $errno . '): ' . $errstr, 1);
        }
        return $this;
    }

    /**
     * Sets nickname/realname and logs in the client to the server
     */
    protected function login(string $nickname, ?string $realname = null, string $version = '1.0.0') : object {
        $this->nickname = $nickname;
        $this->realname = $realname;
        $this->version = $version;
        $this->user($this->nickname, $this->realname, 8);
        $this->nick($this->nickname);
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
     */
    protected function user(?string $user, ?string $realname = null, int $mode = 0) : object {
        $user = preg_match('/^[^\s,@]+(,[^\s,@]+)*$/', strval($user)) ? strval($user) : null;
        $mode = preg_match('/^(0|4|8|12)$/', strval($mode)) ? strval($mode) : '0';
        if (isset($user, $realname, $mode)) {
        	$this->__send_data(self::USER, $user . ' ' . $mode . ' * :' . $realname, true);
        } elseif (isset($user, $mode)) {
        	$this->__send_data(self::USER, $user . ' ' . $mode . ' * :' . $user, true);
        }
        return $this;
    }

    /**
     * NICK <nickname>
     *
     * nickname   =  ( letter / special ) *8( letter / digit / special / "-" )
     *
     * @see https://tools.ietf.org/html/rfc2812#section-3.1.2
     */
    protected function nick(?string $nickname) : object {
        $nickname = preg_match('/^[a-z\x5B-\x60\x7B-\x7D]\w*$/i', strval($nickname)) ? strval($nickname) : null;
        if (isset($nickname)) {
            $this->__send_data(self::NICK, $nickname, true);
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
     */
    protected function join(string|array|null $channels, string|array|null $keys = array()) : object {
        $channels = is_array($channels) ? implode(',', $channels) : strval($channels);
        $channels = preg_match('/^[#&+][^\s,:]+(,[#&+][^\s,:]+)*$/', $channels) ? $channels : null;
        $keys     = is_array($keys) ? implode(',', $keys) : strval($keys);
        $keys     = preg_match('/^[^\s,]{1,23}(,[^\s,]{1,23})*$/', $keys) ? $keys : null;
        if (isset($channels, $keys)) {
            $this->__send_data(self::JOIN, $channels . ' ' . $keys, true);
        } elseif (isset($channels)) {
            $this->__send_data(self::JOIN, $channels, true);
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
     * @see https://tools.ietf.org/html/rfc2812#section-3.7.3
     */
    protected function pong(?string $server) : object {
        $server = preg_replace('/^:/', '', strval($server));
        $server = preg_match('/^[a-z0-9][a-z0-9-]*(.[a-z0-9][a-z0-9-]*)*$/i', strval($server)) ? strval($server) : null;
        if (isset($server)) {
            $this->__send_data(self::PONG, ':' . $server);
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
     */
    protected function privmsg(?string $msgtarget, ?string $text) : object {
        $msgtarget = is_array($msgtarget) ? implode(',', $msgtarget) : strval($msgtarget);
        $msgtarget = preg_match('/^([#&+][^\s,:]+|[a-z\x5B-\x60\x7B-\x7D]\w*)(,([#&+][^\s,:]+|[a-z\x5B-\x60\x7B-\x7D]\w*))*$/i', $msgtarget) ? $msgtarget : null;
        if (isset($msgtarget, $text)) {
            $this->__send_data(self::PRIVMSG, $msgtarget . ' :' . strval($text), true);
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
     */
    protected function topic(?string $channel, ?string $topic = null) : object {
        $channel = preg_match('/^[#&+][^\s,:]+$/', strval($channel)) ? strval($channel) : null;
        if (isset($channel, $topic)) {
            $this->__send_data(self::TOPIC, $channel .  ' :' . strval($topic), true);
        } elseif (isset($channel)) {
            $this->__send_data(self::TOPIC, $channel, true);
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
     */
    protected function kick(?string $channel, ?string $user, ?string $comment = null) : object {
        $channel = preg_match('/^[#&+][^\s,:]+(,[#&+][^\s,:]+)*$/', strval($channel)) ? strval($channel) : null;
        $user    = preg_match('/^[^\s,@]+(,[^\s,@]+)*$/', strval($user)) ? strval($user) : null;
        if (isset($channel, $user, $comment)) {
            $this->__send_data(self::KICK, $channel . ' ' . $user . ' :' . $comment, true);
        } elseif (isset($channel, $user)) {
            $this->__send_data(self::KICK, $channel . ' ' . $user . ' :' . $user, true);
        }
        return $this;
    }

    /**
     * Get version.
     */
    protected function version() : string {
        $name = empty($this->_config['realname']) ? 'PHP IRCBot' : $this->_config['realname'];
        $version = empty($this->_config['version']) ? '' : ' v' . $this->_config['version'];
        $url = ' (https://github.com/vonKrafft/PHP-ircBot)';
        return $name . $version . $url . ' - PHP v' . phpversion();
    }
}
