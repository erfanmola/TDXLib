<?php
/*
 * _____   ____   __  __  _     _ _
 * |_   _| |  _ \  \ \/ / | |   (_) |__
 *   | |   | | | |  \  /  | |   | | '_ \
 *   | |   | |_| |  /  \  | |___| | |_) |
 *   |_|   |____/  /_/\_\ |_____|_|_.__/
 * 
 * Author: Erfan Mola
 * Version: 0.1.1
 * License: GNU Affero General Public License v3.0
 */

function TelegramAPI(string $method, array $params = [], bool|null $response = null, string|null $bot_token = null, string|null $bot_api_server = null) : array|null {

    if (is_null($bot_api_server)) {

        if (defined('TDXBotAPIServer')) {

            $bot_api_server = TDXBotAPIServer;

        }else{
            
            $bot_api_server = "https://api.telegram.org";

        }

    }

    if (is_null($bot_token)) {

        if (defined('TDXToken')) {

            $bot_token = TDXToken;
    
        }else{

            return [ 'ok' => false, 'error' => 'Bot Token Not Specified' ];

        }

    }

    if (is_null($response)) {

        if (defined('TDXResponse')) {

            $response = TDXResponse;
    
        }else{

            $response = true;

        }

    }

    $params = json_encode(array_map(function($item) { return (is_array($item) ? json_encode($item) : $item); }, array_filter($params, function($item) { return $item; })));

    if ($response) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "{$bot_api_server}/bot{$bot_token}/{$method}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
        curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($ch, CURLOPT_TCP_NODELAY, true);

        $result = curl_exec($ch);
        
        curl_close($ch);

        if ($response === false) {

            $result = json_encode([
                'ok'              => false,
                'curl_error_code' => curl_errno($ch),
            ]);

        }

        return json_decode($result, true);

    }else{

        if (defined('SWOOLE_BASE') && (count(Swoole\Coroutine::list()) > 0)) {

            go(function() use (&$bot_api_server, &$bot_token, &$method, &$params) {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "{$bot_api_server}/bot{$bot_token}/{$method}");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
                curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
                curl_setopt($ch, CURLOPT_TCP_NODELAY, true);
        
                curl_exec($ch);
                
                curl_close($ch);

            });

        }else{

            $handle = popen("curl --parallel --parallel-immediate --parallel-max 100 --tcp-fastopen --tcp-nodelay -X POST -H 'Content-type: application/json' -d " . escapeshellarg($params) . " '{$bot_api_server}/bot{$bot_token}/{$method}' -o /dev/null >> /dev/null 2>&1 &", 'r');
            pclose($handle);

        }

    }

    return null;

}

/* Telegram Methods */

function SendMessage(string|int $chat_id, string|array $text, string|int|null $reply_to_message_id = null, array $params = [], bool|null $response = null, string|null $bot_token = null, string|null $bot_api_server = null) {

    $params = array_merge($params, [
        'chat_id'                     => $chat_id,
        'text'                        => is_string($text) ? $text : json_encode($text),
        'reply_to_message_id'         => $reply_to_message_id,
        'allow_sending_without_reply' => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'    => $params['disable_web_page_preview'] ?? true,
        'parse_mode'                  => $params['parse_mode'] ?? 'HTML',
    ]);

    if (strlen(strip_tags($params['text'])) <= 4096) {

        return TelegramAPI('sendMessage', $params, $response, $bot_token, $bot_api_server);

    }else{

        return array_map(function($chunk) use ($params, $response, $bot_token, $bot_api_server) {

            $params['text'] = $chunk;

            return TelegramAPI('sendMessage', $params, $response, $bot_token, $bot_api_server);

        }, str_split($params['text'], 4096));

    }

}

function SendDocument(string|int $chat_id, string $document, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'document'                       => $document,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendDocument', $params, $response, $bot_token, $bot_api_server);

}

function SendPhoto(string|int $chat_id, string $photo, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'photo'                          => $photo,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendPhoto', $params, $response, $bot_token, $bot_api_server);

}

function SendAnimation(string|int $chat_id, string $animation, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'animation'                      => $animation,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendAnimation', $params, $response, $bot_token, $bot_api_server);

}

function SendVideo(string|int $chat_id, string $video, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'video'                          => $video,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendVideo', $params, $response, $bot_token, $bot_api_server);

}

function SendVoice(string|int $chat_id, string $voice, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'voice'                          => $voice,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendVoice', $params, $response, $bot_token, $bot_api_server);

}

