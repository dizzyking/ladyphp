<?php /* Generated by LadyPHP */

# LadyPHP - type PHP with elegance
# ================================
# http://github.com/unu/ladyphp
# Unumin 2012 WTFPL

# -----------------------------------------------
# Lady
# parse LadyPHP to PHP code
# handle file cache
# -----------------------------------------------
class Lady{

  const HEAD = '/* Generated by LadyPHP */';
  const REGEX_CLASS = ';^([A-Z].*|self|parent)$;';
  const REGEX_VARIABLE =   ';^[_a-z].*$;';
  const REGEX_NOVARIABLE = ';^(false|true|self|parent|null)$;';
  const JOINING = '&& || & | -> . + - , / * % = ? :';
  const ENDING = '; { } ( [ <?';  # do not add ; { } after these
  const CONTINUING = ') ]';  # line is continuing if starts with these

  # ---------------------------------------------
  # register
  # bind LadyStream wrapper to lady://
  # ---------------------------------------------
  static function register($cacheDir = null){
    if ($cacheDir && !is_dir($cacheDir)){
      mkdir($cacheDir, 0755, true);}
    LadyStream::$cacheDir = realpath($cacheDir);
    if (in_array('lady', stream_get_wrappers())){
      stream_wrapper_unregister('lady');}
    return stream_wrapper_register('lady', 'LadyStream');}

  # ---------------------------------------------
  # parseFile
  # load file and parse it
  # ---------------------------------------------
  static function parseFile($file){
    return self::parse(file_get_contents($file));}

  # ---------------------------------------------
  # testFile
  # parse file and show input and output as html
  # ---------------------------------------------
  static function testFile($file){
    $td = '<td valign="top"><pre style="border:1px solid gray;background-color:#fff;'
       . 'width:30em;padding:.2em;color:black;font-size:13px;overflow:auto">';
    foreach ([file_get_contents($file), self::parseFile($file)] as $i => $text){
      $html = ($i == 0) ? '<b>' . $file . '</b><table><tr>' . $td : $html . '</pre></td>' . $td;
      foreach (explode("\n", $text) as $n => $line){
        $html .= sprintf("<span style=\"color:gray\">%3d</span> %s\n", $n, htmlspecialchars($line));}}
    return $html . '</pre></td></tr></table>';}

  # ---------------------------------------------
  # parse
  # convert LadyPHP from string to PHP code
  # ---------------------------------------------
  static function parse($source){
    $source = str_replace("\r", '', $source);
    $tokens = self::tokenize($source);
    $openingBracket = false;
    $closingBrackets = [];

    # process tokens
    foreach ($tokens as $n => $token){
      extract($token, EXTR_OVERWRITE | EXTR_REFS);

      # skip last dummy token
      if ($n > count($tokens) - 2){
        break;}

      # convert 'fn' to 'function'
      if ($str == 'fn'){
        $str = 'function';}

      # convert . to -> or ::
      elseif ($str == '.'
          && (!$hasBlank || !$tokens[$n + 1]['hasBlank'])){
        if (preg_match(self::REGEX_CLASS, $tokens[$n - 1]['str'])){
          $str = '::';}
        else{
          $str = '->';}}

      # convert : to =>
      elseif ($str == ':' && !$hasBlank && !$isLast){
        $str = ' =>';}

      # add $ before variables
      elseif ($type == T_STRING
          && $tokens[$n + 1]['str'] != '('
          && $tokens[$n - 1]['str'] != '->'
          && preg_match(self::REGEX_VARIABLE, $str)
          && !preg_match(self::REGEX_NOVARIABLE, $str)){
        $str = '$' . $str;}

      # add 'new' before 'Foo\Bar()'
      $i = 0;
      while ((($tokens[$n + $i]['type'] == T_STRING
          && preg_match(self::REGEX_CLASS, $tokens[$n + $i]['str']))
          || $tokens[$n + $i]['type'] == T_NS_SEPARATOR)
          && $hasBlank
          && (!$tokens[$n + $i]['hasBlank'] || $i == 0)
          && $tokens[$n - 1]['type'] != T_NEW){
        if ($tokens[$n + $i]['type'] == T_STRING
            && $tokens[$n + $i + 1]['str'] == '('){
          $str = 'new ' . $str;
          break;}
        $i++;}

      # add semicolon and brackets
      if ($isLast
          && !in_array($str, explode(' ', self::JOINING . ' ' . self::ENDING))
          && $type != T_CLOSE_TAG){

        # sort list of closing brackets
        $closingBrackets = array_unique($closingBrackets);
        rsort($closingBrackets);

        # switch block
        $isSwitch = false;
        $i = 0;
        while (isset($tokens[$n - $i]['y'])
            && $tokens[$n - $i]['y'] == $y){
          if (in_array($tokens[$n - $i]['type'], [T_CASE, T_DEFAULT])){
            $isSwitch = true;
            break;}
          $i++;}
        if ($isSwitch){
          $str .= ':';}

        # next line is indented
        elseif ($tokens[$n + 1]['indent'] > $indent){
          # add opening bracket
          if (!in_array($tokens[$n + 1]['str'], explode(' ', self::JOINING . ' ' . self::CONTINUING))){
            $str .= '{';
            $closingBrackets[] = $indent;
            $openingBracket = false;}
          # save opening bracket
          else{
            $openingBracket = $indent;}}

        # line doesn't continue
        elseif (!in_array($tokens[$n + 1]['str'], explode(' ', self::JOINING . ' ' . self::CONTINUING))){
          # there is saved opening bracket
          if ($openingBracket !== false
              && $tokens[$n + 1]['indent'] > $openingBracket){
            $str .= '{';
            $closingBrackets[] = $openingBracket;
            $openingBracket = false;}
          # add semicolon
          else{
            $str .= ';';}}

        # add closing brackets
        if ($indent > $tokens[$n + 1]['indent']){
          while (isset($closingBrackets[0])
              && $closingBrackets[0] >= $tokens[$n + 1]['indent']){
            $str .= '}';
            $closingBrackets = array_slice($closingBrackets, 1);}}}

      # convert php open tags
      if ($type == T_OPEN_TAG){
        $str = '<?php ';
        if ($y == 0){
          $str .= self::HEAD;}}
      if ($type == T_OPEN_TAG_WITH_ECHO){
        $str = '<?php echo ';}

      # save token
      $tokens[$n] = $token;}

    # glue code
    $code = null;
    foreach ($tokens as $token){
      $code .= $token['blank'] . $token['str'];}

    # return
    return $code;}

