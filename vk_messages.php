<?php
class VK_Messages {
	static function get($out = 0, $count = 20) {
		$query = VK::execQuery("messages.get", "out=$out&count=$count", true);
		$msgs = array();
		foreach ($query['response']['items'] as $item) {
			$msgs[] = new VK_Message($item);
		}
		return $msgs;
	}

	static function getDialogs($count = 20) {
		$query = VK::execQuery("messages.getDialogs", "count=$count", true);
		$dialogs = array();
		foreach ($query['response']['items'] as $item) {
			$dialogs[] = new VK_Dialog($item);
		}
		return $dialogs;
	}

	static function getHistory($peerID, $count = 20, $rev = 0) {
		$query = VK::execQuery("messages.getHistory", "peer_id=$peerID&count=$count&rev=$rev", true);
		$messages = array();
		foreach ($query['response']['items'] as $item) {
			$messages[] = new VK_Message($item);
		}
		return $messages;
	}

	static function send($peerID, $text) {
		$_text = urlencode(iconv('windows-1251', 'utf-8', $text));
		$query = VK::execQuery("messages.send", "peer_id=$peerID&message=$_text", true);
		if ($query['response']) return true;
		if ($query['error']) return new VK_Error($query['error']);
	}
}