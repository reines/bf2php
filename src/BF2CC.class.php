<?php

require dirname(__FILE__).'/BF2RCon.class.php';

class BF2CC extends BF2RCon {
	const NAME = 'BF2PHP';

	public function __construct($ip, $port, $password) {
		parent::__construct($ip, $port, $password);

		if(($result = $this->query('bf2cc setadminname '.BF2CC::NAME)) != 'Admin name set to '.BF2CC::NAME)
			throw new Exception($result);
	}

	public function send_switch_player($id) {
		return trim($this->query('bf2cc switchplayer '.$id.' 0'));
	}

	public function send_server_chat($message) {
		return trim($this->query('bf2cc sendserverchat '.$message));
	}

	public function get_chat_buffer() {
		$result = explode("\r\r", $this->query('bf2cc serverchatbuffer'));

		$chats = array();
		foreach($result as $chat) {
			list(, $origin, , $type, $time, $message) = explode("\t", $chat);

			$chats[] = array(
				'origin'	=>	$origin,
				'type'		=>	$type,
				'time'		=>	substr($time, 1, -1),
				'message'	=>	$message,
			);
		}

		return $chats;
	}

	public function get_server_details() {
		$result = explode("\t", $this->query('bf2cc si'));

		$details = array(
			'server'		=>	array(
				'name'			=>	$result[7],
				'mod'			=>	$result[21],
				'ranked'		=>	$result[25] == '1',
				'autobalance'		=>	$result[24] == '1',
				'reserved'		=>	$result[29],
			),
			'map'			=>	array(
				'name'			=>	$result[5],
				'mode'			=>	$result[20],
			),
			'players'		=>	array(
				'current'		=>	$result[3],
				'maximum'		=>	$result[2],
				'joining'		=>	$result[4],
			),
			'team'			=>	array(
				0 => array(
					'name'		=>	$result[8],
					'tickets'	=>	$result[11],
					'size'		=>	$result[26],
				),
				1 => array(
					'name'		=>	$result[13],
					'tickets'	=>	$result[16],
					'size'		=>	$result[27],
				),
			),
			'time'			=>	array(
				'elapsed'		=>	$result[18],
			),
		);

		if($result[19] != '-1')
			$details['time']['remaining'] = $result[19];

		return $details;
	}

	public function get_player_list() {
		if(!($result = $this->query('bf2cc pl')))
			return array();

		$result = explode("\r", $result);

		$players = array();
		foreach($result as $player) {
			$elements = explode("\t", $player);

			$details = array(
				'profile'	=>	array(
					'pid'				=>	$elements[10],
					'rank'				=>	$elements[39],
					'key'				=>	$elements[42],
				),
				'round'		=>	array(
					'score'		=>	array(
						'damage_assists'		=>	$elements[19],
						'passenger_assists'		=>	$elements[20],
						'target_assists'		=>	$elements[21],
						'revives'			=>	$elements[22],
						'team_damages'			=>	$elements[23],
						'team_vehicle_damages'		=>	$elements[24],
						'cp_captures'			=>	$elements[25],
						'cp_defends'			=>	$elements[26],
						'cp_assists'			=>	$elements[27],
						'cp_neutralizes'		=>	$elements[28],
						'cp_neutralizes_assists'	=>	$elements[29],
						'suicides'			=>	$elements[30],
						'kills'				=>	$elements[31],
						'team_kills'			=>	$elements[32],
						'deaths'			=>	$elements[36],
						'score'				=>	$elements[37],
					),
					'team_id'	=>	$elements[2],
					'alive'		=>	$elements[8] == '1',
					'kit_type'	=>	$elements[34],
					'camera'	=>	$elements[38],
				),
				'ping'		=>	$elements[3],
				'joining'	=>	$elements[4] == '0',
				'ip'		=>	$elements[18],
				'idle_time'	=>	$elements[41],
			);

			$name = explode(' ', $elements[1]);
			if(isset($name[1])) {
				$details['profile']['name'] = $name[1];
				$details['profile']['tag'] = $name[0];
			}
			else
				$details['profile']['name'] = $name[0];

			if($elements[13] > 0)
				$details['round']['spawn_time'] = intval($elements[13]);

			if($elements[16])
				$details['round']['team_status'] = 3;
			else if($elements[15]) {
				$details['round']['squad_id'] = $elements[14];
				$details['round']['team_status'] = 2;
			}
			else if($elements[14]) {
				$details['round']['squad_id'] = $elements[14];
				$details['round']['team_status'] = 1;
			}
			else
				$details['round']['team_status'] = 0;

			$players[$elements[0]] = $details;
		}

		return $players;
	}
}
