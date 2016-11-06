<?php

class VK_Friends {
	static function get($userID = null, $count = 20, $fields = array("online", "sex"), $order = "hints", $nameCase = "nom") {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("friends.get", "user_id=$userID&order=$order&count=$count&fields=$_fields&name_case=$nameCase", true);
		if (VK::checkQuery($query)) {
			$friends = array();
			foreach ($query['response']['items'] as $item) {
				$friends[] = new VK_User($item);
			}
			return $friends;
		}
		else return $query;
	}

	static function add($userID, $text = "", $follow = null) {
		$_text = urlencode(iconv('windows-1251', 'utf-8', $text));
		$query = VK::execQuery("friends.add", "user_id=$userID&text=$_text&follow=$follow", true);
		if (VK::checkQuery($query)) {
			$result = $query['response'];
			return $result;
		}
		else return $query;
	}

	static function check($userIDs, $needSign = 0) {
		$_userIDs = implode(",", $userIDs);
		$query = VK::execQuery("friends.areFriends", "user_ids=$_userIDs&need_sign=$needSign", true);
		if (VK::checkQuery($query)) {
			$statuses = array();
			foreach ($query['response'] as $status) {
				$statuses[] = new VK_FriendStatus($status);
			}
			return $statuses;
		}
		else return $query;
	}

	static function delete($userID) {
		$query = VK::execQuery("friends.delete", "user_id=$userID", true);
		if (VK::checkQuery($query)) {
			return new VK_FriendDelete($query['response']);
		}
		else return $query;
	}

	static function deleteAllRequests() {
		$query = VK::execQuery("friends.deleteAllRequests", "", true);
		if (VK::checkQuery($query)) {
			return $query['response'];
		}
		else return $query;
	}
}