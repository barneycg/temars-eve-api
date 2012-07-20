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
 * @class TeamSpeak3_Helper_Convert
 * @brief Helper class for data conversion.
 */
class TeamSpeak3_Helper_Convert
{
  /**
   * Converts bytes to a human readable value.
   *
   * @param  integer $bytes
   * @return string
   */
  public static function bytes($bytes)
  {
    $kbytes = sprintf("%.02f", $bytes/1024);
    $mbytes = sprintf("%.02f", $kbytes/1024);
    $gbytes = sprintf("%.02f", $mbytes/1024);
    $tbytes = sprintf("%.02f", $gbytes/1024);

    if($tbytes >= 1)
      return $tbytes . " TB";
    if($gbytes >= 1)
      return $gbytes . " GB";
    if($mbytes >= 1)
      return $mbytes . " MB";
    if($kbytes >= 1)
      return $kbytes . " KB";

    return $bytes . " B";
  }

  /**
   * Converts seconds/milliseconds to a human readable value.
   *
   * @param  integer $seconds
   * @param  boolean $is_ms
   * @param  string  $format
   * @return string
   */
  public static function seconds($seconds, $is_ms = FALSE, $format = "%dD %02d:%02d:%02d")
  {
    if($is_ms) $seconds = $seconds/1000;

    return sprintf($format, $seconds/60/60/24, ($seconds/60/60)%24, ($seconds/60)%60, $seconds%60);
  }

  /**
   * Converts a given codec ID to a human readable name.
   *
   * @param  integer $codec
   * @return string
   */
  public static function codec($codec)
  {
    if($codec == TeamSpeak3::CODEC_SPEEX_NARROWBAND)
      return "Speex Narrowband (8 kHz)";
    if($codec == TeamSpeak3::CODEC_SPEEX_WIDEBAND)
      return "Speex Wideband (16 kHz)";
    if($codec == TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND)
      return "Speex Ultra-Wideband (32 kHz)";
    if($codec == TeamSpeak3::CODEC_CELT_MONO)
      return "CELT Mono (48 kHz)";

    return "Unknown";
  }

  /**
   * Converts a given group type ID to a human readable name.
   *
   * @param  integer $type
   * @return string
   */
  public static function groupType($type)
  {
    if($type == TeamSpeak3::GROUP_DBTYPE_TEMPLATE)
      return "Template";
    if($type == TeamSpeak3::GROUP_DBTYPE_REGULAR)
      return "Regular";
    if($type == TeamSpeak3::GROUP_DBTYPE_SERVERQUERY)
      return "ServerQuery";

    return "Unknown";
  }

  /**
   * Converts a given permission type ID to a human readable name.
   *
   * @param  integer $type
   * @return string
   */
  public static function permissionType($type)
  {
    if($type == TeamSpeak3::PERM_TYPE_SERVERGROUP)
      return "Server Group";
    if($type == TeamSpeak3::PERM_TYPE_CLIENT)
      return "Client";
    if($type == TeamSpeak3::PERM_TYPE_CHANNEL)
      return "Channel";
    if($type == TeamSpeak3::PERM_TYPE_CHANNELGROUP)
      return "Channel Group";
    if($type == TeamSpeak3::PERM_TYPE_CHANNELCLIENT)
      return "Channel Client";

    return "Unknown";
  }

  /**
   * Converts a given permission category value to a human readable name.
   *
   * @param  integer $pcat
   * @return string
   */
  public static function permissionCategory($pcat)
  {
    if($pcat == TeamSpeak3::PERM_CAT_GLOBAL)
      return "Global";
    if($pcat == TeamSpeak3::PERM_CAT_GLOBAL_INFORMATION)
      return "Global / Information";
    if($pcat == TeamSpeak3::PERM_CAT_GLOBAL_SERVER_MGMT)
      return "Global / Virtual Server Management";
    if($pcat == TeamSpeak3::PERM_CAT_GLOBAL_ADM_ACTIONS)
      return "Global / Administration";
    if($pcat == TeamSpeak3::PERM_CAT_GLOBAL_SETTINGS)
      return "Global / Settings";
    if($pcat == TeamSpeak3::PERM_CAT_SERVER)
      return "Virtual Server";
    if($pcat == TeamSpeak3::PERM_CAT_SERVER_INFORMATION)
      return "Virtual Server / Information";
    if($pcat == TeamSpeak3::PERM_CAT_SERVER_ADM_ACTIONS)
      return "Virtual Server / Administration";
    if($pcat == TeamSpeak3::PERM_CAT_SERVER_SETTINGS)
      return "Virtual Server / Settings";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL)
      return "Channel";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL_INFORMATION)
      return "Channel / Information";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL_CREATE)
      return "Channel / Create";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL_MODIFY)
      return "Channel / Modify";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL_DELETE)
      return "Channel / Delete";
    if($pcat == TeamSpeak3::PERM_CAT_CHANNEL_ACCESS)
      return "Channel / Access";
    if($pcat == TeamSpeak3::PERM_CAT_GROUP)
      return "Group";
    if($pcat == TeamSpeak3::PERM_CAT_GROUP_INFORMATION)
      return "Group / Information";
    if($pcat == TeamSpeak3::PERM_CAT_GROUP_CREATE)
      return "Group / Create";
    if($pcat == TeamSpeak3::PERM_CAT_GROUP_MODIFY)
      return "Group / Modify";
    if($pcat == TeamSpeak3::PERM_CAT_GROUP_DELETE)
      return "Group / Delete";
    if($pcat == TeamSpeak3::PERM_CAT_CLIENT)
      return "Client";
    if($pcat == TeamSpeak3::PERM_CAT_CLIENT_INFORMATION)
      return "Client / Information";
    if($pcat == TeamSpeak3::PERM_CAT_CLIENT_ADM_ACTIONS)
      return "Client / Admin";
    if($pcat == TeamSpeak3::PERM_CAT_CLIENT_BASICS)
      return "Client / Basics";
    if($pcat == TeamSpeak3::PERM_CAT_CLIENT_MODIFY)
      return "Client / Modify";
    if($pcat == TeamSpeak3::PERM_CAT_FILETRANSFER)
      return "File Transfer";
    if($pcat == TeamSpeak3::PERM_CAT_NEEDED_MODIFY_POWER)
      return "Grant";

    return "Unknown";
  }

  /**
   * Converts a given log level ID to a human readable name.
   *
   * @param  integer $level
   * @return string
   */
  public static function logLevel($level)
  {
    if($level == TeamSpeak3::LOGLEVEL_CRITICAL)
      return "CRITICAL";
    if($level == TeamSpeak3::LOGLEVEL_ERROR)
      return "ERROR";
    if($level == TeamSpeak3::LOGLEVEL_DEBUG)
      return "DEBUG";
    if($level == TeamSpeak3::LOGLEVEL_WARNING)
      return "WARNING";
    if($level == TeamSpeak3::LOGLEVEL_INFO)
      return "INFO";

    return "DEVELOP";
  }

  /**
   * Converts a given string to a ServerQuery password hash.
   *
   * @param  string $plain
   * @return string
   */
  public static function password($plain)
  {
    return base64_encode(sha1($plain, TRUE));
  }
}
