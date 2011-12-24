<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
require('Emoji/EmojiBase.php');
require('Emoji/Docomo.php');
require('Emoji/Au.php');
require('Emoji/Softbank.php');
require('Emoji/Img.php');

class Emoji
{
  static public $isDocomo=false;
  static public $isAu=false;
  static public $isSoftbank=false;

  static public function escape($str, $remove = false)
  {
    $result = '';
    $l = strlen($str);
    for ($i=0; $i<$l; $i++) {
      $emoji = '';
      $c1 = ord($str[$i]);
      if (self::$isSoftbank) {
        if ($c1 == 0xF7 || $c1 == 0xF9 || $c1 == 0xFB) {
          $bin = substr($str, $i, 2);
          $emoji = self::escape_softbank($bin);
        }
      } elseif ($c1 == 0xF8 || $c1 == 0xF9) {
        $bin = substr($str, $i, 2);
        $emoji = self::escape_docomo($bin);
      } elseif (0xF3 <= $c1 && $c1 <= 0xF7) {
        $bin = substr($str, $i, 2);
        $emoji = self::escape_ezweb($bin);
      }
      if ($emoji) {
        if (!$remove) {
          $result .= $emoji;
        }
        $i++;
      } else {
        $result .= $str[$i];
        if ((0x81<=$c1 && $c1<=0x9F) || 0xE0 <= $c1) {
          $result .= $str[$i+1];
          $i++;
        }
      }
    }
    return $result;
  }

  static private function escape_docomo($bin)
  {
    $regexp = '\xF8[\x9F-\xFC]|\xF9[\x40-\xFC]';
    if (preg_match('/'.$regexp.'/', $bin)) {
      $unicode = mb_convert_encoding($bin, 'UCS2', 'SJIS-win');
      $converter = EmojiDocomo::getInstance();
      $emoji = $converter->getInternalCode(sprintf('&#x%02X%02X;', ord($unicode[0]), ord($unicode[1])));
    } else {
      $emoji = '';
    }
    return $emoji;
  }

  static private function escape_ezweb($bin)
  {
    $s = (ord($bin[0]) << 8) + ord($bin[1]);
    if (0xF340<=$s && $s<=0xF493) {
      if ($s <= 0xF352) {
        $u = $s - 3443;
      } elseif ($s <= 0xF37E) {
        $u = $s - 2259;
      } elseif ($s <= 0xF3CE) {
        $u = $s - 2260;
      } elseif ($s <= 0xF3FC) {
        $u = $s - 2241;
      } elseif ($s <= 0xF47E) {
        $u = $s - 2308;
      } else {
        $u = $s - 2309;
      }
    } elseif (0xF640<=$s && $s<=0xF7FC) {
      if ($s <= 0xF67E) {
        $u = $s - 4568;
      } elseif ($s <= 0xF6FC) {
        $u = $s - 4569;
      } elseif ($s <= 0xF77E) {
        $u = $s - 4636;
      } elseif ($s <= 0xF7D1) {
        $u = $s - 4637;
      } elseif ($s <= 0xF7E4) {
        $u = $s - 3287;
      } else {
        $u = $s - 4656;
      }
    } else {
      return '';
    }
    $converter = EmojiAu::getInstance();
    return $converter->getInternalCode(sprintf('&#x%04X;', $u));
  }

