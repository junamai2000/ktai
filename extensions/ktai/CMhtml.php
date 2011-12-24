<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CMhtml
{
  /**
   * モバイル用テキストフィールドの生成
   *
   * [テキスト入力モード]
   * Docomo,au(istyle属性)
   *  istyle="1"  全角かな style="-wap-input-format:"*<ja:h>""
   *  istyle="2"  半角カナ style="-wap-input-format:"*<ja:hk>""
   *  istyle="3"  英字     style="-wap-input-format:"*<ja:en>""
   *  istyle="4"  数字     style="-wap-input-format:"*<ja:n>""
   *
   * au(format属性)
   *  format="*A" 半角英字(大文字)モード
   *  format="*a" 半角英字(小文字)モード   style="-wap-input-format:*m;"
   *  format="*N" 半角数字モード           style="-wap-input-format:*N;"
   *  format="*X" 半角英数(大文字)モード
   *  format="*x" 半角英数(小文字)モード
   *  format="*M" 全角かなモード           style="-wap-input-format:*M;"
   *  format="*m" 全角英字モード  
   *
   * Softbank(mode属性)
   *  mode="hiragana" 全角かな      style="-wap-input-format:"*<ja:h>""
   *  mode="hankakukana"  半角カナ  style="-wap-input-format:"*<ja:hk>""
   *  mode="alphabet" 英字          style="-wap-input-format:"*<ja:en>""
   *  mode="numeric"  数字          style="-wap-input-format:"*<ja:n>""
   *  mode="katakana" 全角カナ
   *
   * @param CModel $model the data model
   * @param string $attribute the attribute
   * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
   * @param string $mode the text input mode
   * @return string the text field html
   */ 
  public static function activeTextField($model,$attribute,$htmlOptions=array(),$mode)
  {    
    if(Yii::app()->controller->isMobile)
    {
      static $style=array(
        'attr'=>array('s'=>'mode','i'=>'istyle','e'=>'format'),
        'hiragana'=>array(
          's'=>array('val'=>'hiragana',    'style'=>'-wap-input-format:"*<ja:h>"'),
          'i'=>array('val'=>'1',           'style'=>'-wap-input-format:"*<ja:h>"'),
          'e'=>array('val'=>'*M',          'style'=>'-wap-input-format:*M;')),
        'hankakukana'=>array(
          's'=>array('val'=>'hankakukana', 'style'=>'-wap-input-format:"*<ja:hk>"'),
          'i'=>array('val'=>'2',           'style'=>'-wap-input-format:"*<ja:hk>"'),
          'e'=>array('val'=>'*M',          'style'=>'-wap-input-format:*M;')),
        'alphabet'=>array(
          's'=>array('val'=>'alphabet',    'style'=>''),
          'i'=>array('val'=>'3',           'style'=>'-wap-input-format:"*<ja:en>"'),
          'e'=>array('val'=>'*x',          'style'=>'')),
        'numeric'=>array(
          's'=>array('val'=>'numeric',     'style'=>'-wap-input-format:"*<ja:n>"'),
          'i'=>array('val'=>'4',           'style'=>'-wap-input-format:"*<ja:n>"'),
          'e'=>array('val'=>'*N',          'style'=>'-wap-input-format:*N;')),
      );
      $c='';
      if(Yii::app()->controller->isDocomo)
        $c='i';
      else if(Yii::app()->controller->isSoftbank)
        $c='s';
      else if(Yii::app()->controller->isAu)
        $c='e';
      if(isset($style['attr'][$c]))
      {
        $htmlOptions[$style['attr'][$c]]=$style[$mode][$c]['val'];
        $htmlOptions['style']=((isset($htmlOptions['style']))?$htmlOptions['style']:'').$style[$mode][$c]['style'];
      }
    }
    return CHtml::activeTextField($model,$attribute,$htmlOptions);
  }

  /**
   * モバイル用プルダウンボックスの生成
   *
   * モバイルはsize指定するとリストをクリックしたとき
   * 指定した数のみ表示されるため指定させない
   *
   * @param CModel $model the data model
   * @param string $attribute the attribute
   * @param array $data data for generating the list options (value=>display)
   * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
   * @return string the generated list box
   */
  public static function activeListBox($model,$attribute,$data,$htmlOptions=array())
  {
    return CHtml::activeDropDownList($model,$attribute,$data,$htmlOptions);
  }

  /**
   * 罫線の生成
   *
   * @param integer $color hr color code
   * @param integer $top hr top margin
   * @param integer $bottom hr bottom margin
   * @param integer $width hr width
   * @param integer $height hr height
   * @return string customize hr tag
   */
  public function hr($color='#5a5a5a',$top=5,$bottom=5,$width='100%',$height=2)
  {
    if(Yii::app()->controller->isDocomo)
      return
        self::spacer($top).
        CHtml::tag('div',array('style'=>'background-color:'.$color.';'),CHtml::tag('img',array('src'=>self::SPACER,'width'=>$width,'height'=>$height))).
        self::spacer($bottom);
    else
      return
        CHtml::tag('hr',array('color'=>$color,'style'=>'width:'.$width.';height:'.$height.';margin:'.$top.' 0 '.$bottom.' 0;padding:0;background-color:'.$color.';border:0;'),false);
  }

  /**
   * ページ内アンカータグの生成
   *
   * id属性とname属性の両方を設定すると
   * 構文エラーとなるためキャリアで出しわけ
   *   docomo   id属性のみ
   *   au       name属性のみ
   *   softbank name属性のみ
   *
   * @param string $name the anchor attribute name
   * @return string anchor link tag
   */
  public function anchor($name)
  {
    $htmlOptions=array();
    if(Yii::app()->controller->isMobile)
    {
      $attr=array(
        'i'=>array('name'=>NULL,'id'=>$name),
        'e'=>array('name'=>$name,'id'=>NULL),
        's'=>array('name'=>$name,'id'=>NULL),
      );
      if(Yii::app()->controller->isDocomo)
        $htmlOptions=$attr['i'];
      else if(Yii::app()->controller->isAu)
        $htmlOptions=$attr['e'];
      else if(Yii::app()->controller->isSoftbank)
        $htmlOptions=$attr['s'];
    }
    return CHtml::link('','',$htmlOptions);
  }

  /**
   * マーキーの生成
   *
   * @param string @text content
   * @param string @color font color
   * @param string @bgcolor background color
   * @return string marquee tag
   */
  public function marquee($text,$color='#ffffff',$bgcolor='#f8a233')
  {
    return CHtml::tag('div',array('class'=>'marquee','style'=>'background-color:'.$bgcolor.';color:'.$color.';'),$text);
  }

  /**
   * 画像の回り込み対応タグの生成
   *
   * @param string @text content
   * @param string @src image url
   * @param string @alt image alt text
   * @return string float image and text tag
   */
  public function floatImage($text,$src,$alt='')
  {
    return
      CHtml::tag('div',array(),CHtml::image($src,$alt,array('align'=>'left','style'=>'float:left;')).$text).
      CHtml::tag('div',array('clear'=>'all','style'=>'clear:both;'));
  }

  /**
   * 省略文字列の生成(絵文字対応)
   *
   * [i:123] [e:123] [s:123]は2バイトとしてカウント
   *
   * @param string $string the target string
   * @param integer $length the truncate count
   * @param string $etc the following characters
   * @param array $options the etc options
   * @return string truncate string
   */
  public function str_truncate($string,$length=80,$etc='...',$options=array())
  {
    if($length==0)
      return '';
    // 絵文字は下駄文字に仮変換
    $tmpString=preg_replace('/\[[ies]:\d+\]/','〓',$string);
    if(mb_strlen($tmpString)>$length)
      return mb_substr($string,0,$length).$etc;
    else
      return $string;
  }
}
