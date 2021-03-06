var Lady = {};
// generated by script update-ladyjs.php
Lady.rules = {"parser":"(?:(?:<\\?php)|((?:^|\\?>)(?:[^<]|<(?:[^?]|$))*(?=<\\?|$))|(?:\\<\\<\\<'?(\\w+)'?\\n[\\s\\S]*?\\n\\2(?=[\\n;]))|(?:\"[^\"\\\\]*(?:\\\\[\\s\\S][^\"\\\\]*)*\"|'[^'\\\\]*(?:\\\\[\\s\\S][^'\\\\]*)*')|(?:\/\/|\\#)[^\\n]*(?=(?:\\n|$))|\/\\*(?:[^*]|\\*(?!\/))*\\*\/|(?:[a-zA-Z0-9_]\\w*))","structures":[["([^{}]*)([{}])","(^|[^NS\\s>.$]|[^-]>)[NS\\s]*F[NS\\s]*\\(","{","}B"],["([^{}]*)([{}])","(^|[^NS\\s>.$]|[^-]>)[NS\\s]*X[NS\\s]*$","{","}Z"],["([^()]*)([()])","(^|[^ZNS\\s>.$]|[^-]>)[NS\\s]*[WX][NS\\s]*$","(",")Z"]],"tokens":{"A":"case|default","D":"[0-9].*","E":"self","F":"function","G":"as","I":"['\"]_*[a-z][a-zA-Z0-9_]*['\"]","J":"and|extends|implements|instanceof|insteadof|x?or","K":"break|continue|die|end(?:declare|for(?:each)?|if|switch|while)|exit|false|null|return|true","L":"callable|class|clone|const|declare|echo|else|global|goto|include(?:_once)?|interface|new|print|private|require(?:_once)?|throw|trait|try|var|yield|array|binary|bool(?:ean)?|double|float|int(?:eger)?|object|real|string|unset","M":"private|protected|public|final|abstract","O":"namespace|use","P":"<\\?php","R":"parent","S":"[\/\\#][\\w\\W]*","T":"this","U":"static","W":"catch|elseif|for(?:each)?|if|switch|while","X":"do","V":"_*[a-z]\\w*|GLOBALS|_SERVER|_REQUEST|_POST|_GET|_FILES|_ENV|_COOKIE|_SESSION","Q":"['\"][\\w\\W]*","C":"_*[A-Z].*"},"dictionary":{"as":"G","case":"A","class":"[CERU]","eol":"(?:\\n|$)","eos":"[S\\s]*(\\n|$)(?![S\\s]*([\\])\\.\\-+:=\/%*&|>,\\{?GJ]|<[^?]))","function":"F","html":"H","key":"[AEFGJKLMOPRTUVWX]","keyword":"[AEFGJKLMOPRUWX]","leading":"[FGJLOWX]","methodprefix":"[MU][MSU\\s]*","noesc":"^|[^\\\\]","noprop":"^|[^>$\\\\]|[^-]>","ns":"[O\\\\]","phptag":"P","self":"E","space":"[NS\\s]","string":"[QI]","this":"T","var":"[TV]"},"toPhp":{"(noesc)@@":"$1self::","(noesc)@":"$1$this->","(ns,space*)var|var(space*\\\\)":"$1C$2","(class,space*as,space*)var":"$1C","(([$.]|->)keyword)":"$2V","(^|[,[(]space*)key(\\s?(=>|:))":"$1'I'$2","(^|[^\\?:LZS\\s\\\\]):(space)":"$1 =>$2","([^.])\\.(?![.=D])":"$1->","(class)->":"$1::","(noesc)~":"$1.","(noprop)(var(?!space*\\())":"$1$$2","([A-RT-Y\\]\\)\\-\\+]|[^\\{;S\\s]\\})(eos)":"$1;$2","(^space*|(?:noprop)leading|phptag|html);(space*eol)":"$1$2","(case[^\\n]*)\\s\\=>":"$1:","<\\?(?!p[h][p]\\b|=)":"<?php","(methodprefix)(var,space*\\()":"$1function $2","\\\\([~@$])":"$1"},"toLady":{"([@~])":"\\\\$1","(->|\\$)\\$":"$1\\\\$","(^|[,[(]space*)(\\$var,space*=>)":"$1\\\\$2","\\$this->":"@N","self::":"@@N","([^.])\\.(?![.=D])":"$1~","->":".","(class)::":"$1.","(noesc)\\$(var(?!space*\\())":"$1$2","\\$(keyword)":"$V","I(\\s?=>)":"Y$1","(^|[^S\\s])\\s?=>(\\s)":"$1:$2","(phptag)":"N<?","(methodprefix)function(?:space)(space*var)":"$1N$2","\\\\\\$":"$","(^|[^Z]space*);(space*eol)":"$1$2"}};

Lady.toPhp = function(input) {
  return Lady.convert(input, false);
};

Lady.toLady = function(input) {
  return Lady.convert(input, true);
};

Lady.convert = function(code, toLady) {
  var rules = Lady.rules[toLady ? 'toLady' : 'toPhp'];
  var values = [], brackets = [];
  var parser = new RegExp(Lady.rules.parser, 'g');
  code = code.toString().replace(parser, function (code, html, heredoc) {
    values.push(code);
    if (heredoc) return 'Q';
    if (html) return 'H';
    for (name in Lady.rules.tokens) {
      var pattern = new RegExp('^(' + Lady.rules.tokens[name] + ')$');
      if (code.match(pattern) !== null) return name;
    }
    return 'N';
  });
  for (var i in Lady.rules.structures) {
    struct = Lady.rules.structures[i];
    code = code.replace(new RegExp(struct[0], 'g'), function (token, code, bracket) {
      if (bracket == struct[2]) {
        brackets.push(code.match(new RegExp(struct[1])));
        return token;
      } else {
        return code + (brackets.pop() ? struct[3] : bracket);
      }
    });
  }
  for (var i in rules) {
    var pattern = i.replace(/([a-z]{2,}),?/g, function(s, id) {
      return Lady.rules.dictionary[id];
    });
    var replacement = rules[i].replace(/\\\$/, '$');
    code = code.replace(new RegExp(pattern, 'g'), function(x, a, b) {
      return replacement.replace(/\$1/, a ? a : '').replace(/\$2/, b ? b : '');
    });
  }
  return code.replace(/[A-Z]/g, function (n) {
    if (n == 'B' || n == 'Z') return '';
    var value = values.shift();
    return (n == 'N') ? '' : (n == 'Y' ? value.substring(1, value.length - 1) : value);
  });
};

if (typeof(module) !== 'undefined') {
  module.exports = Lady;
}