  static private function escape_softbank($bin)
  {
    $s1 = ord($bin[0]);
    $s2 = ord($bin[1]);
    $w1 = $w2 = 0;
    switch ($s1) {
    case 0xF9:
      if (0x41<=$s2 && $s2<=0x7E) {
        $w1 = ord('G');
        $w2 = $s2 - 0x20;
      } elseif(0x80<=$s2 && $s2<=0x9B) {
        $w1 = ord('G');
        $w2 = $s2 - 0x21;
      } elseif (0xA1<=$s2 && $s2<=0xED) {
        $w1 = ord('O');
        $w2 = $s2 - 0x80;
      }
      break;
    case 0xF7:
      if (0x41<=$s2 && $s2<=0x7E) {
        $w1 = ord('E');
        $w2 = $s2 - 0x20;
      } elseif (0x80<=$s2 && $s2<=0x9B) {
        $w1 = ord('E');
        $w2 = $s2 - 0x21;
      } elseif (0xA1<=$s2 && $s2<=0xF3) {
        $w1 = ord('F');
        $w2 = $s2 - 0x80;
      }
      break;
    case 0xFB:
      if (0x41<=$s2 && $s2<=0x7E) {
        $w1 = ord('P');
        $w2 = $s2 - 0x20;
      } elseif (0x80<=$s2 && $s2<=0x8D) {
        $w1 = ord('P');
        $w2 = $s2 - 0x21;
      } elseif (0xA1<=$s2 && $s2<=0xD7) {
        $w1 = ord('Q');
        $w2 = $s2 - 0x80;
      }
      break;
    default:
      return '';
    }
    $converter = EmojiSoftbank::getInstance();
    return $converter->getInternalCode(pack('c5', 0x1b, 0x24, $w1, $w2, 0x0f));
  }

  static public function convert($str)
  {
    $regexp = '/\[([ies\*]):([0-9a-z#]+)\]/';
    return preg_replace_callback($regexp, array('Emoji', 'convert_callback'), $str);
  }

  static public function convert_callback($matches)
  {
    $carrier = ''; // 現在のキャリア
    $convCarrier = 's'; // [*:XXX]形式のデフォルトキャリア
    $carrierMap = array('i'=>0,'e'=>1,'s'=> 2);
    if (self::$isDocomo) {
      $carrier = 'i';
      $convCarrier = 'i';
    } elseif (self::$isAu) {
      $carrier = 'e';
      $convCarrier = 'e';
    } elseif (self::$isSoftbank) {
      $carrier = 's';
    }

    if ($matches[1] == '*') { // [*:XXX]形式
      $emojiCarrier = $convCarrier; 
      if (isset(Emoji::$charMap[$matches[2]])) {
        $emojiCode = Emoji::$charMap[$matches[2]][$carrierMap[$emojiCarrier]];
      } else {
        return '';
      }
    } else {
      $emojiCarrier = $matches[1];
      $emojiCode = $matches[2];
    }

    if ($emojiCarrier != $carrier) {
      $converter = EmojiImg::getInstance();
    } else {
      switch ($carrier) {
      case 'i':
        $converter = EmojiDocomo::getInstance();
        break;

      case 's':
        $converter = EmojiSoftbank::getInstance();
        break;

      case 'e':
        $converter = EmojiAu::getInstance();
        break;

      default:
        $converter = EmojiImg::getInstance();
        break;
      }
    }
    return $converter->getWebCode($emojiCode, $emojiCarrier);
  }

  static public function unescape($str, $escape_amp = false)
  {
    $amp = ($escape_amp) ? '&amp;' : '&';
    $regexp = "/$amp#x(E[0-9A-F]{3});/";
    return preg_replace_callback($regexp, array('Emoji', 'unescape_callback'), $str);
  }

  static public function unescape_callback($matches)
  {
    $u = hexdec($matches[1]);
    if (0xE63E<=$u && $u<=0xE757) {
      return self::unescape_docomo($u);
    } elseif ((0xE468<=$u && $u<=0xE5DF) || (0xEA80<=$u && $u<=0xEB88)) {
      return self::unescape_ezweb($u);
    } else {
      return $matches[0];
    }
  }

  static private function unescape_docomo($u)
  {
    $u = pack('H4', dechex($u));
    return mb_convert_encoding($u, 'SJIS-win', 'UCS2');
  }

  static private function unescape_ezweb($u)
  {
    if (0xE468<=$u  && $u<=0xE5DF) {
      if ($u <= 0xE4A6) {
        $s = $u + 4568;
      } elseif ($u <= 0xE523) {
        $s = $u + 4569;
      } elseif ($u <= 0xE562) {
        $s = $u + 4636;
      } elseif ($u <= 0xE5B4) {
        $s = $u + 4637;
      } elseif ($u <= 0xE5CC) {
        $s = $u + 4656;
      } else {
        $s = $u + 3443;
      }
    } elseif (0xEA80<=$u && $u<=0xEB88) {
      if ($u <= 0xEAAB) {
        $s = $u + 2259;
      } elseif ($u <= 0xEAFA) {
        $s = $u + 2260;
      } elseif ($u <= 0xEB0D) {
        $s = $u + 3287;
      } elseif ($u <= 0xEB3B) {
        $s = $u + 2241;
      } elseif ($u <= 0xEB7A) {
        $s = $u + 2308;
      } else {
        $s = $u + 2309;
      }
    }
    return pack('H4', dechex($s));
  }