  # ---------------------------------------------
  # tokenize
  # ---------------------------------------------
  static function tokenize($source){
    $tokens = [];
    $blank = null;

    # prepare tokens
    foreach (token_get_all($source) as $n => $token){

      # convert to associative array
      if (is_array($token)){
        $token = ['str' => $token[1], 'type' => $token[0]];}
      else{
        $token = ['str' => $token, 'type' => null];}

      # save whitespaces and comments into tokens
      if (in_array($token['type'], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_INLINE_HTML])){
        $blank .= $token['str'];}
      else{
        $token['blank'] = $blank;
        $token['hasBlank'] = ($blank != null);
        $blank = null;
        $tokens[] = $token;}}

    # save remaining blank
    $tokens[] = ['str' => null, 'type' => null, 'blank' => $blank, 'isLast' => true];

    # get positions
    foreach ($tokens as $n => $token){
      $token['n'] = $n;
      if ($n == 0){
        $token['indent'] = $token['x'] = $token['y'] = 0;
        $token['isFirst'] = true;}
      else{
        $token['y'] = $tokens[$n - 1]['y'] + count(explode("\n", $tokens[$n - 1]['str'] . $token['blank'])) - 1;
        $token['isFirst'] = $tokens[$n - 1]['isLast'] = ($tokens[$n - 1]['y'] != $token['y']);
        $token['x'] = mb_strlen(array_slice(explode("\n", $token['blank']), -1)[0]);
        $token['x'] += !$token['isFirst'] ? $tokens[$n - 1]['x'] + mb_strlen($tokens[$n - 1]['str']) : null;
        $token['indent'] = $token['isFirst'] ? $token['x'] : $tokens[$n - 1]['indent'];}
      $tokens[$n] = $token;}

    return $tokens;}}

# -----------------------------------------------
# LadyStream
# stream wrapper for lady://
# -----------------------------------------------
class LadyStream{

  var $buffer, $position, $file, $cacheFile;
  static $cacheDir;

  # ---------------------------------------------
  # stream_open
  # ---------------------------------------------
  function stream_open($path){
    $this->position = 0;
    $this->file = stream_resolve_include_path(substr($path, 7));
    if (!self::$cacheDir){
      $this->buffer = Lady::parseFile($this->file);}
    else{
      if (!is_dir(self::$cacheDir)){
        mkdir(self::$cacheDir, 0755, true);}
      $this->cacheFile = self::$cacheDir
        . '/' . preg_replace(';/;', '_', $this->file)
        . '-' . md5($this->file) . '.php';
      if (!is_file($this->cacheFile) || filemtime($this->cacheFile) <= filemtime($this->file)){
        $this->buffer = Lady::parseFile($this->file);
        file_put_contents($this->cacheFile, $this->buffer);}
      else{
        $this->buffer = file_get_contents($this->cacheFile);}}
    return is_string($this->buffer);}

  # ---------------------------------------------
  # stream_read
  # ---------------------------------------------
  function stream_read($count){
    $this->position += $count;
    return substr($this->buffer, $this->position - $count, $count);}

  # ---------------------------------------------
  # stream_eof
  # ---------------------------------------------
  function stream_eof(){
    return $this->position >= strlen($this->buffer);}

  # ---------------------------------------------
  # stream_stat
  # ---------------------------------------------
  function stream_stat(){
    return ['size' => strlen($this->buffer), 'mode' => 0100644];}

  # ---------------------------------------------
  # url_stat
  # ---------------------------------------------
  function url_stat(){
    return $this->stream_stat();}}
