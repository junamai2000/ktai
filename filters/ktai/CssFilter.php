<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CssFilter extends CFilter
{
  public $baseDir='./';
  protected function preFilter($filterChain)
  {
    if ($filterChain->controller->isDocomo) {
      Yii::import('application.vendors.*');
      ob_start();
      $filterChain->run();
      require('HTML/CSS/Mobile.php');
      $this->postFilter($filterChain);
    } else {
      $filterChain->run();
    }
  }

  protected function postFilter($filterChain)
  {
    try {
      echo HTML_CSS_Mobile::getInstance()->setBaseDir($this->baseDir)->setMode('strict')->apply(ob_get_clean());
    } catch (Exception $e) {
      throw new CHttpException(500,$e->getMessage());
    }
  }
}
