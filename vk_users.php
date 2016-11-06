<?php

class VK_Users {
	static function getUser($userID, $fields = array("online", "sex"), $nameCase = "nom", $asArray = false) {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("users.get", "user_ids=$userID&fields=$_fields&name_case=$nameCase", true);
		if (VK::checkQuery($query)) {
			if (!$asArray) return new VK_User($query['response'][0]);
			else return $query['response'][0];
		}
		else return $query;
	}

	static function getUsers($userIDs, $fields = array("online", "sex"), $nameCase = "nom") {
		$_userIDs = implode(",", $userIDs);
		$_fields = implode(",", $fields);
		$query = VK::execQuery("users.get", "user_ids=$_userIDs&fields=$_fields&name_case=$nameCase", true);
		if (VK::checkQuery($query)) {
			$users = array();
			foreach ($query['response'] as $item) {
				$users[] = new VK_User($item);
			}
			return $users;
		}
		else return $query;
	}

	static function getFollowers($userID, $count = 20, $fields = array("online", "sex"), $nameCase = "nom") {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("users.getFollowers", "user_id=$userID&count=$count&fields=$_fields&name_case=$nameCase", true);
		if (VK::checkQuery($query)) {
			$followers = array();
			foreach ($query['response']['items'] as $item) {
				$followers[] = new VK_User($item);
			}
			return $followers;
		}
		else return $query;
	}

	static function getSubscriptions($userID, $extended = 0, $count = 20, $fields = array("online", "sex")) {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("users.getSubscriptions", "user_id=$userID&count=$count&fields=$_fields&extended=$extended", true);
		if (VK::checkQuery($query)) {
			if ($extended) {
				$list = array();
				foreach ($query['response']['items'] as $item) {
					if ($item['type']) $list[] = new VK_Group($item);
					else $list[] = new VK_User($item);
				}
				return $list;
			}
			else {
				$groups = array();
				$users = array();
				foreach ($query['response']['groups']['items'] as $item) {
					$groups[] = new VK_Group($item);
				}
				foreach ($query['response']['users']['items'] as $item) {
					$users[] = new VK_User($item);
				}
				return array('users' => $users, 'groups' => $groups);
			}
		}
		else return $query;
	}
}