<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once './include/db_handler.php';
require_once './include/upload.php';
require '././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Registering user account with new GCM id
$app->post('/user/register', function() use ($app)
{
    $response = array();
    verifyRequiredParams(array(
        'name',
        'username',
        'email',
        'password',
        'icon'
    ));
    $name     = $app->request->post('name');
    $username = $app->request->post('username');
    $email    = $app->request->post('email');
    $password = $app->request->post('password');
    $icon     = $app->request->post('icon');
    validateEmail($email);
    $db       = new DbHandler();
    $uploader = new FileUploader();
    if (!$db->isUserExists($email, $username)) {
        $uploadResponse = $uploader->uploadImage($icon, $username, rand(1, 500000));
        if ($uploadResponse["error"] == false) {
            $response = $db->createUser($name, $username, $email, $password, $uploadResponse["image_path"]);
        } else {
            $response = $uploadResponse;
        }
    } else {
        $response["error"] = true;
        $response["code"]  = USER_ALREADY_EXISTS;
    }
    echoRespnse(200, $response);
});

// Login
$app->get('/user/login', function() use ($app)
{
    verifyRequiredParams(array(
        'username',
        'password'
    ));
    $username = $app->request->params('username');
    $password = $app->request->params('password');
    $db       = new DbHandler();
    $response = $db->userLogin($username, $password);
    echoRespnse(200, $response);
});

$app->post('/user/update/icon', 'authenticate', function() use ($app)
{
    global $user_id;
    $uploader       = new FileUploader();
    $db             = new DbHandler();
    $username       = $app->request->params('username');
    $encoded_string = $app->request->params('encoded');
    $response       = $uploader->uploadImage($encoded_string, $username, $user_id);
    if ($response["error"] == false) {
        $res               = $db->updateUserIcon($user_id, $username, $response["image_path"]);
        $response["error"] = $res["error"];
        $response["code"]  = $res["code"];
    } else {
        $response["error"] = true;
        $response["code"]  = UNKNOWN_ERROR;
    }
    echoRespnse(200, $response);
});

$app->post('/group/update/icon', 'authenticate', function() use ($app)
{
    global $user_id;
    $uploader       = new FileUploader();
    $db             = new DbHandler();
    $username       = $app->request->params('username');
    $group_id       = $app->request->params('group_id');
    $encoded_string = $app->request->params('encoded');
    $response       = $uploader->uploadImage($encoded_string, $username, $user_id);
    if ($response["error"] == false) {
        $res                = $db->updateGroupIcon($group_id, $user_id, $response["image_path"]);
        $response["error"]  = $res["error"];
        $response["status"] = $res["code"];
    } else {
        $response["error"] = true;
        $response["code"]  = UNKNOWN_ERROR;
    }
    echoRespnse(200, $response);
});

// Use to update user account.
$app->put('/user/update/:toUpdate', 'authenticate', function($toUpdate) use ($app)
{
    global $user_id;
    $db       = new DbHandler();
    $uploader = new FileUploader();
    $response;
    $username = $app->request->params('username');
    if ($toUpdate == "user-status") {
        $userstatus         = $app->request->params('userstatus');
        $response           = $db->updateUserStatus($user_id, $username, $userstatus);
        $user               = $db->getUserByUsername($username);
        $response["status"] = $user["status"];
    } elseif ($toUpdate == "user-password") {
        $userpassword = $app->request->params('userpassword');
        $response     = $db->updateUserPassword($user_id, $username, $userpassword);
    } elseif ($toUpdate == "name") {
        $newname          = $app->request->params('name');
        $response         = $db->updateUserName($user_id, $username, $newname);
        $user             = $db->getUserByUsername($username);
        $response["name"] = $user["name"];
    } else {
        $response['error'] = true;
        $response['code']  = UNKNOWN_ERROR;
    }
    echoRespnse(200, $response);
});

