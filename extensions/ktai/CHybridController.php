<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CHybridController extends CController
{
  public $isMobile=false;
  public $isDocomo=false;
  public $isSoftbank=false;
  public $isAu=false;

  public function init()
  {
    $this->isMobile();
  }
  
  private function isMobile()
  {
    $userAgent = @$_SERVER['HTTP_USER_AGENT'];
    if ($this->isDocomo($userAgent)) {
      header('Content-Type: application/xhtml+xml; charset=Shift_JIS');
      $this->isMobile=true;
      $this->isDocomo=true;
    } else if ($this->isSoftbank($userAgent)) {
      $this->isMobile=true;
      $this->isSoftbank=true;
    } else if ($this->isAu($userAgent)) {
      $this->isMobile=true;
      $this->isAu=true;
    }
  }

  private function isDocomo($userAgent)
  {
    if (preg_match('!^DoCoMo!', $userAgent)) {
      return true;
    }
    return false;
  }
  
  private function isSoftbank($userAgent)
  {
    if (preg_match('!^SoftBank!', $userAgent)) {
      return true;
    } elseif (preg_match('!^Semulator!', $userAgent)) {
      return true;
    } elseif (preg_match('!^Vodafone!', $userAgent)) {
      return true;
    } elseif (preg_match('!^Vemulator!', $userAgent)) {
      return true;
    } elseif (preg_match('!^MOT-!', $userAgent)) {
      return true;
    } elseif (preg_match('!^MOTEMULATOR!', $userAgent)) {
      return true;
    } elseif (preg_match('!^J-PHONE!', $userAgent)) {
      return true;
    } elseif (preg_match('!^J-EMULATOR!', $userAgent)) {
      return true;
    }
    return false;
  }
  
  private function isAu($userAgent)
  {
    if (preg_match('!^KDDI-!', $userAgent)) {
      return true;
    } elseif (preg_match('!^UP\.Browser!', $userAgent)) {
      return true;
    }
    return false;
  }

  public function resolveViewFile($viewName,$viewPath,$basePath)
  {
    if (!$this->isMobile) {
      return parent::resolveViewFile($viewName,$viewPath,$basePath);
    }

    if(empty($viewName))
      return false;

    if(($renderer=Yii::app()->getViewRenderer())!==null)
      $extension=$renderer->fileExtension;
    else 
      $extension='.php';

    $prefix = $viewPath.DIRECTORY_SEPARATOR;
    $name = '';
    $suffix = $viewName . $extension;

    if ($this->isDocomo) {
      if (file_exists($prefix.'docomo/'.$suffix)) {
        $viewName='docomo/'.$viewName;
      }
    } else if ($this->isSoftbank) {
      if (file_exists($prefix.'softbank/'.$suffix)) {
        $viewName='softbank/'.$viewName;
      }
    } else if ($this->isAu) {
      if (file_exists($prefix.'au/'.$suffix)) {
        $viewName='au/'.$viewName;
      }
    }

    Yii::trace('[mobile view] '.$viewName, 'system.extensions.ktai');

    return parent::resolveViewFile($viewName,$viewPath,$basePath);
  }
}
