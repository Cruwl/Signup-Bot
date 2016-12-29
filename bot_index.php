<?php

/**
 * Credit goes to Cruwl
 * If you like this code, please share it with others!
 */

define('API_KEY','xxxXxxxXxxx'); //YOUR BOT TOKEN

function getData($id){
    $cached = apc_fetch($id);
    return $cached?$cached:'Flase';
}

function setData($id,$step){
    apc_store($id, $step, 60*60*12);
}

function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

// FETCHING UPDATES
$update = json_decode(file_get_contents('php://input'));

if (isset($update->message)) {
    // FETCHING USER INFO
    $chat_id = $update->message->chat->id;
    $message_id = $update->message->message_id;
    $text = $update->message->text;
    $step = getData('step-'.$chat_id);
    // PROCCESSING MESSAGE
    if ($text == '/cancel') {
        setData('step-'.$chat_id,'0');
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Session cancelled! send /start if you need me again."
        ]);
    }elseif ($text == '/start') {
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Hi there.\nSend /sign_up command to submite your information for lottery!"
        ]);
    }elseif ($text == '/sign_up') {
        setData('step-'.$chat_id,'1');
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Welcome!\nSend me your first name now:\n\nsend /cancel to cancel."
        ]);
    }elseif ($step == '1') {
        setData('name-'.$chat_id,$text);
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Success! Now send me your age:\n\nsend /cancel to cancel."
        ]);
        setData('step-'.$chat_id,'2');
    }elseif ($step == '2') {
        setData('age-'.$chat_id,$text);
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Success! Your information submitted. We will contact you soon.\n\n<b>Note:</b> If you wanna review your submitted information use /my_info!",
            'parse_mode' => 'HTML'
        ]);
        setData('step-'.$chat_id,'0');
    }elseif ($text == '/my_info') {
        $user_name = getData('name-'.$chat_id);
        $user_age = getData('age-'.$chat_id);
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "<b>Your name:</b> " . "<code>$user_name</code>" . "\n<b>Your age:</b> " . "<code>$user_age</code>",
            'parse_mode' => 'HTML'
        ]);
    }else{
        bot('sendMessage',[
            'chat_id' => $chat_id,
            'text' => "Command not found! If you wanna program a bot like me please visit this link:\n\nhttps://github.com/Cruwl/Signup-Bot"
        ]);
    }
}