// Use to update group settings.
$app->put('/group/update/:toUpdate', 'authenticate', function($toUpdate) use ($app)
{
    global $user_id;
    $db = new DbHandler();
    $response;
    $group_id = $app->request->params('group_id');
    $username = $app->request->params('username');
    if ($toUpdate == "group-status") {
        $status   = $app->request->params('groupstatus');
        $response = $db->updateGroupStatus($group_id, $user_id, $status);
        if ($response["code"] == REQUEST_PASSED) {
            sendGroupMessage($group_id, $user_id, 2, $username . " has changed the group description.", "");
        }
    } elseif ($toUpdate == "group-leave") {
        $message = $username . " left the group.";
        sendGroupMessage($group_id, $user_id, 2, $message, "");
        $response = $db->updateGroupLeave($group_id, $user_id);
    } elseif ($toUpdate == "group-kick") {
        $whom_param       = $app->request->params('groupkick');
        $whom             = $db->getUserByUsername($whom_param);
        $message          = $username . " kicked " . $whom['username'];
        $extras['action'] = "kick";
        $extras['whom']   = $whom['username'];
        sendGroupMessage($group_id, $user_id, 2, $message, $extras);
        $response = $db->updateGroupKick($group_id, $whom['user']);
        if ($response["code"] == REQUEST_PASSED) {
        }
    } elseif ($toUpdate == "group-name") {
        $name     = $app->request->params('groupname');
        $response = $db->updateGroupName($group_id, $user_id, $name);
        $message  = $username . " has changed the group name to '" . $name . "'";
        if ($response["code"] == REQUEST_PASSED) {
            sendGroupMessage($group_id, $user_id, 2, $message, "");
        }
    } elseif ($toUpdate == "group-addmember") {
        $usernames = array_filter(explode(',', $app->request->params('members')));
        $members   = $db->getUsersByUsername($usernames);
        $response  = $db->updateGroupParticipants($group_id, $members);
        if ($response["code"] == REQUEST_PASSED) {
            foreach ($members as $member) {
                $message = $username . " added " . $member["username"];
                sendGroupMessage($group_id, $user_id, 2, $message, "");
            }
        }
    } else {
        $response['error'] = true;
        $response['code']  = UNKNOWN_ERROR;
    }
    echoRespnse(200, $response);
});

// Update user GCM ID.
$app->put('/user/', 'authenticate', function() use ($app)
{
    $gcm_id = $app->request->params('gcm');
    global $user_id;
    $db       = new DbHandler();
    $response = $db->updateGCMID($user_id, $gcm_id);
    echoRespnse(200, $response);
});

// Create a new group.
$app->post('/groups/create', 'authenticate', function() use ($app)
{
    global $user_id;
    $db       = new DbHandler();
    $uploader = new FileUploader();
    verifyRequiredParams(array(
        'group_name',
        'group_icon',
        'group_description',
        'group_members'
    ));
    $group_name        = $app->request->params('group_name');
    $group_icon        = $app->request->params('group_icon');
    $group_description = $app->request->params('group_description');
    $group_creator     = $user_id;
    $group_members     = array_filter(explode(',', $app->request->params('group_members')));
    $members           = $db->getUsersByUsername($group_members);
    $uploadIcon        = $uploader->uploadImage($group_icon, rand(1, 500000), rand(1, 100000));
    if ($uploadIcon["error"] == false) {
        $response = $db->createGroup($group_name, $uploadIcon["image_path"], $group_description, $group_creator, $members);
        sendGroupMessage($response['CG'], $group_creator, 2, "Group conversation started.", "");
        $response["icon"]  = $uploadIcon["image_path"];
        $response["error"] = false;
    } else {
        $response["error"] = true;
        $response["code"]  = REQUEST_FAILED;
    }
    echoRespnse(200, $response);
});

// Searching for users from database.
$app->get('/users/directory/:toFind', function($toFind)
{
    global $app;
    $db                = new DbHandler();
    $result            = $db->searchUsers($toFind);
    $response["error"] = false;
    $response['users'] = array();
    while ($users = $result->fetch(PDO::FETCH_ASSOC)) {
        $user               = array();
        $user["user"]       = $users["id"];
        $user["username"]   = $users["username"];
        $user["email"]      = $users["email"];
        $user["name"]       = $users["name"];
        $user["status"]     = $users["status"];
        $user["icon"]       = $users["icon"];
        $user["created_At"] = $users["created_At"];

        array_push($response["users"], $user);
    }
    echoRespnse(200, $response);
});

// Retreiving user information.
$app->get('/user/info/:id', function($username)
{
    global $app;
    $db       = new DbHandler();
    $response = $db->getUserByUsername($username);
    if ($response['user'] != NULL) {
        $response['error'] = false;
    } else {
        $response['error'] = true;
    }
    echoRespnse(200, $response);
});