  static public $charMap = array(
    'fine'               => array(1  , 44 , 74 ),
    'good'               => array(204, 287,  14),
    'cloud'              => array(2  , 107, 73 ),
    'rain'               => array(3  , 95 , 75 ),
    'sown'               => array(4  , 191, 72 ),
    'lightning'          => array(5  , 16 , 151),
    'storm'              => array(6  , 190, 414),
    'fog'                => array(7  , 305, 0  ),
    'drizzle'            => array(8  , 481, 407),
    'baseball'           => array(22 , 45 , 22 ),
    'golf'               => array(23 , 306, 20 ),
    'tennis'             => array(24 , 220, 21 ),
    'soccer'             => array(25 , 219, 24 ),
    'motor'              => array(28 , 222, 140),
    'train'              => array(30 , 172, 30 ),
    'car'                => array(33 , 125, 27 ),
    'bus'                => array(35 , 216, 179),
    'plane'              => array(37 , 168, 29 ),
    'house'              => array(38 , 112, 54 ),
    'building'           => array(39 , 156, 56 ),
    'parking'            => array(47 , 208, 169),
    'coffee'             => array(51 , 93 , 69 ),
    'acting'             => array(63 , 494, 426),
    'ticket'             => array(65 , 106, 127),
    'book'               => array(70 , 122, 162),
    'tel'                => array(74 , 85 , 9  ),
    'mobile'             => array(75 , 161, 10 ),
    'memo'               => array(76 , 395, 271),
    'tv'                 => array(77 , 288, 132),
    'foot'               => array(91 , 728, 477),
    'mail1'              => array(106, 784, 93 ),
    'mail2'              => array(110, 108, 93 ),
    'id'                 => array(115, 385, 221),
    'key'                => array(116, 120, 63 ),
    'magnifier'          => array(119, 119, 110),
    'new'                => array(120, 334, 198),
    '#'                  => array(110, 108, 93 ),
    'q'                  => array(124, 4  , 205),
    '1'                  => array(125, 180, 208),
    '2'                  => array(126, 181, 209),
    '3'                  => array(127, 182, 210),
    '4'                  => array(128, 183, 211),
    '5'                  => array(129, 184, 212),
    '6'                  => array(130, 185, 213),
    '7'                  => array(131, 186, 214),
    '8'                  => array(132, 187, 215),
    '9'                  => array(133, 188, 216),
    '0'                  => array(134, 325, 217),
    'ok'                 => array(135, 326, 257),
    'heart'              => array(136, 51 , 34),
    'sheart'             => array(137, 803, 309),
    'hearts'             => array(139, 328, 309),
    'up'                 => array(145, 731, 234),
    'mnote'              => array(146, 343, 62 ),
    'bright'             => array(150, 420, 316),
    'light'              => array(151, 77 , 105),
    'wow'                => array(158, 2  , 33 ),
    'money'              => array(186, 233, 137),
    'pc'                 => array(187, 337, 12 ),
    'pen'                => array(190, 149, 271),
    'crown'              => array(191, 354, 104),
    'cup'                => array(195, 423, 326),
    'copyright'          => array(214, 81 , 258),
    'danger'             => array(220, 1  , 262),
    'school'             => array(227, 377, 177),
    'clover'             => array(230, 53 , 106),
    'sprout'             => array(235, 811, 106),
    'dot'                => array(230, 23 , 205),
    'ext1'               => array(186, 233, 137),
    'ext2'               => array(139, 328, 309),
    'ext3'               => array(38 , 112, 54 ),
    'ext4'               => array(39 , 156, 56 ),
    'satisfy1'           => array(140, 257, 87 ),
    'satisfy2'           => array(198, 446, 86 ),
    'satisfy3'           => array(197, 441, 88 ),
    'satisfy3'           => array(142, 444, 88 )
  );
}
