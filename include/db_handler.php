<?php
class DbHandler
{

    private $conn;

    function __construct()
    {
        require_once dirname(__FILE__) . '/db_connect.php';
        $db         = new DbConnect();
        $this->conn = $db->connect();
    }

    public function createUser($name, $username, $email, $password, $icon)
    {
        require_once 'PassHash.php';
        $response = array();
        if (!$this->isUserExists($email, $username)) {
            $password_hash = PassHash::hash($password);
            $api           = $this->generateApiKey();
            $stmt          = $this->conn->prepare("INSERT INTO users(name, username, email, password, icon, api) values(:var1, :var2, :var3, :var4, :var5, :var6)");
            $stmt->bindParam(":var1", $name);
            $stmt->bindParam(":var2", $username);
            $stmt->bindParam(":var3", $email);
            $stmt->bindParam(":var4", $password_hash);
            $stmt->bindParam(":var5", $icon);
            $stmt->bindParam(":var6", $api);
            $result = $stmt->execute();
            $stmt = null;
            if ($result) {
                $response          = $this->getApi($username);
                $response["error"] = false;
                $response["user"]  = $this->getUserByEmail($email);
            } else {
                $response["error"]   = true;
                $response["message"] = UNKNOWN_ERROR;
            }
        } else {
            $response["error"] = true;
            $response["code"]  = USER_ALREADY_EXISTS;
        }
        return $response;
    }

    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    public function userLogin($username, $password)
    {
        require_once 'PassHash.php';
        $response = array();
        $stmt     = $this->conn->prepare("SELECT password, disabled FROM users WHERE username = '$username'");
        $stmt->execute();
        $acc  = $stmt->fetch();
        $disabled = $acc["disabled"];
        $password_hash = $acc["password"];
        if ($stmt->rowCount() > 0) {
            if ($disabled == 0) {
                if (PassHash::check_password($password_hash, $password)) {
                    $response            = $this->getApi($username);
                    $response['error']   = false;
                    $response['account'] = $this->getUserByUsername($username);
                } else {
                    $response["error"] = true;
                    $response["code"]  = PASSWORD_INCORRECT;
                }
            } else {
                $response["error"] = true;
                $response["code"]  = ACCOUNT_DISABLED;
            }
        } else {
            $response["error"] = true;
            $response["code"]  = USER_INVALID;
        }
        return $response;
    }

