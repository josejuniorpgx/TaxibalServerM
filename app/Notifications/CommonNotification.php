<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\AppSetting;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;
use Illuminate\Support\Facades\Log;
use Benwilkins\FCM\FcmMessage;
use Berkayk\OneSignal\OneSignalClient;

class CommonNotification extends Notification
{
    use Queueable;

    public $type, $data, $subject, $notification_message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->subject = str_replace("_", " ", ucfirst($this->data['subject']));
        $this->notification_message = $this->data['message'] != '' ? $this->data['message'] : __('message.default_notification_body');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // 🔍 Log inicial completo
        Log::info('🔔 === NOTIFICATION START ===', [
            'timestamp' => now(),
            'user_id' => $notifiable->id ?? 'unknown',
            'user_type' => $notifiable->user_type ?? 'unknown',
            'player_id' => $notifiable->player_id ?? 'null',
            'player_id_length' => strlen($notifiable->player_id ?? ''),
            'notification_type' => $this->data['type'] ?? 'unknown',
            'subject' => $this->subject,
            'environment' => app()->environment()
        ]);

        $notifications = [];

        if ($notifiable->player_id != null) {
            // 🔍 Log de configuraciones disponibles
            Log::info('🔧 Environment Variables Check', [
                'driver_config' => [
                    'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
                    'has_api_key' => !empty(env('ONESIGNAL_DRIVER_REST_API_KEY')),
                    'api_key_length' => strlen(env('ONESIGNAL_DRIVER_REST_API_KEY') ?? ''),
                    'channel_id' => env('ONESIGNAL_DRIVER_CHANNEL_ID'),
                    'general_channel_id' => env('ONESIGNAL_CHANNEL_ID')
                ],
                'rider_config' => [
                    'app_id' => env('ONESIGNAL_APP_ID'),
                    'has_api_key' => !empty(env('ONESIGNAL_REST_API_KEY')),
                    'api_key_length' => strlen(env('ONESIGNAL_REST_API_KEY') ?? ''),
                    'channel_id' => env('ONESIGNAL_RIDER_CHANNEL_ID')
                ]
            ]);

            if ($notifiable->user_type == 'driver' && env('ONESIGNAL_DRIVER_APP_ID') && env('ONESIGNAL_DRIVER_REST_API_KEY')) {

                Log::info('🚗 === PROCESSING DRIVER NOTIFICATION ===', [
                    'user_id' => $notifiable->id,
                    'player_id' => $notifiable->player_id,
                    'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
                    'notification_data_type' => $this->data['type'] ?? 'unknown'
                ]);

                $channelId = ($this->data['type'] === 'new_ride_requested') ? env('ONESIGNAL_CHANNEL_ID') : env('ONESIGNAL_DRIVER_CHANNEL_ID');
                $sound = ($this->data['type'] === 'new_ride_requested') ? 'ride_get_sound.wav' : 'default_app_sound.wav';

                $heading = [
                    'en' => $this->subject,
                ];

                $content = [
                    'en' => strip_tags($this->notification_message),
                ];

                $parameters = [
                    'api_key' => env('ONESIGNAL_DRIVER_REST_API_KEY'),
                    'android_channel_id' => $channelId,
                    'ios_sound' => $sound,
                    'ios_badgeType' => 'Increase',
                    'ios_badgeCount' => 1,
                    'mutable_content' => true,
                    'content_available' => true,
                    'apns_env' => 'sandbox',
                    'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
                    'include_player_ids' => [$notifiable->player_id],
                    'headings' => $heading,
                    'contents' => $content,
                    'data' => [
                        'id' => $this->data['id'],
                        'type' => $this->data['type'],
                    ]
                ];

                if ($this->type == 'push_notification' && $this->data['image'] != null) {
                    $parameters['big_picture'] = $this->data['image'];
                    $parameters['ios_attachments'] = $this->data['image'];

                    Log::info('🖼️ Image parameters added', [
                        'image_url' => $this->data['image'],
                        'notification_type' => $this->type
                    ]);
                }

                // 🔍 Log del payload completo antes de enviar
                Log::info('📤 DRIVER - Sending to OneSignal API', [
                    'url' => 'https://onesignal.com/api/v1/notifications',
                    'method' => 'POST',
                    'payload' => $parameters,
                    'payload_size' => strlen(json_encode($parameters)),
                    'player_id' => $notifiable->player_id
                ]);

                try {
                    // 🔥 Usar cURL directo para mejor control y logging
                    $response = $this->sendWithDetailedLogging($parameters, 'DRIVER', $notifiable->player_id);

                    // También ejecutar el método original para mantener compatibilidad
                    $onesignal_client = new OneSignalClient(env('ONESIGNAL_DRIVER_APP_ID'), env('ONESIGNAL_DRIVER_REST_API_KEY'), null);
                    $originalResponse = $onesignal_client->sendNotificationCustom($parameters);

                    Log::info('✅ DRIVER - OneSignal responses', [
                        'curl_response' => $response,
                        'original_client_response' => $originalResponse,
                        'player_id' => $notifiable->player_id
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ DRIVER - OneSignal Exception', [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'player_id' => $notifiable->player_id,
                        'parameters_sent' => $parameters
                    ]);
                }

            } else if ($notifiable->user_type == 'rider' && env('ONESIGNAL_APP_ID') && env('ONESIGNAL_REST_API_KEY')) {

                Log::info('🚶 === PROCESSING RIDER NOTIFICATION ===', [
                    'user_id' => $notifiable->id,
                    'player_id' => $notifiable->player_id,
                    'app_id' => env('ONESIGNAL_APP_ID'),
                    'notification_data_type' => $this->data['type'] ?? 'unknown'
                ]);

                $channelId = env('ONESIGNAL_RIDER_CHANNEL_ID');
                $sound = ($this->data['type'] === 'new_ride_requested') ? 'ride_get_sound.wav' : 'default_app_sound.wav';

                $heading = [
                    'en' => $this->subject,
                ];

                $content = [
                    'en' => strip_tags($this->notification_message),
                ];

                $parameters = [
                    'api_key' => env('ONESIGNAL_REST_API_KEY'),
                    'android_channel_id' => $channelId,
                    'ios_sound' => 'ride_get_sound.wav',
                    'ios_badgeType' => 'Increase',
                    'ios_badgeCount' => 1,
                    'mutable_content' => true,
                    'content_available' => true,
                    'apns_env' => 'sandbox',
                    'app_id' => env('ONESIGNAL_APP_ID'),
                    'include_player_ids' => [$notifiable->player_id],
                    'headings' => $heading,
                    'contents' => $content,
                    'data' => [
                        'id' => $this->data['id'],
                        'type' => $this->data['type'],
                    ],
                ];

                if ($this->type == 'push_notification' && $this->data['image'] != null) {
                    $parameters['big_picture'] = $this->data['image'];
                    $parameters['ios_attachments'] = $this->data['image'];
                }

                // 🔍 Log del payload completo antes de enviar
                Log::info('📤 RIDER - Sending to OneSignal API', [
                    'url' => 'https://onesignal.com/api/v1/notifications',
                    'method' => 'POST',
                    'payload' => $parameters,
                    'payload_size' => strlen(json_encode($parameters)),
                    'player_id' => $notifiable->player_id
                ]);

                try {
                    // 🔥 Usar cURL directo para mejor control y logging
                    $response = $this->sendWithDetailedLogging($parameters, 'RIDER', $notifiable->player_id);

                    // También ejecutar el método original
                    $onesignal_client = new OneSignalClient(env('ONESIGNAL_APP_ID'), env('ONESIGNAL_RIDER_REST_API_KEY'), null);
                    $originalResponse = $onesignal_client->sendNotificationCustom($parameters);

                    Log::info('✅ RIDER - OneSignal responses', [
                        'curl_response' => $response,
                        'original_client_response' => $originalResponse,
                        'player_id' => $notifiable->player_id
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ RIDER - OneSignal Exception', [
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'player_id' => $notifiable->player_id,
                        'parameters_sent' => $parameters
                    ]);
                }

            } else {
                Log::warning('⚠️ OneSignal notification skipped', [
                    'reason' => 'Missing configuration or unsupported user type',
                    'user_type' => $notifiable->user_type,
                    'user_id' => $notifiable->id,
                    'driver_conditions' => [
                        'is_driver' => $notifiable->user_type == 'driver',
                        'has_driver_app_id' => !empty(env('ONESIGNAL_DRIVER_APP_ID')),
                        'has_driver_api_key' => !empty(env('ONESIGNAL_DRIVER_REST_API_KEY'))
                    ],
                    'rider_conditions' => [
                        'is_rider' => $notifiable->user_type == 'rider',
                        'has_rider_app_id' => !empty(env('ONESIGNAL_APP_ID')),
                        'has_rider_api_key' => !empty(env('ONESIGNAL_REST_API_KEY'))
                    ]
                ]);

                array_push($notifications, OneSignalChannel::class);
            }
        } else {
            Log::warning('⚠️ Notification skipped - No player_id', [
                'user_id' => $notifiable->id ?? 'unknown',
                'user_type' => $notifiable->user_type ?? 'unknown',
                'player_id_is_null' => $notifiable->player_id === null,
                'player_id_value' => $notifiable->player_id
            ]);
        }

