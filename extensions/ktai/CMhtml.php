<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CMhtml
{
  public static function activeTextField($model,$attribute,$htmlOptions=array(),$mode)
  {    
    if (Yii::app()->controller->isMobile) {
      /*******************************
       * [メモ]
      Docomo,au　（istyle属性）
        istyle="1"  全角かな style="-wap-input-format:"*<ja:h>""
        istyle="2"  半角カナ style="-wap-input-format:"*<ja:hk>""
        istyle="3"  英字     style="-wap-input-format:"*<ja:en>""
        istyle="4"  数字     style="-wap-input-format:"*<ja:n>""  

      au　（format属性）
        format="*A" 半角英字（大文字）モード
        format="*a" 半角英字（小文字）モード  style="-wap-input-format:*m;"
        format="*N" 半角数字モード            style="-wap-input-format:*N;"
        format="*X" 半角英数（大文字）モード
        format="*x" 半角英数（小文字）モード
        format="*M" 全角かなモード            style="-wap-input-format:*M;"
        format="*m" 全角英字モード  

      Softbank　（mode属性）
        mode="hiragana" 全角かな      style="-wap-input-format:"*<ja:h>""
        mode="hankakukana"  半角カナ  style="-wap-input-format:"*<ja:hk>""
        mode="alphabet" 英字          style="-wap-input-format:"*<ja:en>""
        mode="numeric"  数字          style="-wap-input-format:"*<ja:n>""
        mode="katakana" 全角カナ
      **********************************/ 
      static $style=array(
        'attr'=>array('s'=>'mode', 'i'=>'istyle', 'e'=>'format'),
        'hiragana'=>array(
          's'=>array('val'=>'hiragana',    'style'=>'-wap-input-format:"*<ja:h>"'),
          'i'=>array('val'=>'1',           'style'=>'-wap-input-format:"*<ja:h>"'),
          'e'=>array('val'=>'*M',           'style'=>'-wap-input-format:*M;')),
        'hankakukana'=>array(
          's'=>array('val'=>'hankakukana', 'style'=>'-wap-input-format:"*<ja:hk>"'),
          'i'=>array('val'=>'2',           'style'=>'-wap-input-format:"*<ja:hk>"'),
          'e'=>array('val'=>'*M',           'style'=>'-wap-input-format:*M;')),
        'alphabet'=>array(
          's'=>array('val'=>'alphabet',    'style'=>'-wap-input-format:"*<ja:en>"'),
          'i'=>array('val'=>'3',           'style'=>'-wap-input-format:"*<ja:en>"'),
          'e'=>array('val'=>'*a',           'style'=>'-wap-input-format:*m;')),
        'numeric'=>array(
          's'=>array('val'=>'numeric',     'style'=>'-wap-input-format:"*<ja:n>"'),
          'i'=>array('val'=>'4',           'style'=>'-wap-input-format:"*<ja:n>"'),
          'e'=>array('val'=>'*N',           'style'=>'-wap-input-format:*N;')),
      );
      $c='';
      if (Yii::app()->controller->isDocomo) {
        $c='i';
      } else if (Yii::app()->controller->isSoftbank) {
        $c='s';
      } else if (Yii::app()->controller->isAu) {
        $c='e';
      }
      if (isset($style['attr'][$c])) {
        $htmlOptions[$style['attr'][$c]]=$style[$mode][$c]['val'];
        $htmlOptions['style']=$style[$mode][$c]['style'];
      }
    }
    return CHtml::activeTextField($model,$attribute,$htmlOptions);
  }
}
