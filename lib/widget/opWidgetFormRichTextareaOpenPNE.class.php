<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opWidgetFormRichTextareaOpenPNE
 *
 * @package    OpenPNE
 * @subpackage widget
 * @author     Shogo Kawahara <kawahara@ejimaya.net>
 */
class opWidgetFormRichTextareaOpenPNE extends opWidgetFormRichTextarea
{
  static protected $firstRenderOpenPNE = true;

  protected $defaultTinyMCEConfigs = array(
    'mode'                            => 'textareas',
    'theme'                           => 'advanced',
    'editor_selector'                 => 'mceEditor_dummy_selector',
    'plugins'                         => 'openpne',
    'theme_advanced_toolbar_location' => 'top',
    'theme_advanced_toolbar_align'    => 'left',
    'theme_advanced_buttons1'         => 'op_b,op_u,op_s,op_i,op_large,op_small,op_color,op_image,op_emoji_docomo,op_emoji_au,op_emoji_softbank,op_cmd',
    'theme_advanced_buttons2'         => '',
    'theme_advanced_buttons3'         => '',
    'valid_elements'                  => 'b/strong,u,s/strike,i,font[color|size],br',
    'forced_root_block'               => false,
    'force_p_newlines'                => false,
    'force_br_newlines'               => true,
    'inline_styles'                   => false,
    'language'                        => 'ja',
    'entity_encoding'                 => 'raw',
    'remove_linebreaks'               => false,
    'custom_undo_redo_levels'         => 0,
    'custom_undo_redo'                => false,
  );

  static protected $defaultEnableButtons = array('op_b' , 'op_u', 'op_s', 'op_i', 'op_large', 'op_small', 'op_color', 'op_emoji_docomo');

  static protected $defaultButtonConfigs = array();

  static protected $defaultButtonOnclickActions = array('op_emoji_docomo' => "");

  static protected $htmlConvertList = array(
    'op:b' => array('b'),
    'op:u' => array('u'),
    'op:i' => array('i'),
    'op:s' => array('s'),
    'op:large' => array('font', array('size' => 5)),
    'op:small' => array('font', array('size' => 1)),
    'op:color' => array('font'),
  );

  public function configure($options = array(), $attributes = array())
  {
    $this->addOption('enable_button', self::$defaultEnableButtons);
    $this->addOption('button_config', self::$defaultButtonConfigs);

    parent::configure($options, $attributes);
  }

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    $js = '';

    foreach ($this->getOption('enable_button') as $buttonName)
    {
      $config[$buttonName] = array('isEnabled' => 1, 'imageURL' => image_path('deco_'.$buttonName.'.gif'));
    }
    $config = array_merge_recursive($config, $this->getOption('button_config'));

    if (self::$firstRenderOpenPNE)
    {
      sfProjectConfiguration::getActive()->loadHelpers('Asset');
      sfProjectConfiguration::getActive()->loadHelpers('Partial');
      sfContext::getInstance()->getResponse()->addJavascript('/sfProtoculousPlugin/js/prototype');
      sfContext::getInstance()->getResponse()->addJavascript('op_emoji');
      sfContext::getInstance()->getResponse()->addJavascript('Selection');
      sfContext::getInstance()->getResponse()->addJavascript('decoration');

      $relativeUrlRoot = sfContext::getInstance()->getRequest()->getRelativeUrlRoot();
      $js .= sprintf("function op_mce_editor_get_config() { return %s; }\n", json_encode($config));
      $js .= sprintf('function op_get_relative_uri_root() { return "%s"; }', $relativeUrlRoot);

      self::$firstRenderOpenPNE = false;
    }

    if ($js)
    {
      sfProjectConfiguration::getActive()->loadHelpers('Javascript');
      $js = javascript_tag($js);
    }

    $id = $this->getId($name, $attributes);
    $this->setOption('textarea_template', '<div id="'.$id.'_buttonmenu" class="'.$id.'">'
      .get_partial('global/richTextareaOpenPNEButton', array(
        'id' => $id,
        'configs' => $config,
        'onclick_actions' => self::$defaultButtonOnclickActions
      )).
      '</div>'.$this->getOption('textarea_template'));

    return $js.parent::render($name, $value, $attributes, $errors);
  }

 /**
  * original tag to html
  *
  * @param string  $string
  * @param boolean $isStrip          true if original tag is stripped from the string, false original tag convert html tag. 
  * @param boolean $isUseStylesheet
  */
  static public function toHtml($string, $isStrip, $isUseStylesheet)
  {
    $regexp = '/(&lt;|<)(\/?)(op:.+?)(?:\s+code=(&quot;|")(#[0-9a-f]{3,6})\4)?\s*(&gt;|>)/i';

    if ($isStrip)
    {
      $converted = preg_replace($regexp, '', $string);
    }
    else
    {
      if ($isUseStylesheet)
      {
        $converted = preg_replace_callback($regexp, 'opWidgetFormRichTextareaOpenPNE::toHtmlUseStylesheet', $string);
      }
      else
      {
        $converted = preg_replace_callback($regexp, 'opWidgetFormRichTextareaOpenPNE::toHtmlNoStylesheet', $string);
      }
    }

    return $converted;
  }

  static public function toHtmlUseStylesheet($matches)
  {
    $isEndtag = $matches[2];
    if ($isEndtag) {
        return '</span>';
    }

    $options = array();
    $tagname = strtolower($matches[3]);
    $colorcode = strtolower($matches[5]);
    $options['class'] = strtr($tagname, ':', '_');

    if ($tagname == 'op:color' && $colorcode) {
      $options['style'] = 'color:'.$colorcode;
    }

    return tag('span', $options, true);
  }

  static public function toHtmlNoStylesheet($matches)
  {
    $options = array();
    $isEndtag = $matches[2];
    $tagname = strtolower($matches[3]);
    $colorcode = strtolower($matches[5]);
    $classname = strtr($tagname, ':', '_');

    if (!array_key_exists($tagname, self::$htmlConvertList)) {
      return $value;
    }

    $htmlTagInfo = self::$htmlConvertList[$tagname];
    $htmlTagName = $htmlTagInfo[0];

    if ($isEndtag) {
      return '</' . $htmlTagName . '>';
    }

    if ($tagname == 'op:color' && $colorcode) {
      $options['color'] = $colorcode;
    }

    if (isset($htmlTagInfo[1]) && is_array($htmlTagInfo[1]))
    {
      $options = array_merge($options, $htmlTagInfo[1]);
    }

    return tag($htmlTagName, $options, true);
  }
}