    public function updateGCMID($user_id, $gcm_id)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE users SET registration_id = :var1 WHERE id = :var2");
        $stmt->bindParam(":var1", $gcm_id);
        $stmt->bindParam(":var2", $user_id);
        if ($stmt->execute()) {
            $response["error"]   = false;
            $response["message"] = GCM_UPDATE_SUCCESSFUL;
        } else {
            $response["error"]   = true;
            $response["message"] = GCM_UPDATE_FAILED;
            $stmt->error;
        }
        $stmt = null;
        return $response;
    }

    public function checkApi($api)
    {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api = :var1");
        $stmt->bindParam(":var1", $api);
        $stmt->execute();
        //$stmt->store_result();
        $num_rows = $stmt->rowCount();
        $stmt = null;
        return $num_rows > 0;
    }

    public function getApi($user_id)
    {
        $stmt = $this->conn->prepare("SELECT api FROM users WHERE id = :var1 OR username = :var1");
        $stmt->bindParam(":var1", $user_id);
        if ($stmt->execute()) {
            $api = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            return $api;
        } else {
            return NULL;
        }
    }

    public function getUserId($api)
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api = :var1");
        $stmt->bindParam(":var1", $api);
        if ($stmt->execute()) {
            $user_id = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = null;
            return $user_id;
        } else {
            return NULL;
        }
    }

    function isUserExists($email, $username)
    {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = :var1 OR username = :var2");
        $stmt->bindParam(":var1", $email);
        $stmt->bindParam(":var2", $username);
        $stmt->execute();
        //$stmt->store_result();
        $num_rows = $stmt->rowCount();
        $stmt = null;
        return $num_rows > 0;
    }

    public function getUser($user_id)
    {
        $stmt = $this->conn->prepare("SELECT id as user, username, name, email, registration_id, created_At, disabled, status, icon FROM users WHERE id = :var1");
        $stmt->bindParam(":var1", $user_id);
        if ($stmt->execute()) {
            $u = $stmt->fetch();
            $user                    = array();
            $user["user"]            = $u["user"];
            $user["username"]        = $u["username"];
            $user["name"]            = $u["name"];
            $user["email"]           = $u["email"];
            $user["registration_id"] = $u["registration_id"];
            $user["created_At"]      = $u["created_At"];
            $user["disabled"]        = $u["disabled"];
            $user["status"]          = $u["status"];
            $user["icon"]            = $u["icon"];
            $stmt = null;
            return $user;
        } else {
            return NULL;
        }
    }

    private function getUserByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT id as user, username, name, email, registration_id, created_At, disabled, status, icon FROM users WHERE email = :var1");
        $stmt->bindParam(":var1", $email);
        if ($stmt->execute()) {
            $u = $stmt->fetch();
            $user                    = array();
            $user["user"]            = $u["user"];
            $user["username"]        = $u["username"];
            $user["name"]            = $u["name"];
            $user["email"]           = $u["email"];
            $user["registration_id"] = $u["registration_id"];
            $user["created_At"]      = $u["created_At"];
            $user["disabled"]        = $u["disabled"];
            $user["status"]          = $u["status"];
            $user["icon"]            = $u["icon"];
            $stmt = null;
            return $user;
        } else {
            return NULL;
        }
    }

    function getUserByUsername($username)
    {
        $stmt = $this->conn->prepare("SELECT id as user, username, name, email, registration_id, created_At, disabled, status, icon FROM users WHERE username = :var1");
        $stmt->bindParam(":var1", $username);
        if ($stmt->execute()) {
            $u = $stmt->fetch();
            $user                    = array();
            $user["user"]            = $u["user"];
            $user["username"]        = $u["username"];
            $user["name"]            = $u["name"];
            $user["email"]           = $u["email"];
            $user["registration_id"] = $u["registration_id"];
            $user["created_At"]      = $u["created_At"];
            $user["disabled"]        = $u["disabled"];
            $user["status"]          = $u["status"];
            $user["icon"]            = $u["icon"];
            $stmt = null;
            return $user;
        } else {
            return NULL;
        }
    }

    public function getUsersByUsername($usernames)
    {
        $users = array();
        if (sizeof($usernames) > 0) {
            $query = "SELECT id, username, name, email, registration_id, created_At, disabled, status, icon FROM users WHERE username IN (";
            foreach ($usernames as $username) {
                $query .= $username . ',';
            }
            $query = substr($query, 0, strlen($query) - 1);
            $query .= ')';
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            // $result = $stmt->get_result();
            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tmp                    = array();
                $tmp["user"]            = $user['id'];
                $tmp["username"]        = $user['username'];
                $tmp["name"]            = $user['name'];
                $tmp["email"]           = $user['email'];
                $tmp["registration_id"] = $user['registration_id'];
                $tmp["created_At"]      = $user['created_At'];
                $tmp["disabled"]        = $user['disabled'];
                $tmp["status"]          = $user['status'];
                $tmp["icon"]            = $user['icon'];
                array_push($users, $tmp);
            }
        }
        return $users;
    }

    public function searchUsers($toFind)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username LIKE '%$toFind%' OR name LIKE '%$toFind%' OR email = :var1");
        $stmt->bindParam(":var1", $toFind);
        $stmt->execute();
        $users = $stmt;
        $stmt = null;
        return $users;
    }

    public function updateUserIcon($user_id, $username, $image_Path)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE users SET icon = :var1 WHERE id = :var2");
        $stmt->bindParam(":var1", $image_Path);
        $stmt->bindParam(":var2", $user_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        $stmt = null;
        return $response;
    }

    public function updateUserStatus($user_id, $username, $userstatus)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE users SET status = :var1 WHERE username = :var2 AND id = :var3");
        $stmt->bindParam(":var1", $userstatus);
        $stmt->bindParam(":var2", $username);
        $stmt->bindParam(":var3", $user_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateUserPassword($user_id, $username, $userpassword)
    {
        require_once 'PassHash.php';
        $response = array();
        $password = PassHash::hash($userpassword);
        $stmt     = $this->conn->prepare("UPDATE users SET password = :var1 WHERE username = :var2 AND id = :var3;");
        $stmt->bindParam(":var1", $password);
        $stmt->bindParam(":var2", $username);
        $stmt->bindParam(":var3", $user_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateUserName($user_id, $username, $newname)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE users SET name = :var1 WHERE username = :var2 AND id = :var3;");
        $stmt->bindParam(":var1", $newname);
        $stmt->bindParam(":var2", $username);
        $stmt->bindParam(":var3", $user_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateReceipt($group_id, $user_id, $msg_id)
    {
        $response;
        $query;
        if ($group_id == "-1") {
            $query = "UPDATE messages_receipt SET is_delivered = 1 WHERE message_id = :var1 AND user_id = :var2";
        } else {
            $query = "UPDATE group_receipt SET is_delivered = 1 WHERE message_id = :var1 AND user_id = :var2";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":var1", $msg_id);
        $stmt->bindParam(":var2", $user_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        $stmt = null;
        return $response;
    }

    public function getAllMessages($user_id)
    {
        $stmt = $this->conn->prepare("SELECT m.message_id, m.receiver_id, m.sender_id, u.name, m.msg_type, m.message, m.created_At, mr.is_delivered
FROM messages m LEFT JOIN users u ON u.id = m.sender_id LEFT JOIN messages_receipt mr ON mr.message_id = m.message_id
WHERE mr.is_delivered = 0 AND mr.user_id = :var1 ORDER BY m.message_id;");
        $stmt->bindParam(":var1", $user_id);
        $stmt->execute();
        $messages = $stmt;
        $stmt = null;
        return $messages;
    }

    public function getAllGroupConversation($user_id)
    {
        $stmt = $this->conn->prepare("SELECT gm.message_id, gm.group_id, g.name as group_name, g.icon as group_icon, g.description as group_description, g.creation as
group_creation, gm.user_id, u.username, gm.msg_type , gm.message, gm.created_at, gr.is_delivered
FROM group_messages gm LEFT JOIN groups g ON g.group_id = gm.group_id LEFT JOIN users u ON u.id = gm.user_id LEFT JOIN group_receipt gr ON gr.message_id = gm.message_id
WHERE gr.is_delivered = 0 AND gr.user_id = :var1 ORDER BY gm.group_id;");
        $stmt->bindParam(":var1", $user_id);
        $stmt->execute();
        $messages = $stmt;
        $stmt = null;
        return $messages;
    }

    public function addGroupMessage($group_id, $user_id, $message_type, $message)
    {
        $response = array();
        date_default_timezone_set('UTC');
        $crDate = date("Y-m-d H:i:s");
        $stmt   = $this->conn->prepare("SELECT AddGroupMessage (:var1, :var2, :var3, :var4, :var5) as GM");
        $stmt->bindParam(":var1", $group_id);
        $stmt->bindParam(":var2", $user_id);
        $stmt->bindParam(":var3", $message_type);
        $stmt->bindParam(":var4", $message);
        $stmt->bindParam(":var5", $crDate);
        $stmt->execute();
        $result  = $stmt->fetch();
        if ($stmt->rowCount() > 0) {
            $response["error"]      = false;
            $response["message_id"] = $result["GM"];
            $response["message"]    = $message;
            $response["creation"]   = $crDate;
            $response["code"]       = MESSAGE_SENT;
        } else {
            $response["error"] = true;
            $response["code"]  = FAILED_MESSAGE_SEND;
        }
        return $response;
    }

    public function addPrivateMessage($from_user_id, $to_user_id, $message_type, $message)
    {
        date_default_timezone_set('UTC');
        $crDate = date("Y-m-d H:i:s");
        $stmt   = $this->conn->prepare("SELECT AddMessage (:var1, :var2, :var3, :var4, :var5) as PM");
        $stmt->bindParam(":var1", $to_user_id);
        $stmt->bindParam(":var2", $from_user_id);
        $stmt->bindParam(":var3", $message_type);
        $stmt->bindParam(":var4", $message);
        $stmt->bindParam(":var5", $crDate);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($stmt->rowCount() > 0) {
            $response["error"]      = false;
            $response["message_id"] = $result["PM"];
            $response["creation"]   = $crDate;
            $response["code"]       = MESSAGE_SENT;
        } else {
            $response["error"] = true;
            $response["code"]  = FAILED_MESSAGE_SEND;
        }
        return $response;
    }

    public function getGroupMembers($group_id)
    {
        $members = array();
        $stmt    = $this->conn->prepare("SELECT gm.group_id, gm.user_id, u.username, u.registration_id FROM group_members gm LEFT JOIN
users u ON u.id = gm.user_id WHERE group_id = :var1 GROUP BY gm.group_id, gm.user_id;");
        $stmt->bindParam(":var1", $group_id);
        $stmt->execute();
        // $result = $stmt->get_result();
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tmp                    = array();
            $tmp["group_id"]        = $user["group_id"];
            $tmp["user_id"]         = $user['user_id'];
            $tmp["username"]        = $user['username'];
            $tmp["registration_id"] = $user['registration_id'];
            array_push($members, $tmp);
        }
        $stmt = null;
        return $members;
    }

    public function getGroupInformation($group_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM groups WHERE group_id = :var1");
        $stmt->bindParam(":var1", $group_id);
        if ($stmt->execute()) {
            $g  = $stmt->fetch();
            $group                = array();
            $group["group_id"]    = $g["id"];
            $group["name"]        = $g["name"];
            $group["icon"]        = $g["icon"];
            $group["description"] = $g["description"];
            $group["creation"]    = $g["creation"];
            $stmt = null;
            return $group;
        } else {
            return NULL;
        }
    }

    public function createGroup($group_name, $group_icon, $group_description, $group_creator, $members)
    {
        $response = array();
        $stmt     = $this->conn->prepare("SELECT CreateGroup (:var1, :var2, :var3, :var4) as CG");
        $stmt->bindParam(":var1", $group_name);
        $stmt->bindParam(":var2", $group_icon);
        $stmt->bindParam(":var3", $group_description);
        $stmt->bindParam(":var4", $group_creator);
        $stmt->execute();
        $r = $stmt->fetch();
        $group_id = $r["CG"];
        $stmt = null;
        $query = "INSERT INTO group_members (group_id, user_id) VALUES ";
        foreach ($members as $user_id) {
            $query .= '(' . $group_id . ',' . $user_id['user'] . '),';
        }
        $query = substr($query, 0, strlen($query) - 2);
        $query .= ')';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result       = $stmt->fetch();
        $result['CG'] = $group_id;
        $stmt = null;
        return $result;
    }

    public function updateGroupIcon($group_id, $user_id, $icon)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE groups SET icon = :var1 WHERE group_id = (SELECT group_id FROM group_members WHERE user_id = :var2 AND group_id = :var3);");
        $stmt->bindParam(":var1", $icon);
        $stmt->bindParam(":var2", $user_id);
        $stmt->bindParam(":var3", $group_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateGroupStatus($group_id, $user_id, $status)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE groups SET description = :var1 WHERE group_id = (SELECT group_id FROM group_members WHERE user_id = :var2 AND group_id = :var3);");
        $stmt->bindParam(":var1", $status);
        $stmt->bindParam(":var2", $user_id);
        $stmt->bindParam(":var3", $group_id);
        if ($stmt->execute()) {
            $response["error"]  = false;
            $response["code"]   = REQUEST_PASSED;
            $response["status"] = $status;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateGroupLeave($group_id, $user_id)
    {
        $response = array();
        $stmt     = $this->conn->prepare("DELETE FROM group_members WHERE user_id = :var1 AND group_id = :var2");
        $stmt->bindParam(":var1", $user_id);
        $stmt->bindParam(":var2", $group_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateGroupKick($group_id, $whom)
    {
        $response = array();
        $stmt     = $this->conn->prepare("DELETE FROM group_members WHERE user_id = :var1 AND group_id = :var2");
        $stmt->bindParam(":var1", $whom);
        $stmt->bindParam(":var2", $group_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateGroupName($group_id, $user_id, $name)
    {
        $response = array();
        $stmt     = $this->conn->prepare("UPDATE groups SET name = :var1 WHERE group_id = (SELECT group_id FROM group_members WHERE user_id = :var2 AND group_id = :var3);");
        $stmt->bindParam(":var1", $name);
        $stmt->bindParam(":var2", $user_id);
        $stmt->bindParam(":var3", $group_id);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
            $response["name"]  = $name;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }

    public function updateGroupParticipants($group_id, $members)
    {
        $response = array();
        $query    = "INSERT INTO group_members (group_id, user_id) VALUES ";
        foreach ($members as $user_id) {
            $query .= '(' . $group_id . ',' . $user_id['user'] . '),';
        }
        $query = substr($query, 0, strlen($query) - 2);
        $query .= ')';
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute()) {
            $response["error"] = false;
            $response["code"]  = REQUEST_PASSED;
        } else {
            $response["error"] = true;
            $response["code"]  = REQUEST_FAILED;
        }
        return $response;
    }
}
?>
