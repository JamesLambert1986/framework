<?php
 
class HtmlToAmp {
 
  private $html;
 
  /**
   * HtmlToAmp constructor.
   */
  public function __construct($htmlContent) {
 
    $this->html = $htmlContent;
  }
 
  public function getConvertedHtml() {
 
    return $this->ampify();
  }
 
  /**
   *
   * @
   */
  private function replaceTagsMain() {
    // Replacing all img, audio, iframe, video elements to amp custom elements.
    $this->html = str_ireplace(
            ['<html', 'https:', 'http:', '<img', '<video', '/video>', '<audio', '/audio>'], ['<html amp', '', '', '<amp-img', '<amp-video', '/amp-video>', '<amp-audio', '/amp-audio>'], $this->html
    );
  }
 
  private function replaceHeaderMetas() {
    // Content to replace header.
    $this->html = preg_replace('/<(meta|link) ((charset|property)=|(name|rel)=[\\"](viewport|canonical)[\\"])(.*?)>/', '', $this->html);
  }
 
  private function replaceHeaderMain() {
    // Content to replace header.
    $this->html = preg_replace(
            '/<head\/?>(.*?)<\/head>/', '<head>'
            . '<meta charset="utf-8">'
            . '<script async src="https://cdn.ampproject.org/v0.js"></script>'
            . '$1'
            . '</head>', $this->html);
  }
 
  private function replaceHeaderTags() {
    // Content to replace header.
    $this->html = preg_replace(
            '/<head\/?>(.*?)<\/head>/', '<head>'
            . '$1'
            . '<link rel="canonical" href="$SOME_URL" />'
            . '<meta name="viewport" content="width=device-width,minimum-scale=1">'
            . '</head>', $this->html);
  }
 
  private function replaceHeaderStyles() {
    // Content to replace header.
    $this->html = preg_replace(
            '/<head\/?>(.*?)<\/head>/', '<head>'
            . '$1'
            . '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>'
            . '<noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>'
            . '</head>', $this->html);
  }
 
  private function replaceBodyContent() {
 
    // Adding amp-img closing tag.
    $this->html = preg_replace('/<amp-img(.*?)\/?>/', '<amp-img layout="responsive"$1></amp-img>', $this->html);
  }
 
  protected function ampify() {
 
    // Replacing all img, audio, iframe, video elements to amp custom elements.
    $this->replaceTagsMain();
 
    // Content to replace header.
    $this->replaceHeaderMetas();
 
    // Content to replace header.
    $this->replaceHeaderMain();
 
    // Content to replace header.
    $this->replaceHeaderTags();
 
    // Content to replace header.
    $this->replaceHeaderStyles();
 
    // Adding amp-img closing tag.
    $this->replaceBodyContent();
 
    return $this->html;
  }
 
}
?>