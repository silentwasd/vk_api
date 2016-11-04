<?php

class VK {
	static public $appID; // Идентификатор приложения
	static public $redirectURI = "https://oauth.vk.com/blank.html"; // Адрес переадресации
	static public $scope; // Права доступа
	static public $access_token; // Ключ доступа
	static private $apiURL = "https://api.vk.com/method/"; // URL запросов к API

	static function showAuth($form, $browser) {
		$form->show();
		$browser->url = "https://oauth.vk.com/authorize?client_id=" . self::$appID . "&redirect_uri=" . self::$redirectURI . "&display=popup&scope=" . self::$scope . "&response_type=token&v=5.60&state=&revoke=1";
	}

	static function execQuery($method, $params, $toArray=false) {
		$url = self::$apiURL . "$method?$params&access_token=" . self::$access_token . "&v=5.60";
		clipboard_setText($url);

		$result = file_get_contents($url);
		$win1251 = iconv("utf-8", "windows-1251", $result);
		if ($toArray) return self::jsondecode($result);
		else return $win1251;
	}

	static function getFriends($userID="", $fields="sex", $order="hints", $nameCase="nom", $count="") {
		$friends = self::execQuery("friends.get", "user_id=$userID&order=$order&count=$count&fields=$fields&name_case=$nameCase", true);
		$_friends = array();
		foreach ($friends['response']['items'] as $friend) {
			$_friend = new VK_User();
			$_friend->syncWithJS($friend);
			$_friends[] = $_friend;
		}
		return $_friends;
	}

	static function getMessages($out=0, $count=20) {
		$query = self::execQuery("messages.get", "out=$out&count=$count", true);
		$msgs = array();
		foreach ($query['response']['items'] as $item) {
			$msg = new VK_Message();
			$msg->syncWithJS($item);
			$msgs[] = $msg;
		}
		return $msgs;
	}

	static function getDialogs($count=20) {
		$query = self::execQuery("messages.getDialogs", "count=$count", true);
		$dialogs = array();
		foreach ($query['response']['items'] as $item) {
			$dialog = new VK_Dialog();
			$dialog->syncWithJS($item);

			$dialogs[] = $dialog;
		}
		return $dialogs;
	}

	static function getDialogMessages($peerID, $count=20, $rev=0) {
		$query = self::execQuery("messages.getHistory", "peer_id=$peerID&count=$count&rev=$rev", true);
		$messages = array();
		foreach ($query['response']['items'] as $item) {
			$msg = new VK_Message();
			$msg->syncWithJS($item);

			$messages[] = $msg;
		}
		return $messages;
	}

	static function getUser($userID, $fields="online,sex", $nameCase="nom") {
		$query = self::execQuery("users.get", "user_ids=$userID&fields=$fields&name_case=$nameCase", true);
		$user = new VK_User();
		$user->syncWithJS($query['response'][0]);
		return $user;
	}

	static function sendMessage($peerID, $text) {
		$_text = urlencode(iconv('windows-1251', 'utf-8', $text));
		$query = self::execQuery("messages.send", "peer_id=$peerID&message=$_text");
		if ($query['response']) return true;
		if ($query['error']) return $query['error']['error_code'];
	}

	static function jsondecode($sText) {
	    if (!$sText) return false;
	    $aJson = json_decode($sText, true);
	    $aJson = self::iconvarray($aJson);
	    return $aJson;
	}

	static function iconvarray($aJson) {
		$_aJson = $aJson;
	    foreach ($_aJson as $key => $value) {
	        if (is_array($value)) {
	            $_aJson[$key] = self::iconvarray($value);
	        } else {
	            $_aJson[$key] = iconv('utf-8', 'windows-1251//IGNORE', $value);
	        }
	    }
	    return $_aJson;
	}
}

class VK_User {
	public $id;
	public $name;
	public $lastName;
	public $online;
	public $sex;

	function syncWithJS($js_array) {
		$this->id = $js_array['id'];
		$this->name = $js_array['first_name'];
		$this->lastName = $js_array['last_name'];
		$this->online = $js_array['online'];
		$this->sex = $js_array['sex'];
	}
}

class VK_Message {
	public $id;
	public $date;
	public $out;
	public $userID;
	public $fromID;
	public $readState;
	public $title;
	public $text;
	public $chatID;

	function syncWithJS($js_array) {
		$this->id = $js_array['id'];
		$this->date = $js_array['date'];
		$this->out = $js_array['out'];
		$this->userID = $js_array['user_id'];
		$this->fromID = $js_array['from_id'];
		$this->readState = $js_array['read_state'];
		$this->title = $js_array['title'];
		$this->text = $js_array['body'];
		$this->chatID = $js_array['chat_id'];
	}

	function getPeerID() {
		if ($this->chatID) $peerID = 2000000000 + $this->chatID;
		else $peerID = $this->userID;
		return $peerID;
	}
}

class VK_Dialog {
	public $lastMessage;
	public $inRead;
	public $outRead;

	function getMessages($count=20, $rev=0) {
		$message = $this->lastMessage;
		return VK::getDialogMessages($message->getPeerID(), $count, $rev);
	}

	function syncWithJS($js_array) {
		$message = new VK_Message();
		$message->syncWithJS($js_array['message']);
		$this->lastMessage = $message;
		$this->inRead = $js_array['in_read'];
		$this->outRead = $js_array['out_read'];
	}
}