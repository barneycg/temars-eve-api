<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * $Id$
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   TeamSpeak3
 * @version   1.1.5-beta
 * @author    Sven 'ScP' Paulsen
 * @copyright Copyright (c) 2010 by Planet TeamSpeak. All rights reserved.
 */

/**
 * @class TeamSpeak3_Helper_String
 * @brief Helper class for string handling.
 */
class TeamSpeak3_Helper_String implements ArrayAccess, Iterator, Countable
{
  /**
   * Stores the original string.
   *
   * @var string
   */
  protected $string;

  /**
   * @ignore
   */
  protected $position = 0;

  /**
   * The TeamSpeak3_Helper_String constructor.
   *
   * @param  string $string
   * @return TeamSpeak3_Helper_String
   */
  public function __construct($string)
  {
    $this->string = strval($string);
  }

  /**
   * Returns a TeamSpeak3_Helper_String object for thegiven string.
   *
   * @param  string $string
   * @return TeamSpeak3_Helper_String
   */
  public static function factory($string)
  {
    return new self($string);
  }

  /**
   * Replaces every occurrence of the string $search with the string $replace.
   *
   * @param  string  $search
   * @param  string  $replace
   * @param  boolean $caseSensitivity
   * @return TeamSpeak3_Helper_String
   */
  public function replace($search, $replace, $caseSensitivity = TRUE)
  {
    if($caseSensitivity)
    {
      $this->string = str_replace($search, $replace, $this->string);
    }
    else
    {
      $this->string = str_ireplace($search, $replace, $this->string);
    }

    return $this;
  }

  /**
   * This function replaces indexed or associative signs with given values.
   *
   * @param  array  $args
   * @param  string $char
   * @return TeamSpeak3_Helper_String
   */
  public function arg(array $args, $char = "%")
  {
    $args = array_reverse($args, TRUE);

    foreach($args as $key => $val)
    {
      $args[$char . $key] = $val;
      unset($args[$key]);
    }

    $this->string = strtr($this->string, $args);

    return $this;
  }

  /**
   * Returns true if the string starts with $pattern.
   *
   * @param  string  $pattern
   * @return boolean
   */
  public function startsWith($pattern)
  {
    return (substr($this->string, 0, strlen($pattern)) == $pattern) ? TRUE : FALSE;
  }

  /**
   * Returns true if the string ends with $pattern.
   *
   * @param  string  $pattern
   * @return boolean
   */
  public function endsWith($pattern)
  {
    return (substr($this->string, strlen($pattern)*-1) == $pattern) ? TRUE : FALSE;
  }

  /**
   * Returns the position of the first occurrence of a char in a string.
   *
   * @param  string $needle
   * @return integer
   */
  public function findFirst($needle)
  {
    return strpos($this->string, $needle);
  }

  /**
   * Returns the position of the last occurrence of a char in a string.
   *
   * @param  string $needle
   * @return integer
   */
  public function findLast($needle)
  {
    return strrpos($this->string, $needle);
  }

  /**
   * Returns the lowercased string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function toLower()
  {
    return new self(strtolower($this->string));
  }

  /**
   * Returns the uppercased string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function toUpper()
  {
    return new self(strtoupper($this->string));
  }

  /**
   * Returns true if the string contains $pattern.
   *
   * @param  string  $pattern
   * @param  booean  $regexp
   * @return boolean
   */
  public function contains($pattern, $regexp = FALSE)
  {
    if(empty($pattern))
    {
      return TRUE;
    }

    if($regexp)
    {
      return (preg_match("/" . $pattern . "/i", $this->string)) ? TRUE : FALSE;
    }
    else
    {
      return (stristr($this->string, $pattern) !== FALSE) ? TRUE : FALSE;
    }
  }

  /**
   * Returns part of a string.
   *
   * @param  integer $start
   * @param  integer $length
   * @return TeamSpeak3_Helper_String
   */
  public function substr($start, $length = null)
  {
    $string = ($length !== null) ? substr($this->string, $start, $length) : substr($this->string, $start);

    return new self($string);
  }

  /**
   * Splits the string into substrings wherever $separator occurs.
   *
   * @param  string  $separator
   * @param  integer $limit
   * @return array
   */
  public function split($separator, $limit = 0)
  {
    $parts = explode($separator, $this->string, ($limit) ? intval($limit) : $this->count());

    foreach($parts as $key => $val)
    {
      $parts[$key] = new self($val);
    }

    return $parts;
  }

