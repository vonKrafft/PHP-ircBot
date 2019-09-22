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
 * Static class to add colors in IRC messages
 * @author vonKrafft <wandrille@vonkrafft.fr>
 */
class IRCColor
{
    /** The control code to reset formatting **/
    const CONTROL_NORMAL        = '0f';
    /** The control code to start or end color formatting **/
    const CONTROL_COLOR         = '03';
    /** The control code to start or end bold formatting **/
    const CONTROL_BOLD          = '02';
    /** The control code to start or end italic formatting **/
    const CONTROL_ITALIC        = '1d';
    /** The control code to start or end underlining **/
    const CONTROL_UNDERLINE     = '1f';
    /** The control code to start or end strikethrough formatting **/
    const CONTROL_STRIKETHROUGH = '1e';
    /** The control code to start or end monospace formatting **/
    const CONTROL_MONOSPACE     = '11';

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

    public static function color($text, $fg, $bg = NULL) {
        if ($bg === NULL) {
            return chr(hexdec(self::CONTROL_COLOR)) . $fg . $text . chr(hexdec(self::CONTROL_COLOR));
        } else {
            return chr(hexdec(self::CONTROL_COLOR)) . $fg . ',' . $bg . $text . chr(hexdec(self::CONTROL_COLOR));
        }
    }

    /**
     * Return the text, with bold IRC formatting.
     * @param string
     * @return string
     */
    public static function bold($text) {
        return chr(hexdec(self::CONTROL_BOLD)) . $text . chr(hexdec(self::CONTROL_BOLD));
    }


    /**
     * Return the text, with italic IRC formatting.
     * @param string
     * @return string
     */
    public static function italic($text) {
        return chr(hexdec(self::CONTROL_ITALIC)) . $text . chr(hexdec(self::CONTROL_ITALIC));
    }


    /**
     * Return the text, with underline IRC formatting.
     * @param string
     * @return string
     */
    public static function underline($text) {
        return chr(hexdec(self::CONTROL_UNDERLINE)) . $text . chr(hexdec(self::CONTROL_UNDERLINE));
    }

    /**
     * Return the text, with strikethrough IRC formatting.
     * Note: This is a relatively new addition to IRC formatting conventions.
     * Use only when you can afford to have its meaning lost, as not many
     * clients support it yet.
     * @param string
     * @return string
     */
    public static function strikethrough($text) {
        return chr(hexdec(self::CONTROL_STRIKETHROUGH)) . $text . chr(hexdec(self::CONTROL_STRIKETHROUGH));
    }

    /**
     * Return the text, with monospace IRC formatting.
     * Note: This is a relatively new addition to IRC formatting conventions.
     * Use only when you can afford to have its meaning lost, as not many
     * clients support it yet.
     * @param string
     * @return string
     */
    public static function monospace($text) {
        return chr(hexdec(self::CONTROL_MONOSPACE)) . $text . chr(hexdec(self::CONTROL_MONOSPACE));
    }
}