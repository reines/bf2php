<?php

class BF2RConBase {
	private $version;
	private $socket;

	public function __construct($ip, $port, $password) {
		if(!($this->socket = @fsockopen($ip, $port)))
			throw new Exception('Unable to connect to '.$ip.':'.$port);

		$this->version = $this->read(true);
		if(($result = $this->query('login '.md5(substr($this->read(true), 17).$password))) != 'Authentication successful, rcon ready.')
			throw new Exception($result);
	}

	protected function get_version() {
		return $this->version;
	}

	protected function query($line, $bare = false) {
		$this->write($line, $bare);
		if(strpos($result = $this->read($bare), 'rcon: unknown command:') === 0)
			throw new Exception($result);

		return $result;
	}

	protected function write($line, $bare = false) {
		fputs($this->socket, ($bare ? '' : "\x02").$line."\n");
	}

	protected function read($bare = false) {
		$delim = $bare ? "\n" : "\x04";
		for($buffer = '';($char = fgetc($this->socket)) != $delim;$buffer .= $char);

		return trim($buffer);
	}

	public function __destruct() {
		if ($this->socket)
			@fclose($this->socket);
	}

}
