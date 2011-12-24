<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class AuthFilter extends CFilter
{
  public $nonSupportBrowser=null;
  public $redirectForNonSupport=array();
  public $denyPcBrowser=false;
  public $denyUnknownIP=true;
  public $redirectForPcBrowser=array();
  public $putenvByUA=null;
  public $onAuthFilter=null;

  // only internal use
  static public $official=false;
  static public $guidAttribute;

  protected function preFilter($filterChain)
  {
    // PC ブラウザアクセス
    if ($this->denyPcBrowser && !$filterChain->controller->isMobile)
      $filterChain->controller->redirect($this->redirectForPcBrowser);

    // サポートブラウザじゃない
    if (is_array($this->nonSupportBrowser))
    {
      foreach ($this->nonSupportBrowser as $i=>$k)
      {
        if (preg_match($k, $_SERVER['HTTP_USER_AGENT']))
          $filterChain->controller->redirect($this->redirectForNonSupport);
      }
    }

    self::$official=$filterChain->controller->docomoOfficial;

    ini_set('session.use_only_cookies','0');
    ini_set('session.use_cookies','0');
    ini_set('session.use_trans_sid','0');
    $session=null;
    $unknownIP=true;
    if($filterChain->controller->network)
    {
      // Docomo
      if($filterChain->controller->isDocomo)
      {
        if(self::$official)
        {
          $prefix=($_SERVER['SERVER_PORT']==443)? 'https://':'http://';
          $self=$prefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
          $connect=strstr($self,'?')? '&':'?';

          if(!isset($_REQUEST['uid']))
            return $filterChain->controller->redirect($self.$connect.'uid=NULLGWDOCOMO');
          else
            $session=$_REQUEST['uid'];
        }
        else
        {
          if(isset($_SERVER['HTTP_X_DCMGUID']))
            $session=$_SERVER['HTTP_X_DCMGUID'];
        }
      }
      // Crowler
      else if($filterChain->controller->isCrowler)
      {
        $session='0';
      }
      // Au
      else if($filterChain->controller->isAu)
      {
        if(array_key_exists('HTTP_X_UP_SUBNO', $_SERVER))
          $session=$_SERVER['HTTP_X_UP_SUBNO'];
      }
      // Softbank
      else if($filterChain->controller->isSoftbank)
      {
        if(array_key_exists('HTTP_X_JPHONE_UID', $_SERVER))
          $session=$_SERVER['HTTP_X_JPHONE_UID'];
      }
      $filterChain->controller->identity=$session;
      session_id(md5($session));
      $unknownIP=false;
    }

    // UserAgengごまかしてるでしょ？
    if($this->denyUnknownIP && $unknownIP)
      $filterChain->controller->redirect($this->redirectForPcBrowser);
    
    // ロボット 
    if($this->putenvByUA)
    {
      foreach($this->putenvByUA as $m=>$n)
      {
        foreach($n as $ua)
        {
          if(strstr($_SERVER['HTTP_USER_AGENT'],$ua))
          {
            putenv($m.'=1');
            break;
          }
        }
      }
    }

    Yii::trace('[carrier] '.$session, 'system.filters.ktai');

    if(is_array($this->onAuthFilter))
      call_user_func($this->onAuthFilter);
    
    if($filterChain->controller->isDocomo)
      ob_start();
    $filterChain->run();
    $this->postFilter($filterChain);
  }

  protected function postFilter($filterChain)
  {
    if($filterChain->controller->isDocomo)
    {
      echo self::addDocomoUid(ob_get_clean());
      echo("<!-- docomo -->\n");
    }
    else if($filterChain->controller->isSoftbank)
      echo("<!-- SoftBank -->\n");
    else if($filterChain->controller->isAu)
      echo("<!-- ezweb -->\n");
    else
      echo("<!-- mobile -->\n");
  }

  static public function addDocomoUid($html)
  {
    self::$guidAttribute=self::$official? 'uid=NULLGWDOCOMO':'guid=on';
    return preg_replace_callback('/<(a|form) ([^>]+)>/i', array('AuthFilter','replaceHtmlLink'), $html);
  }

  static public  function replaceHtmlLink($replace){
    $str=stripslashes($replace[2]);

    // 必要ないパターン
    if (preg_match('/(href|action)="(https?|ftp):\/\//i',$str) ||
       preg_match('/(href|action)="#/i',$str) ||
       preg_match('/(href|action)="(mailto|tel|fax|news):/i',$str))
    {
         
      return '<'.$replace[1].' '.$str.'>';
    }

    // formタグの公式サイトはhiddenで乗せる
    if (self::$official && strtolower($replace[1])!='a' && preg_match('/action=/i',$str))
    {
      $data=explode("=", self::$guidAttribute);
      $str=$str.'>'."\n".'<input type="hidden" name="'.$data[0].'" value="'.$data[1].'" /';
    // その他もろもろ
    }
    else
    {
      if (preg_match('/(href|action)="([^"]+)"/i',$str,$match))
      {
        if (preg_match('/\?$/i',$match[2]) || preg_match('/&$/i',$match[2]))
        {
          $str=preg_replace('/(href|action)="([^"]+)"/i',
            '$1="$2'.self::$guidAttribute.'"',$str);
        }
        else if (preg_match('/\?.+#.+/i',$match[2]))
        {
          $str=preg_replace('/(href|action)="([^"]+)(#.+)"/i',
            '$1="$2&'.self::$guidAttribute.'$3"',$str);
        }
        else if (preg_match('/\?/i',$match[2]))
        {
          $str=preg_replace('/(href|action)="([^"]+)"/i',
            '$1="$2&'.self::$guidAttribute.'"',$str);
        }
        else
        {
          $str=preg_replace("/(href|action)=\"([^\"]+)\"/i",
            '$1="$2?'.self::$guidAttribute.'"',$str);
        }
      }
    }
    return '<'.$replace[1].' '.$str.'>';
  }
}