  /**
   * Appends $part to the string.
   *
   * @param  string $part
   * @return TeamSpeak3_Helper_String
   */
  public function append($part)
  {
    $this->string = $this->string . strval($part);

    return $this;
  }

  /**
   * Prepends $part to the string.
   *
   * @param  string $part
   * @return TeamSpeak3_Helper_String
   */
  public function prepend($part)
  {
    $this->string = strval($part) . $this->string;

    return $this;
  }

  /**
   * Returns a section of the string.
   *
   * @param  string  $separator
   * @param  integer $first
   * @param  integer $last
   * @return TeamSpeak3_Helper_String
   */
  public function section($separator, $first = 0, $last = 0)
  {
    $sections = explode($separator, $this->string);

    $total = count($sections);
    $first = intval($first);
    $last = intval($last);

    if($first > $total) return null;
    if($first > $last) $last = $first;

    for($i = 0; $i < $total; $i++)
    {
      if($i < $first || $i > $last)
      {
        unset($sections[$i]);
      }
    }

    $string = implode($separator, $sections);

    return new self($string);
  }

  /**
   * Sets the size of the string to $size characters.
   *
   * @param  integer $size
   * @param  string  $char
   * @return TeamSpeak3_Helper_String
   */
  public function resize($size, $char = "\0")
  {
    $chars = ($size - $this->count());

    if($chars < 0)
    {
      $this->string = substr($this->string, 0, $chars);
    }
    elseif($chars > 0)
    {
      $this->string = str_pad($this->string, $size, strval($char));
    }

    return $this;
  }

  /**
   * Strips whitespaces (or other characters) from the beginning and end of the string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function trim()
  {
    $this->string = trim($this->string);

    return $this;
  }

  /**
   * Escapes a string using the TeamSpeak 3 escape patterns.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function escape()
  {
    foreach(TeamSpeak3::getEscapePatterns() as $search => $replace)
    {
      $this->string = str_replace($search, $replace, $this->string);
    }

    return $this;
  }

  /**
   * Unescapes a string using the TeamSpeak 3 escape patterns.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function unescape()
  {
    $this->string = strtr($this->string, array_flip(TeamSpeak3::getEscapePatterns()));

    return $this;
  }

  /**
   * Removes any non alphanumeric characters from the string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function filterAlnum()
  {
    $this->string = preg_replace("/[^[:alnum:]]/", "",  $this->string);

    return $this;
  }

  /**
   * Removes any non alphabetic characters from the string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function filterAlpha()
  {
    $this->string = preg_replace("/[^[:alpha:]]/", "",  $this->string);

    return $this;
  }

  /**
   * Removes any non numeric characters from the string.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function filterDigits()
  {
    $this->string = preg_replace("/[^[:digit:]]/", "",  $this->string);

    return $this;
  }

  /**
   * Returns TRUE if the string is a numeric value.
   *
   * @return boolean
   */
  public function isInt()
  {
    return (is_numeric($this->string) && !$this->contains(".")) ? TRUE : FALSE;
  }

  /**
   * Returns the integer value of the string.
   *
   * @return float
   * @return integer
   */
  public function toInt()
  {
    if($this->string == pow(2, 63) || $this->string == pow(2, 64))
    {
      return -1;
    }

    return ($this->string > pow(2, 31)) ? floatval($this->string) : intval($this->string);
  }

  /**
   * Calculates and returns the crc32 polynomial of the string.
   *
   * @return string
   */
  public function toCrc32()
  {
    return crc32($this->string);
  }

  /**
   * Calculates and returns the md5 checksum of the string.
   *
   * @return string
   */
  public function toMd5()
  {
    return md5($this->string);
  }

  /**
   * Calculates and returns the sha1 checksum of the string.
   *
   * @return string
   */
  public function toSha1()
  {
    return sha1($this->string);
  }

  /**
   * Returns TRUE if the string is UTF-8 encoded.
   *
   * @return boolean
   */
  public function isUtf8()
  {
    return (utf8_encode(utf8_decode($this->string)) == $this->string) ? TRUE : FALSE;
  }

