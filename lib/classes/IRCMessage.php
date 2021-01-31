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
 * 
 *   <message>    ::= [':' <prefix> <SPACE> ] <command> <params> <crlf>
 *   <prefix>     ::= <servername> | <nick> [ '!' <user> ] [ '@' <host> ]
 *   <command>    ::= <letter> { <letter> } | <number> <number> <number>
 *   <SPACE>      ::= ' ' { ' ' }
 *   <params>     ::= <SPACE> [ ':' <trailing> | <middle> <params> ]
 *   <middle>     ::= <Any *non-empty* sequence of octets not including SPACE
 *                    or NUL or CR or LF, the first of which may not be ':'>
 *   <trailing>   ::= <Any, possibly *empty*, sequence of octets not including
 *                    NUL or CR or LF>
 *   <crlf>       ::= CR LF
 *   <target>     ::= <to> [ "," <target> ]
 *   <to>         ::= <channel> | <user> '@' <servername> | <nick> | <mask>
 *   <channel>    ::= ('#' | '&') <chstring>
 *   <servername> ::= <host>
 *   <host>       ::= see RFC 952 [DNS:4] for details on allowed hostnames
 *   <nick>       ::= <letter> { <letter> | <number> | <special> }
 *   <mask>       ::= ('#' | '$') <chstring>
 *   <chstring>   ::= <any 8bit code except SPACE, BELL, NUL, CR, LF and
 *                    comma (',')>
 *   <user>       ::= <nonwhite> { <nonwhite> }
 *   <letter>     ::= 'a' ... 'z' | 'A' ... 'Z'
 *   <number>     ::= '0' ... '9'
 *   <special>    ::= '-' | '[' | ']' | '\' | '`' | '^' | '{' | '}'
 *   <nonwhite>   ::= <any 8bit code except SPACE (0x20), NUL (0x0), CR
 *                    (0xd), and LF (0xa)>
 *
 * @see https://tools.ietf.org/html/rfc1459
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
class IRCMessage
{
    protected $regex = '/^(?::(?<prefix>(?P<servername>[\w.:-]+)|(?P<nick>[a-zA-Z][a-zA-Z0-9_\[\]\\\\`^\{\}-]*)(?:!(?P<user>[^\s@]+))?(?:@(?P<host>[\w.:\/-]+))?) +)?(?<command>[a-zA-Z]+|[0-9]{3})(?P<params> +(?:(?P<middle>[^\s:][^\s]*) +)?[^:]*(?::(?P<trailing>[^\n\r]*))?)$/';

    // <message>     ::= [':' <prefix> <SPACE> ] <command> <params> <crlf>
    public $message    = NULL;
    // <prefix>      ::= <servername> | <nick> [ '!' <user> ] [ '@' <host> ]
    public $prefix     = NULL;
    // <servername>  ::= see RFC 952 [DNS:4] for details on allowed hostnames
    public $servername = NULL;
    // <nick>        ::= <letter> { <letter> | <number> | <special> }
    public $nick       = NULL;
    // <user>        ::= <nonwhite> { <nonwhite> }
    public $user       = NULL;
    // <host>        ::= see RFC 952 [DNS:4] for details on allowed hostnames
    public $host       = NULL;
    // <command>     ::= <letter> { <letter> } | <number> <number> <number>
    public $command    = NULL;
    // <params>      ::= <SPACE> [ ':' <trailing> | <middle> <params> ]
    public $params     = NULL;
    // <middle>      ::= <Any *non-empty* sequence of octets, the first of 
    //                   which may not be ':'>
    public $middle     = NULL;
    // <trailing>    ::= <Any, possibly *empty*, sequence of octets>
    public $trailing   = NULL;
    
    /**
     * Construct item, parse input string
     */
    function __construct(string $message = '') {
        preg_match($this->regex, $message, $matches);
        $this->message    = $message;
        $this->prefix     = array_key_exists('prefix'    , $matches) ? $matches['prefix']     : NULL;
        $this->servername = array_key_exists('servername', $matches) ? $matches['servername'] : NULL;
        $this->nick       = array_key_exists('nick'      , $matches) ? $matches['nick']       : NULL;
        $this->user       = array_key_exists('user'      , $matches) ? $matches['user']       : NULL;
        $this->host       = array_key_exists('host'      , $matches) ? $matches['host']       : NULL;
        $this->command    = array_key_exists('command'   , $matches) ? $matches['command']    : NULL;
        $this->params     = array_key_exists('params'    , $matches) ? $matches['params']     : NULL;
        $this->middle     = array_key_exists('middle'    , $matches) ? $matches['middle']     : NULL;
        $this->trailing   = array_key_exists('trailing'  , $matches) ? $matches['trailing']   : NULL;
    }

    public function __tostring() : string {
        return $this->message;
    }

    public function get_source() : string {
        return preg_match('/^#/', $this->middle) ? $this->middle : $this->nick;
    }

    public function get_sender() : string {
        return $this->nick;
    }

    public function get_content() : string {
        return $this->trailing;
    }

    public function set_content(string $message) : object {
        $this->trailing = $message;
        return $this;
    }
}
