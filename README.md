# PHP-ircBot (Awesome PHP Bot for IRC)

**PHP-ircBot** is a simple IRC bot written in PHP.

The bot implements an IRC client, an IRC message parser according to [RFC 1429](https://tools.ietf.org/html/rfc1459), and allows you to execute PHP scripts triggered by IRC messages beginning with an exclamation mark (`!`).

## Installation

First, you need to install `php >= 7.0.0` and to clone the repository.

```
$ git clone https://github.com/vonKrafft/PHP-ircBot
$ cd PHP-ircBot
```

## Usage

You can edit the main script `MyAwesomeBot.php` or copy it to keep an original version. The bot is ready to use, simply set your own configuration at the end of `MyAwesomeBot.php`.

```php
$admin = ''; // The administrator's nickname, case sensitive (leave empty to not use the admin restrictions)
$bot = new MyAwesomeBot($admin, array(
    'server'   => 'chat.freenode.net',  // Hostname of the IRC server
    'port'     => 6667,                 // Remote port of the IRC server
    'channels' => array(),              // List of IRC channel for auto-join
    'nickname' => 'MyAwesomeNickname',  // Bot nickname
    'realname' => 'MyAwesomeBot',       // Bot name (nickname is used if empty)
    'version'  => '1.0.0',              // Version of the bot
));
```

To start the IRC bot, simply run:

```
$ php MyAwesomeBot.php
```

You can run multiple instances of the bot as long as you do not use a duplicate nickname on the same IRC server.

## Plugins

This bot is designed to execute PHP scripts when it receives a message beginning with an exclamation mark (`!`). The scripts have to be stored in `lib/plugins` and nammed `cmd_<your_command>.php`.

A script template is available in `lib/plugins/skeleton.php`.

When a command is executed (for example `!help`), the associated script (here `lib/plugins/cmd_help.php`) retrieves the user's inputs and the execution context:

```php
$stdin   = isset($stdin)   ? $stdin   : '';      // The message received by the bot, without the command keyword
$sender  = isset($sender)  ? $sender  : '';      // The sender's username
$source  = isset($source)  ? $source  : '';      // The source channel (the sender's username in case of private message)
$history = isset($history) ? $history : array(); // The message history
```

You can edit the template to make the bot do what you want:

```php
// Do magic here ...
$stdout = NULL;
```

Keep in mind that at the end of script execution, the following variables are set to be returned to the `IRCCommand` class:

```php
$stdout = empty($stdout) ? NULL : $stdout; // The message to send, if NULL the robot will remain silent
$sendto = empty($sendto) ? NULL : $sendto; // The channel on which to send the IRC command
$action = empty($action) ? NULL : $action; // The desired command (PRIVMSG if NULL)
```

## License

This source code may be used under the terms of the GNU General Public License version 3.0 as published by the Free Software Foundation and appearing in the file LICENSE included in the packaging of this file. Please review the following information to ensure the GNU General Public License version 3.0 requirements will be met: http://www.gnu.org/copyleft/gpl.html.