        // FCM handling
        if (env('FIREBASE_SERVER_KEY') && $notifiable->user_type == 'rider' && $notifiable->fcm_token != null) {
            Log::info('📱 Adding FCM channel for rider', [
                'user_id' => $notifiable->id,
                'has_fcm_token' => !empty($notifiable->fcm_token),
                'fcm_token_length' => strlen($notifiable->fcm_token ?? '')
            ]);
            array_push($notifications, 'fcm');
        }

        Log::info('🔚 === NOTIFICATION END ===', [
            'selected_channels' => $notifications,
            'total_channels' => count($notifications),
            'user_id' => $notifiable->id,
            'user_type' => $notifiable->user_type
        ]);

        return $notifications;
    }

    /**
     * 🔥 Método para envío con logging súper detallado
     */
    private function sendWithDetailedLogging($parameters, $userType, $playerId)
    {
        $startTime = microtime(true);
        $jsonPayload = json_encode($parameters);

        Log::info("🔍 [{$userType}] cURL Request Details", [
            'url' => 'https://onesignal.com/api/v1/notifications',
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Basic ' . $parameters['api_key']
            ],
            'payload_json' => $jsonPayload,
            'payload_size_bytes' => strlen($jsonPayload),
            'player_id' => $playerId,
            'start_time' => $startTime
        ]);

        // cURL con máximo detalle
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $parameters['api_key']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $fullResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlInfo = curl_getinfo($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos

        // Separar headers del body
        $responseHeaders = substr($fullResponse, 0, $headerSize);
        $responseBody = substr($fullResponse, $headerSize);
        $responseData = json_decode($responseBody, true);

        Log::info("📡 [{$userType}] cURL Response Complete", [
            'http_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300,
            'execution_time_ms' => round($executionTime, 2),
            'response_headers' => $responseHeaders,
            'response_body' => $responseBody,
            'response_parsed' => $responseData,
            'curl_info' => $curlInfo,
            'curl_error' => $curlError,
            'player_id' => $playerId
        ]);

        // Análisis específico de la respuesta de OneSignal
        if ($responseData) {
            Log::info("🔬 [{$userType}] OneSignal Response Analysis", [
                'notification_id' => $responseData['id'] ?? 'not_found',
                'recipients' => $responseData['recipients'] ?? 0,
                'external_id' => $responseData['external_id'] ?? null,
                'errors' => $responseData['errors'] ?? null,
                'warnings' => $responseData['warnings'] ?? null,
                'invalid_player_ids' => $responseData['invalid_player_ids'] ?? [],
                'player_id_sent' => $playerId,
                'success_indicators' => [
                    'has_notification_id' => isset($responseData['id']),
                    'has_recipients' => isset($responseData['recipients']) && $responseData['recipients'] > 0,
                    'no_errors' => empty($responseData['errors']),
                    'no_invalid_player_ids' => empty($responseData['invalid_player_ids'])
                ]
            ]);

            // Logs específicos para errores
            if (!empty($responseData['errors'])) {
                Log::error("🚨 [{$userType}] OneSignal API Errors", [
                    'errors' => $responseData['errors'],
                    'player_id' => $playerId,
                    'sent_parameters' => $parameters
                ]);
            }

            if (!empty($responseData['invalid_player_ids'])) {
                Log::error("🚨 [{$userType}] Invalid Player IDs", [
                    'invalid_player_ids' => $responseData['invalid_player_ids'],
                    'sent_player_id' => $playerId,
                    'player_id_matches' => in_array($playerId, $responseData['invalid_player_ids'])
                ]);
            }
        }

        return $responseData;
    }

    public function toOneSignal($notifiable)
    {
        $msg = strip_tags($this->notification_message);
        if (!isset($msg) && $msg == '') {
            $msg = __('message.default_notification_body');
        }

        $type = 'new_ride_requested';
        if (isset($this->data['type']) && $this->data['type'] !== '') {
            $type = $this->data['type'];
        }

        if ($type == 'push_notification' && $this->data['image'] != null) {
            return OneSignalMessage::create()
                ->setSubject($this->subject)
                ->setBody($msg)
                ->setData('id', $this->data['id'])
                ->setData('type', $type)
                ->setIosAttachment($this->data['image'])
                ->setAndroidBigPicture($this->data['image']);
        } else {
            return OneSignalMessage::create()
                ->setSubject($this->subject)
                ->setBody($msg)
                ->setData('id', $this->data['id'])
                ->setData('type', $type);
        }
    }

    public function toFcm($notifiable)
    {
        $message = new FcmMessage();
        $msg = strip_tags($this->notification_message);
        if (!isset($msg) && $msg == '') {
            $msg = __('message.default_notification_body');
        }
        $notification = [
            'body' => $msg,
            'title' => $this->subject,
        ];
        $data = [
            'click_action' => "FLUTTER_NOTIFICATION_CLICK",
            'sound' => 'default',
            'status' => 'done',
            'id' => $this->data['id'],
            'type' => $this->data['type'],
            'message' => $notification,
        ];

        $message->content($notification)->data($data)->priority(FcmMessage::PRIORITY_HIGH);
        return $message;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
