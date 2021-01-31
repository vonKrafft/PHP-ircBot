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
 * Static class to add colors in IRC messages
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
class IRCColor
{
    /** The control code to reset formatting **/
    const CTRL_NORMAL        = ''; //chr(hexdec('0f'));
    /** The control code to start or end color formatting **/
    const CTRL_COLOR         = ''; //chr(hexdec('03'));
    /** The control code to start or end bold formatting **/
    const CTRL_BOLD          = ''; //chr(hexdec('02'));
    /** The control code to start or end italic formatting **/
    const CTRL_ITALIC        = ''; //chr(hexdec('1d'));
    /** The control code to start or end underlining **/
    const CTRL_UNDERLINE     = ''; //chr(hexdec('1f'));
    /** The control code to start or end strikethrough formatting **/
    const CTRL_STRIKETHROUGH = ''; //chr(hexdec('1e'));
    /** The control code to start or end monospace formatting **/
    const CTRL_MONOSPACE     = ''; //chr(hexdec('11'));

    const WHITE        = '00';
    const BLACK        = '01';
    const BLUE         = '02';
    const NAVY         = self::BLUE;
    const GREEN        = '03';
    const RED          = '04';
    const BROWN        = '05';
    const MAROON       = self::BROWN;
    const PURPLE       = '06';
    const ORANGE       = '07';
    const OLIVE        = self::ORANGE;
    const YELLOW       = '08';
    const LIGHT_GREEN  = '09';
    const LIME         = self::LIGHT_GREEN;
    const TEAL         = '10';
    const LIGHT_CYAN   = '11';
    const CYAN         = self::LIGHT_CYAN;
    const LIGHT_BLUE   = '12';
    const ROYAL        = self::LIGHT_BLUE;
    const PINK         = '13';
    const LIGHT_PURPLE = self::PINK;
    const FUCHSIA      = self::PINK;
    const GREY         = '14';
    const GRAY         = self::GREY;
    const LIGHT_GREY   = '15';
    const SILVER       = self::LIGHT_GREY;
    const LIGHT_GRAY   = self::LIGHT_GREY;

    /**
     * Return the text, with color IRC formatting.
     */
    public static function color(string $text, string $fg, ?string $bg = null) : string {
        $color = ($bg === null) ? $fg : $fg . ',' . $bg;
        return self::CTRL_COLOR . $color . $text . self::CTRL_COLOR;
    }

    /**
     * Return the text, with bold IRC formatting.
     */
    public static function bold(string $text) : string {
        return self::CTRL_BOLD . $text . self::CTRL_BOLD;
    }


    /**
     * Return the text, with italic IRC formatting.
     */
    public static function italic(string $text) : string {
        return self::CTRL_ITALIC . $text . self::CTRL_ITALIC;
    }


    /**
     * Return the text, with underline IRC formatting.
     */
    public static function underline(string $text) : string{
        return self::CTRL_UNDERLINE . $text . self::CTRL_UNDERLINE;
    }

    /**
     * Return the text, with strikethrough IRC formatting.
     * 
     * Note: This is a relatively new addition to IRC formatting conventions.
     * Use only when you can afford to have its meaning lost, as not many
     * clients support it yet.
     */
    public static function strikethrough(string $text) : string{
        return self::CTRL_STRIKETHROUGH . $text . self::CTRL_STRIKETHROUGH;
    }

    /**
     * Return the text, with monospace IRC formatting.
     *
     * Note: This is a relatively new addition to IRC formatting conventions.
     * Use only when you can afford to have its meaning lost, as not many
     * clients support it yet.
     */
    public static function monospace(string $text) : string {
        return self::CTRL_MONOSPACE . $text . self::CTRL_MONOSPACE;
    }
}
