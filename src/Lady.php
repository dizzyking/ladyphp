<?php

class Lady {
  public static $rules = [
    'joiningKeywords' => 'and|as|extends|implements|instanceof|insteadof|x?or',
    'leadingKeywords' => '{joiningKeywords}|abstract|callable|case|catch|class
      |clone|const|declare|do|echo|else(?:if)?|final|for(?:each)?|function
      |global|goto|if|include(?:_once)?|interface|namespace|new|print|private
      |protected|public|require(?:_once)?|switch|throw|trait|try|use|var|while
      |yield|array|binary|bool(?:ean)?|double|float|int(?:eger)?|object|real
      |string|unset',
    'keywords' => '{leadingKeywords}|break|continue|default|end(?:declare|for
      (?:each)?|if|switch|while)?|false|null|parent|return|self|static|true',
    'methodPrefix' => '\\b(?:private|protected|public)(?:\\s+ static)?\\s+',
    'classId' => '(^|[^>$]|[^-]>) \\b(?:self|static|parent|[A-Z]\\w*|_+[A-Z])\\b',
    'varId' => '\\b (?:[a-z]\\w*|_+[a-z]\\w*|GLOBALS|_SERVER|_REQUEST|_POST|_GET
      |_FILES|_ENV|_COOKIE|_SESSION) \\b',
    'closure' => '(^|[^.$])\bfunction\b[\s\']*\(',
    'statementEnd' => '[\s\']* (\n|$)(?![\s\']*([\])\.\-\+:=/%*&|>,\{?]|<[^?]
      |({joiningKeywords})\b))',
    'toPhp' => [
      '\\$' => '\\\\$', // escape dollars
      '(^|[^\\\\]) @@' => '$1self::', // @@ to self
      '(^|[^\\\\]) @' => '$1$this->', // @ to $this
      '\\.([^.=0-9])' => '->$1', // dots to arrows
      '(^|[^\\\\]) ~' => '$1.', // tilde to single dot
      '({classId}) ->' => '$1::', // arrows to two colons
      '(^|[^>$\\\\]) ({varId} (?!\s*\\() )' => '$1$$2', // add dollars
      '(^|[^\\\\]) \\$ ({keywords}) \\b' => '$1$2', // remove dollars from keywords
      '([\w"\]\)\-+]|[^\{;\s]\})({statementEnd})' => '$1;$2', // add trailing semicolons
      '((?:^|[^$>]) \b (?:{leadingKeywords})) \; ([\s\']* (\n|$))' => '$1$2', // remove semicolons after leading keywords
      '<\\?\\$php;? \\b' => '<?php', // remove dollars from opening tags
      '(^|[^\\?:\\s\\\\]) : (\\s)' => '$1 =>$2', // colons to double arrows
      '(\\b (case|default) \\b [^\\n]*) \\s \\=>' => '$1:', // remove double arrows from cases
      '<\\? (?!php\\b|=)' => '<?php', // convert short opening tag to long tag
      '({methodPrefix}) ({varId} \\s*\\( )' => '$1function $2', // add functions
      '\\\\([~@$])' => '$1', // unescape @, tildes and dollars
    ],
    'toLady' => [
      '([@~])' => '\\\\$1', // escape @ and tildes
      '(->) \\$' => '$1\\\\$', // escape dollars before dynamic properties
      '\\$\\$' => '\\\\$\\\\$', // escape dollars before dynamic variables
      '\\$ ({keywords}) \\b' => '\\\\$$1', // escape dollars before keywords
      '\\$this->' => '@', // $this to @
      '\\b self::' => '@@', // self to @@
      '\\. (?![=0-9])' => '~', // dots to tilde
      '->' => '.', // arrows to dots
      '({classId}) ::' => '$1.', // double colons to dots
      '(^|[^\\\\]) \\$ ({varId} \\b (?!\\s*\\() )' => '$1$2', // remove dolars
      '(^|[^\\s]) \\s? => (\\s)' => '$1:$2', // double arrows to colons
      '<\\?php \\b' => '<?', //self::convert long opening tag to short tag
      '({methodPrefix}) function \\s+ ({varId} \\s*\\()' => '$1$2', // remove functions
      '\\\\ \\$' => '$', // unescape dollars before keywords
      ';([\s\']*\n)' => '$1', // remove trailing semicolons
    ],
    // patterns for inline html, strings and comments
    'tokens' => '(?: (?: (?:^|\\?>) (?:[^<]|<[^?])* (<\\?(?:php\\b)?)? )
      |(?: "[^"\\\\]*(?:\\\\[\\s\\S][^"\\\\]*)*" | \'[^\'\\\\]*(?:\\\\[\\s\\S][^\'\\\\]*)*\' )
      |(?: (?://|\#)[^\\n]*(?=\\n) | /\\* (?:[^*]|\\*(?!/))* \\*/) )'
  ];

  public static function toPhp($input){
    return self::convert($input, self::$rules['toPhp']);
  }

  public static function toLady($input){
    return self::convert($input, self::$rules['toLady']);
  }

  /**
   * Converts between php and ladyphp code.
   */
  protected static function convert($code, $rules) {
    $strings = [];
    $tokensPattern = sprintf('{%s}x', self::$rules['tokens']);
    $code = preg_replace_callback($tokensPattern, function ($m) use (&$strings) {
      $strings[] = isset($m[1]) ? substr($m[0], 0, -strlen($m[1])) : $m[0];
      return (preg_match('{^[\'"]}', $m[0]) ? '""' : "''") . (isset($m[1]) ? $m[1] : '');
    }, $code);
    $closureBrackets = [];
    $code = preg_replace_callback('/([^{}]*)([{}])/x', function ($m) use (&$closureBrackets) {
      if ($m[2] == '{') {
        $closureBrackets[] = preg_match('{'.self::$rules['closure'].'}x', $m[1]) == 1;
        return $m[0];
      } else {
        return $m[1] . (array_pop($closureBrackets) ? '"}"' : '}');
      }
    }, $code);
    $patterns = array_keys($rules);
    foreach ($patterns as &$pattern) {
      while (preg_match('~{(\w+)}~', $pattern)) {
        $pattern = preg_replace_callback('~{(\w+)}~', function ($m) {
          return self::$rules[$m[1]];
        }, $pattern);
      }
    }
    $patterns = preg_replace('{^.*$}s', '{\0}x', $patterns);
    $code = preg_replace($patterns, $rules, $code);
    $code = preg_replace('{"\}"}', '}', $code);
    return preg_replace_callback('{""|\'\'}', function ($m) use (&$strings) {
      return array_shift($strings);
    }, $code);
  }
}