// Retreiving group information.
$app->get('/group/info/:id', function($group_id)
{
    global $app;
    $db       = new DbHandler();
    $response = $db->getGroupInformation($group_id);
    if ($response != NULL) {
        $response["members"] = $db->getGroupMembers($group_id);
        $response['error']   = false;
    } else {
        $response['error'] = true;
    }
    echoRespnse(200, $response);
});

$app->post('/group/message/:id', 'authenticate', function($group_id) use ($app)
{
    global $user_id;
    verifyRequiredParams(array(
        'message'
    ));
    $message      = $app->request->params('message');
    $message_type = $app->request->params('msg_type');
    $response     = sendGroupMessage($group_id, $user_id, $message_type, $message, "");
    echoRespnse(200, $response);
});

// Sending message to a specific group (Group Messsaging).
function sendGroupMessage($group_id, $user_id, $message_type, $message, $extras)
{
    $db       = new DbHandler();
    $uploader = new FileUploader();
    if ($message_type == 1) {
        $uploadRes = $uploader->uploadImage($message, $user_id, rand(1, 10000));
        $message   = $uploadRes["image_path"];
    }
    $addResponse       = $db->addGroupMessage($group_id, $user_id, $message_type, $message);
    $response['error'] = $addResponse['error'];
    $response['code']  = $addResponse['code'];
    if ($addResponse['error'] == false) {
        require_once __DIR__ . '/libs/gcm/gcm.php';
        require_once __DIR__ . '/libs/gcm/push.php';

        $members          = $db->getGroupMembers($group_id);
        $registration_ids = array();
        foreach ($members as $member) {
            if ($user_id != $member['user_id']) {
                array_push($registration_ids, $member['registration_id']);
            }
        }

        $gcm                  = new GCM();
        $push                 = new Push();
        $user                 = $db->getUser($user_id);
        $group                = $db->getGroupInformation($group_id);
        $data                 = array();
        $data['id']           = $addResponse["message_id"];
        $data['message_type'] = $message_type;
        $data['group']        = $group;
        $data['sender']       = $user;
        $data['isMember']     = true;
        $data['message']      = $addResponse['message'];
        $data['creation']     = $addResponse['creation'];
        if ($extras != "") {
            $data['extras'] = $extras;
        }
        $push->setTitle("Weeki Messenger");
        $push->setIsBackground(FALSE);
        $push->setFlag(PUSH_TYPE_GROUP);
        $push->setData($data);

        $gcm->sendToGroup($registration_ids, $push->getPush());
    }
    return $response;
}
;

// Sending message to a specific user (Private Message).
$app->post('/user/message/:id', 'authenticate', function($to_user_id) use ($app)
{
    global $user_id;
    $db       = new DbHandler();
    $uploader = new FileUploader();
    verifyRequiredParams(array(
        'message'
    ));
    $from_user_id = $user_id;
    $message_type = $app->request->params('msg_type');
    $message      = $app->request->params('message');
    if ($message_type == 1) {
        $uploadRes = $uploader->uploadImage($message, $from_user_id, rand(1, 10000));
        $message   = $uploadRes["image_path"];
    }
    $addResponse       = $db->addPrivateMessage($from_user_id, $to_user_id, $message_type, $message);
    $response['error'] = $addResponse['error'];
    $response['code']  = $addResponse['code'];
    if ($addResponse['error'] == false) {
        require_once __DIR__ . '/libs/gcm/gcm.php';
        require_once __DIR__ . '/libs/gcm/push.php';
        $gcm                  = new GCM();
        $push                 = new Push();
        $receiver             = $db->getUserByUsername($to_user_id);
        $sender               = $db->getUser($from_user_id);
        $data                 = array();
        $data['user']         = $receiver['username'];
        $data['id']           = $addResponse["message_id"];
        $data['message_type'] = $message_type;
        $data['message']      = $message;
        $data['creation']     = $addResponse['creation'];
        $data['sender']       = $sender;

        $push->setTitle("Weeki Messenger");
        $push->setIsBackground(FALSE);
        $push->setFlag(PUSH_TYPE_USER);
        $push->setData($data);

        $gcm->send($receiver['registration_id'], $push->getPush());
    }
    echoRespnse(200, $response);
});

