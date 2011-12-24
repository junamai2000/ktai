<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class AuthFilter extends CFilter
{
  public $docomoOfficial=false;
  public $nonSupportBrowser=null;
  public $redirectForNonSupport=array();
  public $denyPcBrowser=true;
  public $denyUnknownIP=true;
  public $redirectForPcBrowser=array();


  // only internal use
  static public $official=false;
  static public $guidAttribute;

  protected function preFilter($filterChain)
  {
    // PC ブラウザアクセス
    if ($this->denyPcBrowser && !$filterChain->controller->isMobile) {
      $filterChain->controller->redirect($this->redirectForPcBrowser);
    }

    // サポートブラウザじゃない
    if (is_array($this->nonSupportBrowser)) {
      foreach ($this->nonSupportBrowser as $i=>$k) {
        if (preg_match($k, $_SERVER['HTTP_USER_AGENT'])) {
          $filterChain->controller->redirect($this->redirectForNonSupport);
        }
      }
    }

    require('Net/IPv4.php');

    self::$official=$this->docomoOfficial;
    $carrierIPs=array(
      // docomo
      '210.153.84.0/24'=>'D',
      '210.136.161.0/24'=>'D',
      '210.153.86.0/24'=>'D',
      '124.146.174.0/24'=>'D',
      '124.146.175.0/24'=>'D',
      '202.229.176.0/24'=>'D',
      '202.229.177.0/24'=>'D',
      '202.229.178.0/24'=>'D',
      '210.153.87.0/24'=>'D',
      '203.138.180.0/24'=>'D',
      '203.138.181.0/24'=>'D',
      '203.138.203.0/24'=>'D',

      // EZweb
      '210.230.128.224/28'=>'A',
      '121.111.227.160/27'=>'A',
      '61.117.1.0/28'=>'A',
      '219.108.158.0/27'=>'A',
      '219.125.146.0/28'=>'A',
      '61.117.2.32/29'=>'A',
      '61.117.2.40/29'=>'A',
      '219.108.158.40/29'=>'A',
      '219.125.148.0/25'=>'A',
      '222.5.63.0/25'=>'A',
      '222.5.63.128/25'=>'A',
      '222.5.62.128/25'=>'A',
      '59.135.38.128/25'=>'A',
      '219.108.157.0/25'=>'A',
      '219.125.145.0/25'=>'A',
      '121.111.231.0/25'=>'A',
      '121.111.227.0/25'=>'A',
      '118.152.214.192/26'=>'A',
      '118.159.131.0/25'=>'A',
      '118.159.133.0/25'=>'A',
      '118.159.132.160/27'=>'A',
      '111.86.142.0/26'=>'A',

      // softbank
      '123.108.236.0/24'=>'S',
      '123.108.237.0/27'=>'S',
      '202.179.204.0/24'=>'S',
      '202.253.96.224/27'=>'S',
      '210.146.7.192/26'=>'S',
      '210.146.60.192/26'=>'S',
      '210.151.9.128/26'=>'S',
      '210.175.1.128/25'=>'S',
      '211.8.159.128/25'=>'S',
      '123.108.237.224/27'=>'S',
      '202.253.96.0/28'=>'S',

      // TEST 
      '127.0.0.1/32'=>'I',

      // クローラー
      '72.14.199.0/25'=>'C', // google
      '209.85.238.0/25'=>'C',
      '66.249.65.209/32'=>'C',
      '66.249.65.248/32'=>'C',
      '66.249.68.0/24'=>'C',
      '202.238.103.126/32'=>'C', // moba
      '202.213.221.97/32'=>'C',
      '210.150.10.32/27'=>'C', // goo
      '203.131.250.0/24'=>'C',
      '203.104.254.0/24'=>'C', // livedoor
      '124.83.159.0/25'=>'C', // yahoo
      '60.43.36.253/32'=>'C',  // froute
    );

    ini_set('session.use_only_cookies' , '0');
    ini_set('session.use_trans_sid', '0');
    $session=null;
    $unknownIP=true;
    foreach ($carrierIPs as $net=>$val) {
      if (Net_IPv4::ipInNetwork(getenv('REMOTE_ADDR'), $net)) {
        // Docomo
        if ($val=='D' || ($val=='I' && $filterChain->controller->isDocomo)) {
          if (self::$official) {
            if (isset($_REQUEST['uid'])) $session=$_REQUEST['uid'];
          } else {
            if (isset($_SERVER['HTTP_X_DCMGUID'])) $session=$_SERVER['HTTP_X_DCMGUID'];
          }
        // Crowler
        } else if($val=='C') {
          $session='0';
        // Au
        } else if ($val=='A' || ($val=='I' && $filterChain->controller->isAu)) {
          if (array_key_exists('HTTP_X_UP_SUBNO', $_SERVER)) {
            $session=$_SERVER['HTTP_X_UP_SUBNO'];
          }
        // Softbank
        } else if ($val=='S' || ($val=='I' && $filterChain->controller->isSoftbank)) {
          if (array_key_exists('HTTP_X_JPHONE_UID', $_SERVER)) {
            $session=$_SERVER['HTTP_X_JPHONE_UID'];
          }
        }
        session_id(md5($session));
        $unknownIP=false;
        break;
      }
    }

    // UserAgengごまかしてるでしょ？
    if ($this->denyUnknownIP && $unknownIP) {
      $filterChain->controller->redirect($this->redirectForPcBrowser);
    }

    Yii::trace('[carrier] '.$session, 'system.filters.ktai');
    
    if ($filterChain->controller->isDocomo) ob_start();
    $filterChain->run();
    $this->postFilter($filterChain);
  }

  protected function postFilter($filterChain)
  {
    if ($filterChain->controller->isDocomo) {
      echo self::addDocomoUid(ob_get_clean());
      echo("<!-- docomo -->");
    } else if ($filterChain->controller->isSoftbank) {
      echo("<!-- SoftBank -->");
    } else if ($filterChain->controller->isAu) {
      echo("<!-- ezweb -->");
    } else {
      echo("<!-- mobile -->");
    }
  }

  static public function addDocomoUid($html)
  {
    self::$guidAttribute=self::$official? 'uid=NULLGWDOCOMO':'guid=on';
    return preg_replace_callback('/<(a|form) ([^>]+)>/i', "AuthFilter::replaceHtmlLink", $html);
  }

  static public  function replaceHtmlLink($replace){
    $str=stripslashes($replace[2]);

    // 必要ないパターン
    if (preg_match('/(href|action)="(https?|ftp):\/\//i',$str) ||
       preg_match('/(href|action)="#/i',$str) ||
       preg_match('/(href|action)="(mailto|tel|fax|news):/i',$str)) {
         
      return '<'.$replace[1].' '.$str.'>';
    }

    // formタグの公式サイトはhiddenで乗せる
    if (self::$official && strtolower($replace[1])!='a' && preg_match('/action=/i',$str)) {
      $data=explode("=", self::$guidAttribute);
      $str=$str.'><input type="hidden" name="'.$data[0].'" value="'.$data[1].'" /';
    // その他もろもろ
    } else {
      if (preg_match('/(href|action)="([^"]+)"/i',$str,$match)) {
        if (preg_match('/\?$/i',$match[2]) || preg_match('/&$/i',$match[2])) {
          $str=preg_replace('/(href|action)="([^"]+)"/i',
            '$1="$2'.self::$guidAttribute.'"',$str);
        } elseif (preg_match('/\?/i',$match[2])) {
          $str=preg_replace('/(href|action)="([^"]+)"/i',
            '$1="$2&'.self::$guidAttribute.'"',$str);
        } else {
          $str=preg_replace("/(href|action)=\"([^\"]+)\"/i",
            '$1="$2?'.self::$guidAttribute.'"',$str);
        }
      }
    }
    return '<'.$replace[1].' '.$str.'>';
  }
}
