<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class EmojiFilter extends CFilter
{
  protected function preFilter($filterChain)
  {
    require('Emoji.php');

    // なんかダサいなー・・・
    Emoji::$isDocomo=$filterChain->controller->isDocomo;
    Emoji::$isAu=$filterChain->controller->isAu;
    Emoji::$isSoftbank=$filterChain->controller->isSoftbank;

    array_walk_recursive($_POST,array($this,'convert'));
    array_walk_recursive($_GET,array($this,'convert'));

    ob_start();
    $filterChain->run();
    $this->postFilter($filterChain);
  }

  protected function postFilter($filterChain)
  {
    $output=mb_convert_kana(Emoji::convert(ob_get_clean()),'k');
    if(!$filterChain->controller->isSoftbank)
      $output=mb_convert_encoding($output,'SJIS','UTF-8');
    if($filterChain->controller->isDocomo || $filterChain->controller->isAu)
      if(!$_SERVER['HTTP_X_DEBUG'])
        header('Content-Type: application/xhtml+xml; charset=Shift_JIS');
    echo Emoji::unescape(Emoji::unescape($output, true),false);
  }

  static function convert(&$val,$key)
  {
    if(mb_detect_encoding($val)!='ASCII')
    {
      $val=Emoji::escape($val);
      if(!Emoji::$isSoftbank)
        $val=mb_convert_encoding(mb_convert_kana($val,'KVa','SJIS-win'),'UTF-8','SJIS');
    }
  }
}
