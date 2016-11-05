<?php
class VK {
	static public $appID; // Идентификатор приложения
	static public $redirectURI = "https://oauth.vk.com/blank.html"; // Адрес переадресации
	static public $scope; // Права доступа
	static public $access_token; // Ключ доступа
	static private $apiURL = "https://api.vk.com/method/"; // URL запросов к API

	static function showAuth($form, $browser) {
		$form->show();

		$appID = self::$appID;
		$redirectURI = self::$redirectURI;
		$scope = self::$scope;

		$browser->url = "https://oauth.vk.com/authorize?client_id=$appID&redirect_uri=$redirectURI&display=popup&scope=$scope&response_type=token&v=5.60&state=&revoke=1";
	}

	static function accessTokenFromURL($url) {
		preg_match('/^' . str_replace("/", "\/", self::$redirectURI) . '#access_token=([^&]+)&/', $url, $result);
		self::$access_token = $result[1];
	}

	static function execQuery($method, $params, $toArray=false) {
		$apiURL = self::$apiURL;
		$accessToken = self::$access_token;
		$url = "{$apiURL}$method?$params&access_token=$accessToken&v=5.60";

		$result = file_get_contents($url);
		$win1251 = iconv("utf-8", "windows-1251", $result);

		if ($toArray) {
			$arr = self::jsonDecode($result);
			if (!$arr['error']) return $arr;
			else return new VK_Error($arr['error']);
		}
		else return $win1251;
	}

	static private function jsonDecode($json) {
	    if (!$json) return false;
	    $decoded = json_decode($json, true);
	    return self::iconvArray($decoded);
	}

	static private function iconvArray($jsonArray) {
		$myArray = $jsonArray;
	    foreach ($jsonArray as $key => $value) {
	        if (is_array($value)) {
	            $myArray[$key] = self::iconvArray($value);
	        } else {
	            $myArray[$key] = iconv('utf-8', 'windows-1251//IGNORE', $value);
	        }
	    }
	    return $myArray;
	}

	static function checkQuery($query) {
		if (is_object($query)) {
			if (get_class($query) == "VK_Error") return false;
		}
		else return true;
	}
}

class VK_User {
	public $id;
	public $name;
	public $lastName;
	public $deactivated;
	public $hidden;
	public $online;
	public $sex;
	public $photo50;
	public $photo100;
	public $photo200;
	public $photoMax;
	public $status;
	public $counters;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		if (is_array($js_array)) {
			$this->id = $js_array['id'];
			$this->name = $js_array['first_name'];
			$this->lastName = $js_array['last_name'];
			$this->deactivated = $js_array['deactivated'];
			$this->hidden = $js_array['hidden'];
			$this->online = $js_array['online'];
			$this->sex = $js_array['sex'];
			$this->photo50 = $js_array['photo_50'];
			$this->photo100 = $js_array['photo_100'];
			$this->photo200 = $js_array['photo_200'];
			$this->photoMax = $js_array['photo_max'];
			$this->status = $js_array['status'];
			$this->counters = new VK_Counters($js_array['counters']);
		}
		else $this->id = $js_array;
	}

	function update() {
		$this->syncWithJS(VK_Users::getUser($this->id, array("online", "sex", "photo_50", "photo_100", "photo_200", "photo_max", "status", "counters"), "nom", true));
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

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

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

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function getMessages($count=20, $rev=0) {
		$message = $this->lastMessage;
		return VK_Messages::getHistory($message->getPeerID(), $count, $rev);
	}

	function syncWithJS($js_array) {
		$message = new VK_Message();
		$message->syncWithJS($js_array['message']);
		$this->lastMessage = $message;
		$this->inRead = $js_array['in_read'];
		$this->outRead = $js_array['out_read'];
	}
}
class VK_Error {
	public $code;
	public $message;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->code = $js_array['error_code'];
		$this->message = $js_array['error_msg'];
	}
}
class VK_Group {
	public $id;
	public $name;
	public $type;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		if (is_array($js_array)) {
			$this->id = $js_array['id'];
			$this->name = $js_array['name'];
			$this->type = $js_array['type'];
		}
		else $this->id = $js_array;
	}
}

class VK_Counters {
	public $albums;
	public $videos;
	public $audios;
	public $photos;
	public $notes;
	public $friends;
	public $groups;
	public $onlineFriends;
	public $mutualFriends;
	public $userVideos;
	public $followers;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->albums = $js_array['albums'];
		$this->videos = $js_array['videos'];
		$this->audios = $js_array['audios'];
		$this->photos = $js_array['photos'];
		$this->notes = $js_array['notes'];
		$this->friends = $js_array['friends'];
		$this->groups = $js_array['groups'];
		$this->onlineFriends = $js_array['online_friends'];
		$this->mutualFriends = $js_array['mutual_friends'];
		$this->userVideos = $js_array['user_videos'];
		$this->followers = $js_array['followers'];
	}
}

class VK_FriendStatus {
	public $userID;
	public $friendStatus;
	public $requestMessage;
	public $readState;
	public $sign;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->userID = $js_array['user_id'];
		$this->friendStatus = $js_array['friend_status'];
		$this->requestMessage = $js_array['request_message'];
		$this->readState = $js_array['read_state'];
		$this->sign = $js_array['sign'];
	}
}

class VK_FriendDelete {
	public $success;
	public $friendDeleted;
	public $outRequestDeleted;
	public $inRequestDeleted;
	public $suggestionDeleted;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->success = $js_array['success'];
		$this->friendDeleted = $js_array['friend_deleted'];
		$this->outRequestDeleted = $js_array['out_request_deleted'];
		$this->inRequestDeleted = $js_array['in_request_deleted'];
		$this->suggestionDeleted = $js_array['suggestion_deleted'];
	}
}