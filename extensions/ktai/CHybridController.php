<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CHybridController extends CController
{
  public $isCrowler=false;
  public $isBot=false;
  public $isMobile=false;
  public $isDocomo=false;
  public $isSoftbank=false;
  public $isAu=false;
  public $isSmartPhone=false;
  public $isAndroid=false;
  public $isiPod=false;
  public $isiPhone=false;
  public $identity=null;
  public $docomoOfficial=null;
  public $network;
  
  public function init()
  {
    $this->network=self::getNetworkName(getenv('REMOTE_ADDR'));
    $this->isMobile();

    if(!$this->isMobile)
      $this->isSmartPhone();
  }

  private function isSmartPhone()
  {
    $userAgent=@$_SERVER['HTTP_USER_AGENT'];
    if($this->isAndroid($userAgent))
    {
      $this->isSmartPhone=true;
      $this->isAndroid=true;
    }
    else if($this->isiPhone($userAgent))
    {
      $this->isSmartPhone=true;
      $this->isiPhone=true;
    }
    else if($this->isiPod($userAgent))
    {
      $this->isSmartPhone=true;
      $this->isiPod=true;
    }
    //else if($this->id=='smartphone'&&$this->network==self::INTERNAL_NW|| $userAgent=='ipass')
    //  $this->isSmartPhone=true;
  }

  private function isMobile()
  {
    $userAgent=@$_SERVER['HTTP_USER_AGENT'];
    //最優先でチェック
    if($this->isBot($userAgent))
    {
      $this->isBot=true;
    }

    if($this->isDocomo($userAgent))
    {
      header('Content-Type: application/xhtml+xml; charset=Shift_JIS');
      $this->isDocomo=true;
      $this->isMobile=true;
    }
    else if($this->isSoftbank($userAgent))
    {
      header('Content-Type: application/xhtml+xml; charset=UTF-8');
      $this->isMobile=true;
      $this->isSoftbank=true;
    }
    else if($this->isAu($userAgent))
    {
      header('Content-Type: application/xhtml+xml; charset=Shift_JIS');
      $this->isMobile=true;
      $this->isAu=true;
    }
    else if($this->isSpmode())
    {
      $this->isDocomo=true;
    }
    else if($this->network==CHybridController::CROWLER_NW)
    {
      $this->isCrowler=true;
    }

    return;
  }

  private function isAndroid($userAgent)
  {
    if(strstr($userAgent,'Android'))
      return true;
    return false;
  }
  
  private function isiPod($userAgent)
  {
    if(strstr($userAgent,'iPod'))
      return true;
    return false;
  }

  private function isiPhone($userAgent)
  {
    if(strstr($userAgent,'iPhone'))
      return true;
    return false;
  }

  private function isSpmode()
  {
    if($this->network==self::SPMODE_NW)
      return true;
    return false;
  }

  private function isDocomo($userAgent)
  {
    if(($this->network==self::DOCOMO_NW) || ($this->network==self::INTERNAL_NW&&preg_match('!^DoCoMo!',$userAgent)))
      return true;
    return false;
  }
  
  private function isSoftbank($userAgent)
  {
    if($this->network==self::SOFTBANK_NW)return true;
    if($this->network==self::INTERNAL_NW)
    {
      if(preg_match('!^SoftBank!',$userAgent))
        return true;
      else if(preg_match('!^Semulator!',$userAgent))
        return true;
      else if(preg_match('!^Vodafone!',$userAgent))
        return true;
      else if(preg_match('!^Vemulator!',$userAgent))
        return true;
      else if(preg_match('!^MOT-!',$userAgent))
        return true;
      else if(preg_match('!^MOTEMULATOR!',$userAgent))
        return true;
      else if(preg_match('!^J-PHONE!',$userAgent))
        return true;
      else if(preg_match('!^J-EMULATOR!',$userAgent))
        return true;
    }
    return false;
  }

  private function isAu($userAgent)
  {
    if($this->network==self::AU_NW)return true;
    if($this->network==self::INTERNAL_NW)
    {
      if(preg_match('!^KDDI-!',$userAgent))
        return true;
      else if(preg_match('!^UP\.Browser!',$userAgent))
        return true;
    }
    return false;
  }

  public function isBot($userAgent)
  {
    if(preg_match('#i-robot|KDDI-Googlebot-Mobile|Y\!J-SRD|Y\!J-MBS|SoftBankMobileSearch|Googlebot-Mobile|Googlebot|Google-Sitemaps|ichiro/mobile goo|ichiro/3\.0|moba-crawler|symphonybot1\.froute\.jp|LD_mobile_bot|Gree Social Feedback/1\.0#',$userAgent))
      return true;
    return false;
  }

  public function resolveViewFile($viewName,$viewPath,$basePath)
  {
    if(!$this->isMobile)
      return parent::resolveViewFile($viewName,$viewPath,$basePath);

    if(empty($viewName))
      return false;

    if(($renderer=Yii::app()->getViewRenderer())!==null)
      $extension=$renderer->fileExtension;
    else
      $extension='.php';

    $prefix=$viewPath.DIRECTORY_SEPARATOR;
    $name='';
    $suffix=$viewName.$extension;

    if($this->isDocomo)
      if(file_exists($prefix.'docomo/'.$suffix))
        $viewName='docomo/'.$viewName;
    else if($this->isSoftbank)
      if(file_exists($prefix.'softbank/'.$suffix))
        $viewName='softbank/'.$viewName;
    else if($this->isAu)
      if(file_exists($prefix.'au/'.$suffix))
        $viewName='au/'.$viewName;

    Yii::trace('[mobile view] '.$viewName,'system.extensions.ktai');

    return parent::resolveViewFile($viewName,$viewPath,$basePath);
  }

  public function redirect($url,$terminate=true,$statusCode=302)
  {
    if($this->isDocomo && is_array($url))
    {
      if($this->docomoOfficial)
        $url=array_merge($url,array('uid'=>'NULLGWDOCOMO'));
      else
        $url=array_merge($url,array('guid'=>'on'));

    }
    parent::redirect($url,$terminate,$statusCode);
  }
  
  const DOCOMO_NW=1;
  const AU_NW=2;
  const SOFTBANK_NW=3;
  const SPMODE_NW=4;
  const INTERNAL_NW=5;
  const CROWLER_NW=6;
  static private $_carrierIPs=array(
    self::DOCOMO_NW=>array(
      '210.153.84.0/24',
      '210.136.161.0/24',
      '210.153.86.0/24',
      '124.146.174.0/24',
      '124.146.175.0/24',
      '202.229.176.0/24',
      '202.229.177.0/24',
      '202.229.178.0/24',
      '202.229.179.0/24',
      '111.89.188.0/24',
      '111.89.189.0/24',
      '111.89.190.0/24',
      '111.89.191.0/24',
      '210.153.87.0/24',
      '203.138.180.0/24',
      '203.138.181.0/24',
      '203.138.203.0/24'
    ),
    self::AU_NW=>array(
      '210.230.128.224/28',
      '121.111.227.160/27',
      '61.117.1.0/28',
      '219.108.158.0/27',
      '219.125.146.0/28',
      '61.117.2.32/29',
      '61.117.2.40/29',
      '219.108.158.40/29',
      '219.125.148.0/25',
      '222.5.63.0/25',
      '222.5.63.128/25',
      '222.5.62.128/25',
      '59.135.38.128/25',
      '219.108.157.0/25',
      '219.125.145.0/25',
      '121.111.231.0/25',
      '121.111.227.0/25',
      '118.152.214.192/26',
      '118.159.131.0/25',
      '118.159.133.0/25',
      '118.159.132.160/27',
      '111.86.142.0/26',
      '111.86.141.64/26',
      '111.86.141.128/26',
      '111.86.141.192/26',
      '118.159.133.192/26',
      '111.86.143.192/27',
      '111.86.143.224/27',
      '111.86.147.0/27',
      '111.86.142.128/27',
      '111.86.142.160/27',
      '111.86.142.192/27',
      '111.86.142.224/27',
      '111.86.143.0/27',
      '111.86.143.32/27',
      '111.86.147.32/27',
      '111.86.147.64/27',
      '111.86.147.96/27',
      '111.86.147.128/27',
      '111.86.147.160/27',
      '111.86.147.192/27',
      '111.86.147.224/27',
      '111.107.116.0/26',
      '111.107.116.64/26',
      '111.107.116.192/28'
    ),
    self::SOFTBANK_NW=>array(
      '123.108.237.0/27',
      '202.253.96.224/27',
      '210.146.7.192/26',
      '210.175.1.128/25',
    ),
    self::SPMODE_NW=>array(
      '110.163.6.0/24',
      '110.163.7.0/24',
      '110.163.8.0/24',
      '110.163.9.0/24',
      '110.163.10.0/24',
      '110.163.11.0/24',
      '110.163.12.0/24',
      '110.163.13.0/25',
      '110.163.13.128/26',
      '110.163.13.192/27',
      '110.163.13.224/27',
      '1.72.0.0/16',
      '1.73.0.0/16',
      '1.74.0.0/16',
      '1.75.0.0/17',
      '1.75.128.0/18',
      '1.75.192.0/19',
      '1.76.0.0/19',
      '1.76.32.0/21',
      '1.76.40.0/21',
      '1.76.48.0/20',
      '1.76.64.0/20',
      '1.76.88.0/21',
      '1.76.96.0/19',
      '1.76.128.0/19',
      '1.76.160.0/21',
      '1.76.168.0/21',
      '1.76.176.0/20',
      '1.76.192.0/20',
      '1.76.216.0/21',
      '1.76.224.0/19',
      '1.77.0.0/19',
      '1.77.32.0/21',
      '1.77.40.0/21',
      '1.77.48.0/20',
      '1.77.64.0/20',
      '1.77.88.0/21',
      '1.77.96.0/19',
      '1.77.128.0/19',
      '1.77.160.0/21',
      '1.77.168.0/21',
      '1.77.176.0/20',
      '1.77.192.0/20',
      '1.77.216.0/21',
      '1.77.224.0/19',
      '1.78.64.0/18',
      '1.78.128.0/18',
      '1.78.192.0/18',
      '1.79.0.0/18',
      '1.79.64.0/18',
      '1.79.128.0/20',
      '1.79.144.0/20',
      '1.79.160.0/20',
      '1.79.192.0/20',
      '1.79.208.0/20',
      '110.163.216.0/24',
      '110.163.217.0/24',
      '110.163.218.0/23',
      '110.163.220.0/22',
      '110.163.224.0/22',
      '183.74.0.0/22',
      '183.74.4.0/24',
      '1.66.96.0/22',
      '1.66.100.0/24',
      '49.98.7.0/24',
      '49.98.8.0/22',
      '183.74.5.0/24',
      '183.74.6.0/23',
      '183.74.8.0/23',
      '1.66.101.0/24',
      '1.66.102.0/23',
      '1.66.104.0/23',
      '49.98.12.0/22',
      '49.98.16.0/24',
    ),
    self::INTERNAL_NW=>array(
    ),
    self::CROWLER_NW=>array(
      '72.14.199.0/25', // google
      '209.85.238.0/25',
      '66.249.65.209/32',
      '66.249.65.248/32',
      '66.249.68.0/24',
      '202.238.103.126/32', // moba
      '202.213.221.97/32',
      '210.150.10.32/27', // goo
      '203.131.250.0/24',
      '203.104.254.0/24', // livedoor
      '124.83.159.0/25', // yahoo
      '60.43.36.253/32',  // froute

      // Google 2010/07/05
      '66.102.0.0/16',
      '64.233.0.0/16',
      '216.239.0.0/16',
      '74.125.0.0/16',
      '66.249.64.0/20',
      '72.14.192.0/18',
      '209.85.128.0/17',

      // Yahoo 2010/03/02
      '124.83.159.0/22',

      //Yahoo!CrawlingServer
      '124.83.191.8',
      '124.83.191.9',
      '124.83.191.26',
      '124.83.191.27',
      '124.83.191.28',
      '124.83.191.29',
      '203.216.255.112',
      '203.216.255.113',
      '203.216.255.114',
      '203.216.255.115',
      '203.216.255.116',
      '203.216.255.117',

      // mobage
      '202.238.103.126',
      '202.213.221.97',

      // goo-mobile
      '203.131.248.0/21',
      '218.213.128.0/20',

      // F-Route
      '60.43.36.253',

      // Livedoor
      '203.104.254.0/24',

      // GREE Check
      '112.137.184.123'
    )
  );

  /*
   * IPアドレス判定
   */
  static public function getNetworkName($address)
  {
    require('Net/IPv4.php');
    foreach(self::$_carrierIPs as $category=>$masks)
    {
      foreach($masks as $mask)
      {
        if(Net_IPv4::ipInNetwork($address,$mask))
          return $category;
      }
    }
    return false;
  }
}
