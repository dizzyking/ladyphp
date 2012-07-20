<?php

$lady = 'lady.lady';
$php = 'lady.php';
$newPhp = 'lady_new.php';

$compile = isset($_GET['compile']);
if ($compile){
  require($php);
  print '<div>Compiling <b>' . $lady . '</b> to <b>' . $newPhp . '</b>'.
        '<a href="?test">test it</a></div><pre>';
  file_put_contents($newPhp, Lady::parseFile($lady));
  print 'compiled';
}
else {
  if (is_file($newPhp)){
    require($newPhp);
    print '<div>Testing <b>' . $newPhp . '</b>'.
          '<a href="?compile">recompile</a>';
    if (file_get_contents($php) == file_get_contents($newPhp))
      print ' (same as ' . $php . ')';
    else 
      print ' (not same as ' . $php . ')';
    print '</div>';
    print Lady::test($lady);
  } else {
    print '<div>File <b>' . $newPhp . '</b> not found'.
          '<a href="?compile">recompile</a></div>';
  }
}
print '<style>
  pre {height: 90%; overflow: auto; width: 50%; float: left}
  div {background-color: #def; padding: .5em 1em}
  a {color: black; text-decoration: underline; font-weight: bold; margin: 0 2em; float: right}
  hr {display:none}
</style>';
