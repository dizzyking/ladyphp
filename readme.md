
LadyPHP - type PHP with elegance
================================

Simple (and stupid) preprocessor for PHP. Main purpose of this is making source code a little more beautiful.

- optional `;` at end of line
- variables doesn't have to be prefixed with `$`, but they must start with a lowercase letter
- indent style, no need for `{` and `}`
- `.` is converted to `->` or `::`, but not if it's surrounded by spaces
- `:` is converted to `=>`, but only if there isn't space before it
- `fn foo()` is converted to `function foo()`
- `Foo\Bar()` is converted to `new Foo\Bar()`
- optional `:` after `case ...` and `default`
- `<?` and `<?=` are converted to `<?php` and `<?php echo`
- original line numbers are preserved (handy for debugging)
- Lady herself is written in Lady, use the source for reference

## Usage

    <?php
    require_once __DIR__ . '/lady.php';
    Lady::register(__DIR__ . '/tmp');
    require 'lady://' . __DIR__ . '/example.lady'

## Example

    <?                                         | <?php /* Generated by LadyPHP */
                                               |
    class Fruit                                | class Fruit{
      var apples = 0                           |   var $apples = 0;
      var numbers = [                          |   var $numbers = [
        1: 'one',                              |     1 => 'one',
        2: 'two',                              |     2 => 'two',
        3: 'three'                             |     3 => 'three'
      ]                                        |   ];
                                               |
      fn addApples(n = 1)                      |   function addApples($n = 1){
        if (n >= 0)                            |     if ($n >= 0){
          this.apples += n                     |       $this->apples += $n;}
        return this                            |     return $this;}
                                               |
      fn countApples()                         |   function countApples(){
        apples = this.apples                   |     $apples = $this->apples;
        out = 'You have '                      |     $out = 'You have ';
        out .= isset(this.numbers[apples])     |     $out .= isset($this->numbers[$apples])
               ? this.numbers[apples] : apples |            ? $this->numbers[$apples] : $apples;
        switch (apples)                        |     switch ($apples){
          case 1                               |       case 1:
            return out . ' apple.'             |         return $out . ' apple.';
          default                              |       default:
            return "$out apples."              |         return "$out apples.";}}}
                                               |
    fruit = Fruit()                            | $fruit = new Fruit();
    fruit.addApples(1)                         | $fruit->addApples(1)
         .addApples(2)                         |      ->addApples(2);
    ?>                                         | ?>
    <p><?=fruit.countApples()?></p>            | <p><?php echo $fruit->countApples()?></p>

#### Output

    <p>You have three apples.</p>

## API

### Lady::register()

    Lady::register(string $cacheDir = null)

Register `lady://` stream wrapper.

If `$cacheDir` is set, it is used as storage for cache files.

### Lady::parse()

    Lady::parse(string $source)

Convert LadyPHP from string and return PHP code.

### Lady::parseFile()

    Lady::parseFile(string $file)

Convert LadyPHP from file and return PHP code.

### Lady::testFile()

    Lady::testFile(string $file)

Parse file and return input and output as html.
