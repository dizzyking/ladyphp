<?php

/**
 * Converts LadyPHP code to PHP and works as stream wrapper.
 */
class LadyLoader {

	var $buffer, $position, $filename, $cacheFile;
	static $head = '/* Generated by LadyPHP */';
	static $cacheDir;

	/**
	 * Converts and requires lady file.
	 * @param string Filename
	 * @param bool Require file once
	 * @return mixed Return value of required file
	 */
	static function requireFile($filename, $once = false){
		if (!is_file($filename)) {
			throw new ErrorException('Required file ' . $filename . ' not found.');
		}
		stream_wrapper_unregister('file');
		stream_wrapper_register('file', __CLASS__);
		$result = $once ? require_once($filename) : require($filename);
		stream_wrapper_restore('file');
		return $result;
  }

	/**
	 * Opens file and uses cache.
	 * @param string Filename
	 * @return bool File was loaded
	 */
	function stream_open($filename) {
		stream_wrapper_restore('file');
		$this->filename = realpath($filename);
		$this->position = 0;
		if (!self::$cacheDir) {
			self::$cacheDir = sys_get_temp_dir() . '/ladyphp-' . sha1(realpath(__FILE__));
    }
		if (!is_dir(self::$cacheDir)) {
			mkdir(self::$cacheDir, 0755, true);
    }
		$this->cacheFile = self::$cacheDir . '/' . sha1($this->filename) . '.php';
		if (!is_file($this->cacheFile) || filemtime($this->cacheFile) <= filemtime($this->filename)) {
			$this->buffer = Lady::toPhp(file_get_contents($this->filename));
			file_put_contents($this->cacheFile, $this->buffer);
    }
		else {
			$this->buffer = file_get_contents($this->cacheFile);
    }
		return is_string($this->buffer);
  }

	/**
	 * Read bytes from file.
	 * @param int
	 * @return string
	 */
	function stream_read($count){
		$this->position += $count;
		return substr($this->buffer, $this->position - $count, $count);
  }

	/**
	 * Returns true if file pionter is at EOF.
	 * @return bool
	 */
	function stream_eof() {
		return $this->position >= strlen($this->buffer);
  }

	/**
	 * Returns info about file.
	 * @return array
	 */
	function stream_stat() {
		return array('size' => strlen($this->buffer), 'mode' => 0100644);
  }

	/**
	 * Returns info about file.
	 * @return array
	 */
	function url_stat() {
		return $this->stream_stat();
  }
}

/**
 * Shortcut functions
 */
if (!function_exists('lady')) {
	function lady($filename) {
		return LadyLoader::requireFile($filename);
  }
}
if (!function_exists('lady_once')) {
	function lady_once($filename) {
		return LadyLoader::requireFile($filename, true);
  }
}