function SendAudio(string|int $chat_id, string $audio, string|array|null $caption = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'audio'                          => $audio,
        'caption'                        => strlen($caption) > 0 ? $caption : '',
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendAudio', $params, $response, $bot_token, $bot_api_server);

}

function SendSticker(string|int $chat_id, string $sticker, string|null $emoji = null, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                        => $chat_id,
        'sticker'                        => $sticker,
        'emoji'                          => $emoji,
        'reply_to_message_id'            => $reply_to_message_id,
        'allow_sending_without_reply'    => $params['allow_sending_without_reply'] ?? true,
        'disable_web_page_preview'       => $params['disable_web_page_preview'] ?? true,
        'disable_content_type_detection' => $params['disable_content_type_detection'] ?? true,
        'parse_mode'                     => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('sendSticker', $params, $response, $bot_token, $bot_api_server);

}

function DeleteMessage(string|int $chat_id, string|int $msg_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $msg_id,
    ], $response, $bot_token, $bot_api_server);

}

function BanChatMember(string|int $chat_id, string|int $user_id, int $until_date = 0, bool $revoke_messages = true, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('banChatMember', [
        'chat_id'         => $chat_id,
        'user_id'         => $user_id,
        'until_date'      => $until_date,
        'revoke_messages' => $revoke_messages,
    ], $response, $bot_token, $bot_api_server);

}

function UnbanChatMember(string|int $chat_id, string|int $user_id, bool $only_if_banned = true, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('unbanChatMember', [
        'chat_id'        => $chat_id,
        'user_id'        => $user_id,
        'only_if_banned' => $only_if_banned,
    ], $response, $bot_token, $bot_api_server);

}

function BanChatSenderChat(string|int $chat_id, int $sender_chat_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('banChatSenderChat', [
        'chat_id'         => $chat_id,
        'sender_chat_id'  => $sender_chat_id,
    ], $response, $bot_token, $bot_api_server);

}

function UnbanChatSenderChat(string|int $chat_id, int $sender_chat_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('unbanChatSenderChat', [
        'chat_id'         => $chat_id,
        'sender_chat_id'  => $sender_chat_id,
    ], $response, $bot_token, $bot_api_server);

}

function ForwardMessage(string|int $to_chat_id, string|int $from_chat_id, string|int $msg_id, bool $disable_notification = true, null|int $message_thread_id = null, null|int $protect_content = null, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('forwardMessage', [
        'chat_id'              => $to_chat_id,
        'from_chat_id'         => $from_chat_id,
        'message_id'           => $msg_id,
        'disable_notification' => $disable_notification,
        'protect_content'      => $protect_content,
        'message_thread_id'    => $message_thread_id,
    ], $response, $bot_token, $bot_api_server);

}

function CopyMessage(string|int $to_chat_id, string|int $from_chat_id, string|int $msg_id, string|int|null $reply_to_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                     => $to_chat_id,
        'from_chat_id'                => $from_chat_id,
        'message_id'                  => $msg_id,
        'reply_to_message_id'         => $reply_to_message_id,
        'allow_sending_without_reply' => $params['allow_sending_without_reply'] ?? true,
        'parse_mode'                  => $params['parse_mode'] ?? 'HTML',
    ]);

    return TelegramAPI('copyMessage', $params, $response, $bot_token, $bot_api_server);
    
}

function LeaveChat(string|int $chat_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('leaveChat', [
        'chat_id' => $chat_id,
    ], $response, $bot_token, $bot_api_server);

}

function PinMessage(string|int $chat_id, string|int $msg_id, bool $disable_notification = true, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('pinChatMessage', [
        'chat_id'              => $chat_id,
        'message_id'           => $msg_id,
        'disable_notification' => $disable_notification,
    ], $response, $bot_token, $bot_api_server);

}

function UnpinChatMessage(string|int $chat_id, string|int $msg_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('unpinChatMessage', [
        'chat_id'    => $chat_id,
        'message_id' => $msg_id,
    ], $response, $bot_token, $bot_api_server);

}

function UnpinAllChatMessages(string|int $chat_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('unpinAllChatMessages', [
        'chat_id'    => $chat_id,
    ], $response, $bot_token, $bot_api_server);

}