  /**
   * Converts the string to UTF-8.
   *
   * @return TeamSpeak3_Helper_String
   */
  public function toUtf8()
  {
    if(!$this->isUtf8())
    {
      $this->string = utf8_encode($this->string);
    }

    return $this;
  }

  /**
   * Encodes the string with MIME base64 and returns the result.
   *
   * @return string
   */
  public function toBase64()
  {
    return base64_encode($this->string);
  }

  /**
   * Decodes the string with MIME base64 and returns the result as an TeamSpeak3_Helper_String
   *
   * @param  string
   * @return TeamSpeak3_Helper_String
   */
  public static function fromBase64($base64)
  {
    return new self(base64_decode($base64));
  }

  /**
   * Returns the hexadecimal value of the string.
   *
   * @return string
   */
  public function toHex()
  {
    $hex = "";

    foreach($this as $char)
    {
      $hex .= $char->toHex();
    }

    return $hex;
  }

  /**
   * Returns the TeamSpeak3_Helper_String based on a given hex value.
   *
   * @param  string
   * @throws TeamSpeak3_Helper_Exception
   * @return TeamSpeak3_Helper_String
   */
  public static function fromHex($hex)
  {
    $string = "";

    if(strlen($hex)%2 == 1)
    {
      throw new TeamSpeak3_Helper_Exception("given parameter '" . $hex . "' is not a valid hexadecimal number");
    }

    foreach(str_split($hex, 2) as $chunk)
    {
      $string .= chr(hexdec($chunk));
    }

    return new self($string);
  }

  /**
   * Replaces space characters with percent encoded strings.
   *
   * @return string
   */
  public function spaceToPercent()
  {
    return str_replace(" ", "%20", $this->string);
  }

  /**
   * Returns the string as a standard string
   *
   * @return string
   */
  public function toString()
  {
    return $this->string;
  }

  /**
   * Magical function that allows you to call PHP's built-in string functions on the TeamSpeak3_Helper_String object.
   *
   * @param  string $function
   * @param  array  $args
   * @throws TeamSpeak3_Helper_Exception
   * @return TeamSpeak3_Helper_String
   */
  public function __call($function, $args)
  {
    if(!function_exists($function))
    {
      throw new TeamSpeak3_Helper_Exception("cannot call undefined function '" . $function . "' on this object");
    }

    if(count($args))
    {
      if(($key = array_search($this, $args, TRUE)) !== FALSE)
      {
        $args[$key] = $this->string;
      }
      else
      {
        throw new TeamSpeak3_Helper_Exception("cannot call undefined function '" . $function . "' without the " . __CLASS__ . " object parameter");
      }

      $return = call_user_func_array($function, $args);
    }
    else
    {
      $return = call_user_func($function, $this->string);
    }

    if(is_string($return))
    {
      $this->string = $return;
    }
    else
    {
      return $return;
    }

    return $this;
  }

  /**
   * Returns the character as a standard string.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->string;
  }

  /**
   * @ignore
   */
  public function count()
  {
    return strlen($this->string);
  }

  /**
   * @ignore
   */
  public function rewind()
  {
    $this->position = 0;
  }

  /**
   * @ignore
   */
  public function valid()
  {
    return $this->position < $this->count();
  }

  /**
   * @ignore
   */
  public function key()
  {
    return $this->position;
  }

  /**
   * @ignore
   */
  public function current()
  {
    return new TeamSpeak3_Helper_Char($this->string{$this->position});
  }

  /**
   * @ignore
   */
  public function next()
  {
    $this->position++;
  }

  /**
   * @ignore
   */
  public function offsetExists($offset)
  {
    return ($offset < strlen($this->string)) ? TRUE : FALSE;
  }

  /**
   * @ignore
   */
  public function offsetGet($offset)
  {
    return ($this->offsetExists($offset)) ? new TeamSpeak3_Helper_Char($this->string{$offset}) : null;
  }

  /**
   * @ignore
   */
  public function offsetSet($offset, $value)
  {
    if(!$this->offsetExists($offset)) return;

    $this->string{$offset} = strval($value);
  }

  /**
   * @ignore
   */
  public function offsetUnset($offset)
  {
    if(!$this->offsetExists($offset)) return;

    $this->string = substr_replace($this->string, "", $offset, 1);
  }
}
