<?php /* Generated by LadyPHP */

/**
 * LadyPHP - type PHP with elegance
 * http://github.com/unu/ladyphp
 * Unumin 2012 WTFPL
 */

/**
 * Converts LadyPHP code to PHP and works as stream wrapper.
 */
class Lady{

  const HEAD = '/* Generated by LadyPHP */';
  const REGEX_CLASS = ';^([A-Z].*|self|parent)$;';
  const REGEX_VARIABLE =   ';^[_a-z].*$;';
  const REGEX_NOVARIABLE = ';^(false|true|self|parent|null)$;';
  const JOINING = '&& || & | -> . + - , / * % = ? :';
  const ENDING = ' ; { } ( array( [ <?';  # do not add ; { } after these
  const CONTINUING = ') ]';  # line is continuing if starts with these

  var $buffer, $position, $filename, $cacheFile;
  static $cacheDir;

  /**
   * Register class as wrapper for lady://.
   * @param string
   * @return bool
   */
  static function register($cacheDir = null){
    if ($cacheDir){
      if (!is_dir($cacheDir)){
        mkdir($cacheDir, 0755, true);}
      self::$cacheDir = realpath($cacheDir);}
    if (in_array('lady', stream_get_wrappers())){
      stream_wrapper_unregister('lady');}
    return stream_wrapper_register('lady', __CLASS__);}

  /**
   * Loads file and parses it.
   * @param string  filename
   * @return string  PHP code
   */
  static function parseFile($filename, $expanded = false){
    return self::parse(file_get_contents($filename), $expanded);}

  /**
   * Parses file and returns input and output as html.
   * @param string  filename
   * @return string  PHP code
   */
  static function testFile($filename, $expanded = false){
    $html = '<div class="ladyTest"><p><b>' . basename($filename) . '</b> (hover to show PHP)</p><div>';
    foreach (array(file_get_contents($filename), self::parseFile($filename, $expanded)) as $i => $text){
      $html .= ($i == 0) ? '<pre>' : '</pre><pre>';
      foreach (explode("\n", $text) as $n => $line){
        $html .= sprintf("<span>%3d</span> %s\n", $n, htmlspecialchars($line));}}
    return $html . '</pre></div></div><style>'
      . '.ladyTest div {position:relative;border:1px solid #aaa;font-size:13px;overflow:auto}'
      . '.ladyTest pre {background:#fff;color:#222}'
      . '.ladyTest pre:last-child {position:absolute;display:none;top:0;left:0}'
      . '.ladyTest div:hover pre:last-child {display:block}'
      . '.ladyTest p {color:#888;font-size:14px}'
      . '.ladyTest p b {color:#000}'
      . '.ladyTest span {color:#aaa}</style>';}