$app->put('/update/message/', 'authenticate', function() use ($app)
{
    global $user_id;
    $db       = new DbHandler();
    $msg_id   = $app->request->params('msg_id');
    $group_id = $app->request->params('group_id');
    $response = $db->updateReceipt($group_id, $user_id, $msg_id);
    echoRespnse(200, $response);
});

$app->get('/users/messages/', 'authenticate', function()
{
    global $user_id;
    $response = retreiveGroupMessages($user_id);
    $response = retreiveMessages($user_id);
    echoRespnse(200, $response);
});

// Retreiving unread messages of a single user.
function retreiveMessages($user_id)
{
    require_once __DIR__ . '/libs/gcm/gcm.php';
    require_once __DIR__ . '/libs/gcm/push.php';
    $db                = new DbHandler();
    $gcm               = new GCM();
    $push              = new Push();
    $result            = $db->getAllMessages($user_id);
    $response["error"] = false;
    while ($messages = $result->fetch(PDO::FETCH_ASSOC)) {
        $receiver             = $db->getUser($user_id);
        $sender               = $db->getUser($messages["sender_id"]);
        $data['user']         = $receiver["username"];
        $data['id']           = $messages["message_id"];
        $data['message_type'] = $messages["msg_type"];
        $data['message']      = $messages["message"];
        $data['creation']     = $messages["created_At"];
        $data['sender']       = $sender;

        $push->setTitle("Weeki Messenger");
        $push->setIsBackground(FALSE);
        $push->setFlag(PUSH_TYPE_USER);
        $push->setData($data);

        $gcm->send($receiver['registration_id'], $push->getPush());
        //array_push($response["messages"], $data);
    }
    return $response;
}
;

// Retreiving unread groups messages.
function retreiveGroupMessages($user_id)
{
    $db = new DbHandler();
    require_once __DIR__ . '/libs/gcm/gcm.php';
    require_once __DIR__ . '/libs/gcm/push.php';
    $gcm               = new GCM();
    $push              = new Push();
    $result            = $db->getAllGroupConversation($user_id);
    $response["error"] = false;
    while ($groupconvo = $result->fetch(PDO::FETCH_ASSOC)) {
        $msg   = array();
        $group = array();

        // user node
        $receiver             = $db->getUser($user_id);
        // group node
        $group["group_id"]    = $groupconvo["group_id"];
        $group["name"]        = $groupconvo["group_name"];
        $group["description"] = $groupconvo["group_description"];
        $group["icon"]        = $groupconvo["group_icon"];
        $group["creation"]    = $groupconvo["group_creation"];
        // sender node
        $sender["id"]         = $groupconvo["user_id"];
        $sender["username"]   = $groupconvo["username"];
        // data node
        $data                 = array();
        $data["id"]           = $groupconvo["message_id"];
        $data['message_type'] = $groupconvo["msg_type"];
        $data['group']        = $group;
        $data['sender']       = $sender;
        $data['isMember']     = true;
        $data['message']      = $groupconvo["message"];
        $data['creation']     = $groupconvo["created_at"];

        $push->setTitle("Weeki Messenger");
        $push->setIsBackground(FALSE);
        $push->setFlag(PUSH_TYPE_GROUP);
        $push->setData($data);

        $gcm->send($receiver['registration_id'], $push->getPush());
        //array_push($response["messages"], $data);
    }
    return $response;
}

/* Verifying required params posted or not */
function verifyRequiredParams($required_fields)
{
    $error          = false;
    $error_fields   = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response            = array();
        $app                 = \Slim\Slim::getInstance();
        $response["error"]   = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function authenticate(\Slim\Route $route)
{
    $headers  = apache_request_headers();
    $response = array();
    $app      = \Slim\Slim::getInstance();
    if (isset($headers['Authorization'])) {
        $db  = new DbHandler();
        $api = $headers['Authorization'];
        if (!$db->checkApi($api)) {
            $response["error"] = true;
            $response["code"]  = 34;
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            $user = $db->getUserId($api);
            if ($user != NULL)
                $user_id = $user["id"];
        }
    } else {
        $response["error"] = true;
        $response["code"]  = 34;
        echoRespnse(400, $response);
        $app->stop();
    }
}

function validateEmail($email)
{
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["code"]  = 35;
        echoRespnse(400, $response);
        $app->stop();
    }
}

function IsNullOrEmptyString($str)
{
    return (!isset($str) || trim($str) === '');
}

function echoRespnse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response);
}

$app->run();
?>
