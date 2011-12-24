<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
/**
 * EMailer class file.
 *
 * @author MetaYii
 * @version 2.2
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2009 MetaYii
 *
 * Copyright (C) 2009 MetaYii.
 *
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU Lesser General Public License as published by
 * 	the Free Software Foundation, either version 2.1 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU Lesser General Public License for more details.
 *
 * 	You should have received a copy of the GNU Lesser General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * For third party licenses and copyrights, please see phpmailer/LICENSE
 *
 */
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'phpmailer'.DIRECTORY_SEPARATOR.'class.phpmailer.php');

/*　送信方法サンプル
 * $mail = new PHPMailer();
 * $mail->CharSet = "iso-2022-jp";
 * $mail->Encoding = "7bit";
 * $mail->AddAddress($to);
 * $mail->From = $from;
 * $mail->FromName = mb_encode_mimeheader(mb_convert_encoding($fromname,"JIS","EUC-JP"));
 * $mail->Subject = mb_encode_mimeheader(mb_convert_encoding($subject,"JIS","EUC-JP"));
 * $mail->Body  = mb_convert_encoding($subject,"JIS","EUC-JP");
 */

class EMailer
{
  protected $pathViews = 'application.views.email';
  protected $pathLayouts = 'application.views.email.layouts';
  protected $internalEnc;
  private $_myMailer;
  private $_isMobile=false;
  
  public function init() {}
  
  public function __construct()
  {
    $this->_myMailer = new PHPMailer();
  }

  public function setPathLayouts($value)
  {
    if (!is_string($value) && !preg_match("/[a-z0-9\.]/i"))
      throw new CException(Yii::t('EMailer', 'pathLayouts must be a Yii alias path'));
    $this->pathLayouts = $value;
  }

  public function getPathLayouts()
  {
    return $this->pathLayouts;
  }

  public function setPathViews($value)
  {
    if (!is_string($value) && !preg_match("/[a-z0-9\.]/i"))
      throw new CException(Yii::t('EMailer', 'pathViews must be a Yii alias path'));
    $this->pathViews = $value;
  }

  public function getPathViews()
  {
    return $this->pathViews;
  }

  public function __call($method, $params)
  {
    if (is_object($this->_myMailer) && get_class($this->_myMailer)==='PHPMailer') return call_user_func_array(array($this->_myMailer, $method), $params);
    else throw new CException(Yii::t('EMailer', 'Can not call a method of a non existent object'));
  }

  public function __set($name, $value)
  {
    if (is_object($this->_myMailer) && get_class($this->_myMailer)==='PHPMailer') {
      $setter='set'.$name;
      if(method_exists($this,$setter))
        return $this->$setter($value);
      else
        $this->_myMailer->$name = $value;
    }
    else throw new CException(Yii::t('EMailer', 'Can not set a property of a non existent object'));
  }


  public function setSubject($value)
  {
    $this->_myMailer->Subject=mb_encode_mimeheader($value);
  }
  
  public function setBody($value)
  {
    $this->_myMailer->Body=mb_convert_encoding($value,"JIS",$this->internalEnc);
  }
  
  public function setFromName($value)
  {
    $this->_myMailer->FromName=mb_encode_mimeheader($value);
  }
  
  public function setLang($value)
  {
    mb_language($value);
  }
  
  public function setInternalEnc($value)
  {
    $this->internalEnc=$value;
    mb_internal_encoding($value);
  }
  public function AddAddress($address, $name = '')
  {
    // モバイルテンプレート使うときのため
    if (isset(Yii::app()->params['mobileDomain'])||is_array(Yii::app()->params['mobileDomain'])) {
      foreach (Yii::app()->params['mobileDomain'] as $i=>$k) {
        if (strpos($address, $k) !== false) {
          $this->_isMobile=true;
        }
      }
    }
    if (is_object($this->_myMailer) && get_class($this->_myMailer)==='PHPMailer') return $this->_myMailer->AddAddress($address, $name);
    else throw new CException(Yii::t('EMailer', 'Can not call a method of a non existent object'));
  }

  public function __get($name)
  {
    if (is_object($this->_myMailer) && get_class($this->_myMailer)==='PHPMailer') {
      $getter='get'.$name;
      if(method_exists($this,$getter))
        return $this->$getter($value);
      else
        return $this->_myMailer->$name;
    }
    else {
      throw new CException(Yii::t('EMailer', 'Can not access a property of a non existent object'));
    }
  }

  public function __sleep()
  {
  }

  public function __wakeup()
  {
  }

  public function getView($view, $vars = array(), $layout = null)
  {
    if ($this->_isMobile) {
      if (Yii::app()->controller->getViewFile('mobile.'.$view)) {
        $view = 'mobile.'.$view;
      }
    }
    $body = Yii::app()->controller->renderPartial($this->pathViews.'.'.$view, array_merge($vars, array('content'=>$this->_myMailer)), true);

    if ($layout === null) {
      $this->Body = $body;
    }
    else {
      $this->Body = Yii::app()->controller->renderPartial($this->pathLayouts.'.'.$layout, array('content'=>$body), true);
    }
  }
}
