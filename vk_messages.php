<?php
class VK_Messages {
	static function get($out = 0, $count = 20) {
		$query = VK::execQuery("messages.get", "out=$out&count=$count", true);
		if (VK::checkQuery($query)) {
			$msgs = array();
			foreach ($query['response']['items'] as $item) {
				$msgs[] = new VK_Message($item);
			}
			return $msgs;
		}
		else return $query;
	}

	static function getDialogs($count = 20) {
		$query = VK::execQuery("messages.getDialogs", "count=$count", true);
		if (VK::checkQuery($query)) {
			$dialogs = array();
			foreach ($query['response']['items'] as $item) {
				$dialogs[] = new VK_Dialog($item);
			}
			return $dialogs;
		}
		else return $query;
	}

	static function getHistory($peerID, $count = 20, $rev = 0) {
		$query = VK::execQuery("messages.getHistory", "peer_id=$peerID&count=$count&rev=$rev", true);
		if (VK::checkQuery($query)) {
			$messages = array();
			foreach ($query['response']['items'] as $item) {
				$messages[] = new VK_Message($item);
			}
			return $messages;
		}
		else return $query;
	}

	static function send($peerID, $text) {
		$_text = urlencode($text);
		$query = VK::execQuery("messages.send", "peer_id=$peerID&message=$_text", true);
		if (VK::checkQuery($query)) return $query['response'];
		else return $query;
	}
}