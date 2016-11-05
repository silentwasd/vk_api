<?php
class VK_Friends {
	static function get($userID = null, $count = 20, $fields = array("online", "sex"), $order = "hints", $nameCase = "nom") {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("friends.get", "user_id=$userID&order=$order&count=$count&fields=$_fields&name_case=$nameCase", true);
		$friends = array();
		foreach ($query['response']['items'] as $item) {
			$friends[] = new VK_User($item);
		}
		return $friends;
	}
}