function GetChat(string|int $chat_id, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getChat', [
        'chat_id' => $chat_id,
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function GetChatAdministrators(string|int $chat_id, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getChatAdministrators', [
        'chat_id' => $chat_id,
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function GetChatMemberCount(string|int $chat_id, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getChatMemberCount', [
        'chat_id' => $chat_id,
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function GetChatMember(string|int $chat_id, string|int $user_id, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getChatMember', [
        'chat_id' => $chat_id,
        'user_id' => $user_id,
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function GetUserProfilePhotos(string|int $user_id, null|int $offset = null, null|int $limit = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getUserProfilePhotos', [
        'user_id' => $user_id,
        'offset'  => $offset,
        'limit'   => $limit
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function AnswerCallbackQuery(string|int $callback_query_id, string $text, bool $alert = false, int $cache_time = 0, string $url = '', null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('answerCallbackQuery', [
        'callback_query_id' => $callback_query_id,
        'text'              => $text, 
        'show_alert'        => $alert,
        'url'               => $url,
        'cache_time'        => $cache_time,
    ], $response, $bot_token, $bot_api_server);

}

function EditMessageCaption(null|string|int $chat_id = null, null|int|string $message_id = null, string|array $caption, null|int|string $inline_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                  => $chat_id,
        'caption'                  => is_string($caption) ? $caption : json_encode($caption),
        'disable_web_page_preview' => $params['disable_web_page_preview'] ?? true,
        'parse_mode'               => $params['parse_mode'] ?? 'HTML',
        'inline_message_id'        => $inline_message_id,
        'chat_id'                  => $chat_id,
        'message_id'               => $message_id,
    ]);

    return TelegramAPI('editMessageCaption', $params, $response, $bot_token, $bot_api_server);

}

function EditMessageText(null|string|int $chat_id = null, null|int|string $message_id = null, string|array $text, null|int|string $inline_message_id = null, array $params = [], null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    $params = array_merge($params, [
        'chat_id'                  => $chat_id,
        'text'                     => is_string($text) ? $text : json_encode($text),
        'disable_web_page_preview' => $params['disable_web_page_preview'] ?? true,
        'parse_mode'               => $params['parse_mode'] ?? 'HTML',
        'inline_message_id'        => $inline_message_id,
        'chat_id'                  => $chat_id,
        'message_id'               => $message_id,
    ]);

    return TelegramAPI('editMessageText', $params, $response, $bot_token, $bot_api_server);

}

function EditMessageReplyMarkup(null|string|int $chat_id = null, null|int|string $message_id = null, array|string $reply_markup = [], null|int|string $inline_message_id = null, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('editMessageReplyMarkup', [
        'chat_id'           => $chat_id,
        'inline_message_id' => $inline_message_id,
        'chat_id'           => $chat_id,
        'message_id'        => $message_id,
        'reply_markup'      => is_array($reply_markup) ? json_encode($reply_markup) : $reply_markup,
    ], $response, $bot_token, $bot_api_server);

}

function AnswerInlineQuery(string|int $inline_query_id, string|array $results, bool $is_personal = false, int $cache_time = 300, string|int|null $next_offset = null, string|null $switch_pm_text = null, string|null $switch_pm_parameter = null,  string|array|null $button = null, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('answerInlineQuery', [
        'inline_query_id'     => $inline_query_id,
        'results'             => is_array($results) ? json_encode($results) : $results, 
        'is_personal'         => $is_personal,
        'cache_time'          => $cache_time,
        'next_offset'         => $next_offset,
        'switch_pm_text'      => $switch_pm_text,
        'switch_pm_parameter' => $switch_pm_parameter,
        'button'              => is_array($button) ? json_encode($button) : $button,
    ], $response, $bot_token, $bot_api_server);

}

function GetFile(string $file_id, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getFile', [
        'file_id' => $file_id,
    ], true, $bot_token, $bot_api_server)['result'] ?? null;

}

function RestrictChatMember(string|int $chat_id, string|int $user_id, array $permissions, int $until_date = 0, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('restrictChatMember', [
        'chat_id'     => $chat_id,
        'user_id'     => $user_id,
        'permissions' => json_encode($permissions),
        'until_date'  => $until_date,
    ], $response, $bot_token, $bot_api_server);

}

function SendChatAction(string|int $chat_id, string $action, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('sendChatAction', [
        'chat_id' => $chat_id,
        'action'  => $action,
    ], $response, $bot_token, $bot_api_server);

}

function CreateForumTopic(string|int $chat_id, string $name, null|int $icon_color = null, null|string $icon_custom_emoji_id = null, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('createForumTopic', [
        'chat_id'              => $chat_id,
        'name'                 => $name,
        'icon_color'           => $icon_color,
        'icon_custom_emoji_id' => $icon_custom_emoji_id
    ], $response, $bot_token, $bot_api_server);

}

function EditForumTopic(string|int $chat_id, int $message_thread_id, null|string $name, null|string $icon_custom_emoji_id = null, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('editForumTopic', [
        'chat_id'              => $chat_id,
        'message_thread_id'    => $message_thread_id,
        'name'                 => $name,
        'icon_custom_emoji_id' => $icon_custom_emoji_id
    ], $response, $bot_token, $bot_api_server);

}

function CloseForumTopic(string|int $chat_id, int $message_thread_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('closeForumTopic', [
        'chat_id'              => $chat_id,
        'message_thread_id'    => $message_thread_id,
    ], $response, $bot_token, $bot_api_server);

}

function ReopenForumTopic(string|int $chat_id, int $message_thread_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('reopenForumTopic', [
        'chat_id'           => $chat_id,
        'message_thread_id' => $message_thread_id,
    ], $response, $bot_token, $bot_api_server);

}

function DeleteForumTopic(string|int $chat_id, int $message_thread_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('deleteForumTopic', [
        'chat_id'           => $chat_id,
        'message_thread_id' => $message_thread_id,
    ], $response, $bot_token, $bot_api_server);

}

function UnpinAllForumTopicMessages(string|int $chat_id, int $message_thread_id, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('unpinAllForumTopicMessages', [
        'chat_id'           => $chat_id,
        'message_thread_id' => $message_thread_id,
    ], $response, $bot_token, $bot_api_server);

}

function GetForumTopicIconStickers(null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return TelegramAPI('getForumTopicIconStickers', [], true, $bot_token, $bot_api_server)['result'] ?? null;

}

/* Telegram Methods */

/* OpenSwoole Methods */

if (defined('SWOOLE_BASE')) {

    Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

    defined('TDXSwooleFloodTableSize') or define('TDXSwooleFloodTableSize', 1024);

    $OpenSwooleFloodTable = new Swoole\Table(TDXSwooleFloodTableSize);
    $OpenSwooleFloodTable->column('count', Swoole\Table::TYPE_INT, 8);
    $OpenSwooleFloodTable->column('time', Swoole\Table::TYPE_INT, 32);
    $OpenSwooleFloodTable->create();

    function IsUserFlooding(string|int $user_id, int $flood_limit_count = 10, int $flood_limit_time = 120, $context = '') : bool|string|null {

        global $OpenSwooleFloodTable;

        $lastActivity = $OpenSwooleFloodTable->get($user_id . '_' . $context);

        $time = time();

        if ($lastActivity) {

            if ($lastActivity['time'] > ($time - $flood_limit_time)) {

                $OpenSwooleFloodTable->set($user_id . '_' . $context, [ 'time' => $time, 'count' => ($lastActivity['count'] + 1) ]);

                return ($lastActivity['count'] >= $flood_limit_count ? ($lastActivity['count'] > $flood_limit_count ? null : true) : false);

            }

        }

        $OpenSwooleFloodTable->set($user_id . '_' . $context, [ 'time' => $time, 'count' => 1 ]);

        return false;

    }

}

/* OpenSwoole Methods */

/* Helper Methods */

function ReportToAdmin(string|array $text, null|bool $response = null) : bool {
    
    if (defined('TDXAdmin')) {

        if (is_array(TDXAdmin)) {

            foreach (array_unique(TDXAdmin) as $uid) {

                SendMessage($uid, $text, null, [], $response);

            }

        }else{

            SendMessage(TDXAdmin, $text, null, [], $response);

        }

        return true;

    }else{
        
        return false;

    }

}

function IsUserMemberOf(string|int $user_id, string|int $chat_id, null|string $bot_token = null, string|null $bot_api_server = null) : bool {

    $chat_member = GetChatMember($chat_id, $user_id, $bot_token, $bot_api_server);

    if ($chat_member === null) {

        return false;

    }else{

        return isset($chat_member['status']) && $chat_member['status'] !== 'left' && $chat_member['status'] !== 'kicked';

    }

}

function IsUserAdminOf(string|int $user_id, string|int $chat_id, null|string $bot_token = null, string|null $bot_api_server = null) : bool {
    
    foreach (GetChatAdministrators($chat_id, $bot_token, $bot_api_server) as $admin) {

        if ((string)$admin['user']['id'] === (string)$user_id) {

            return true;

        }

    }

    return false;

}

function CheckUserRemainingSponsors(string|int $user_id, array $sponsors, null|string $bot_token = null, string|null $bot_api_server = null) : array {

    if (is_null($bot_api_server)) {

        if (defined('TDXBotAPIServer')) {

            $bot_api_server = TDXBotAPIServer;

        }else{
            
            $bot_api_server = "https://api.telegram.org";

        }

    }

    if (is_null($bot_token)) {

        if (defined('TDXToken')) {

            $bot_token = TDXToken;
    
        }else{

            return [ 'ok' => false, 'error' => 'Bot Token Not Specified' ];

        }

    }

    $mh = curl_multi_init();
   
    $CurlHandles = [];

    foreach ($sponsors as $sponsor) {

        $url = "$bot_api_server/bot$bot_token/getChatMember";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($ch, CURLOPT_POST, true);
    
        $content = [
            'chat_id' => $sponsor['chat_id'],
            'user_id' => $user_id,
        ];
    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

        $CurlHandles[] = $ch;
        curl_multi_add_handle($mh, $ch);

    }
   
    $active = null;

    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {

        if (curl_multi_select($mh) != -1) {

            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        }

    }
   
    foreach ($CurlHandles as $key => $ch) {
        
        $chat_member = json_decode(curl_multi_getcontent($ch), true)['result'] ?? [];
        
        curl_multi_remove_handle($mh, $ch);

        if (isset($chat_member['status']) && $chat_member['status'] !== 'left' && $chat_member['status'] !== 'kicked') {

            unset($sponsors[$key]);

        }

    }

    curl_multi_close($mh); 

    return array_values($sponsors);

}

function MentionUserByID(string|int $user_id, string $text, string $mode = 'HTML') : string {
    
    if ($mode === 'HTML') {

        return "<a href='tg://user?id=$user_id'>$text</a>";

    }else if ($mode === 'MD') {

        return "[$text](tg://user?id=$user_id)";

    }

}

if (!(function_exists('IsUserFlooding'))) {

    function IsUserFlooding(string|int $user_id, int $flood_limit_count = 10, int $flood_limit_time = 120, string|int $context = 'global') : bool|null {

        $context = md5($context);

        $file = sys_get_temp_dir() . "/$context";

        if (!(file_exists($file))) {

            file_put_contents($file, '[]');
            chmod($file, 0777);

            $data = [];

        }

        $i = 0;

        while (0644 === (fileperms($file) & 0777) && $i < 20) {

            usleep(12500);
            $i++;

        }

        chmod($file, 0644);

        $data = file_get_contents($file);
        $data = json_decode($data, true);

        $now = time();
        $now = $now - ($now % 60);

        if ($data === null || $data === false) {

            $data = [$now, []];

        }else if (is_array($data) && count($data) < 2) {

            $data = [$now, []];

        }

        if (($data[0] + $flood_limit_time) <= $now) {

            $data[1] = [];

        }

        $count = $data[1][$user_id] ?? 0;
        $count++;
        $data[1][$user_id] = $count;

        $data[0] = $now;

        if ((int)$count <= (int)((int)$flood_limit_count + 1)) {

            file_put_contents($file, json_encode($data));

        }

        chmod($file, 0777);

        if ((int)$count === (int)$flood_limit_count) {

            return true;

        }else if ((int)$count > (int)$flood_limit_count) {

            return null;

        }else{

            return false;

        }

    }

}

function MuteChatMember(string|int $chat_id, string|int $user_id, int $until_date = 0, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return RestrictChatMember($chat_id, $user_id, [ 
        'can_send_messages' => false
    ], $until_date, $response, $bot_token, $bot_api_server);

}

function UnmuteChatMember(string|int $chat_id, string|int $user_id, int $until_date = 0, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return RestrictChatMember($chat_id, $user_id, [
        'can_send_messages'         => true,
        'can_send_media_messages'   => true,
        'can_send_polls'            => true,
        'can_send_other_messages'   => true,
        'can_add_web_page_previews' => true,
    ], $until_date, $response, $bot_token, $bot_api_server);

}

function UnrestrictChatMember(string|int $chat_id, string|int $user_id, int $until_date = 0, null|bool $response = null, null|string $bot_token = null, string|null $bot_api_server = null) : array|null {

    return RestrictChatMember($chat_id, $user_id, GetChat($chat_id)['permissions'], $until_date, $response, $bot_token, $bot_api_server);

}

function SerializeHTMLString($string) {

    return str_replace("&zwnj;", "‌", htmlentities($string));

}

/* Helper Methods */