  /**
   * Parses LadyPHP from string to PHP code
   * @param string
   * @return string  PHP code
   */
  static function parse($source, $expanded = false){
    $source = str_replace("\r", '', $source);
    $tokens = self::tokenize($source);
    $openingBracket = false;
    $closingBrackets = array();
    $closingIndentStr = array();
    $squareBrackets = 0;
    $arrayBrackets = array();

    # process tokens
    foreach ($tokens as $n => $token){
      $token = $tokens[$n];
      extract($token, EXTR_OVERWRITE | EXTR_REFS);

      # skip last dummy token
      if ($n > count($tokens) - 2){
        break;}

      # square brackets to array()
      elseif ($str == '['){
        $squareBrackets++;
        if ($hasBlank || in_array($tokens[$n - 1]['str'], explode(' ', self::JOINING . ' ' . self::ENDING))){
          $str = 'array(';
          $arrayBrackets[$squareBrackets] = true;}}

      elseif ($str == ']'){
        if (isset($arrayBrackets[$squareBrackets]) && $arrayBrackets[$squareBrackets]){
          $str = ')';
          $arrayBrackets[$squareBrackets] = false;}
        $squareBrackets--;}

      # convert 'fn' to 'function'
      elseif ($str == 'fn'){
        $str = 'function';}

      # convert . to -> or :: and .. to .
      elseif ($str == '.'){
        if ($tokens[$n + 1]['str'] == '.'){
          $tokens[$n + 1]['str'] = '';}
        elseif (!$hasBlank || !$tokens[$n + 1]['hasBlank']){
          if (preg_match(self::REGEX_CLASS, $tokens[$n - 1]['str'])){
            $str = '::';}
          else{
            $str = '->';}}}

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
      if ($isLast && $type != T_CLOSE_TAG
          && !in_array($str, explode(' ', self::JOINING . ' ' . self::ENDING))){

        # sort list of closing brackets
        $closingBrackets = array_unique($closingBrackets);
        rsort($closingBrackets);

        # switch block
        $isSwitch = false;
        $i = 0;
        while (isset($tokens[$n - $i]['y'])
            && $tokens[$n - $i]['y'] == $y){
          if (in_array($tokens[$n - $i]['type'], array(T_CASE, T_DEFAULT))){
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
            $closingIndentStr[$indent] = $indentStr;
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
            if ($expanded){
              $str .= "\n" . $closingIndentStr[$closingBrackets[0]];}
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

  /**
   * Tokenizes source code and extends tokens.
   * @param string
   * @return array
   */
  static function tokenize($source){
    $tokens = array();
    $blank = null;

    # prepare tokens
    foreach (token_get_all($source) as $n => $token){

      # convert to associative array
      if (is_array($token)){
        $token = array('str' => $token[1], 'type' => $token[0]);}
      else{
        $token = array('str' => $token, 'type' => null);}

      # save whitespaces and comments into tokens
      if (in_array($token['type'], array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_INLINE_HTML))){
        $blank .= $token['str'];}
      else{
        $token['blank'] = $blank;
        $token['hasBlank'] = ($blank != null);
        $blank = null;
        $tokens[] = $token;}}

    # save remaining blank
    $tokens[] = array('str' => null, 'type' => null, 'blank' => $blank, 'isLast' => true);

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
        $token['indent'] = $token['isFirst'] ? $token['x'] : $tokens[$n - 1]['indent'];
        $token['indentStr'] = $token['isFirst'] ? array_slice(explode("\n", $token['blank']), -1)[0] : $tokens[$n - 1]['indentStr'];}
      $tokens[$n] = $token;}

    return $tokens;}

  /**
   * Opens file and uses cacheDir if is set.
   * @param string
   * @return bool
   */
  function stream_open($path){
    $this->position = 0;
    $this->filename = stream_resolve_include_path(substr($path, 7));
    if (!$this->filename){
      return false;}
    if (!self::$cacheDir){
      $this->buffer = self::parseFile($this->filename);}
    else{
      if (!is_dir(self::$cacheDir)){
        mkdir(self::$cacheDir, 0755, true);}
      $this->cacheFile = self::$cacheDir
        . '/' . preg_replace(';/;', '_', $this->filename)
        . '-' . md5($this->filename) . '.php';
      if (!is_file($this->cacheFile) || filemtime($this->cacheFile) <= filemtime($this->filename)){
        $this->buffer = self::parseFile($this->filename);
        file_put_contents($this->cacheFile, $this->buffer);}
      else{
        $this->buffer = file_get_contents($this->cacheFile);}}
    return is_string($this->buffer);}

  /**
   * @param int
   * @return string
   */
  function stream_read($count){
    $this->position += $count;
    return substr($this->buffer, $this->position - $count, $count);}

  /**
   * @return bool
   */
  function stream_eof(){
    return $this->position >= strlen($this->buffer);}

  /**
   * @return array
   */
  function stream_stat(){
    return array('size' => strlen($this->buffer), 'mode' => 0100644);}

  /**
   * @return array
   */
  function url_stat(){
    return $this->stream_stat();}}


/**
 * Parses file from first command line argument.
 * If first argument is '-e', use expanded syntax.
 */
if (isset($argv[1]) && realpath($argv[0]) == realpath(__FILE__)){
  if (isset($argv[2]) && $argv[1] == '-e'){
    echo Lady::parseFile($argv[2], true);}
  else{
    echo Lady::parseFile($argv[1]);}}
