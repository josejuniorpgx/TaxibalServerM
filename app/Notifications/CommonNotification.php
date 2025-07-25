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
use Exception;

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
        $notifications = [];

        if ($notifiable->player_id != null) {
            if ($notifiable->user_type == 'driver' && env('ONESIGNAL_DRIVER_APP_ID') && env('ONESIGNAL_DRIVER_REST_API_KEY')) {
                try {
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
                    }

                    $onesignal_client = new OneSignalClient(
                        env('ONESIGNAL_DRIVER_APP_ID'),
                        env('ONESIGNAL_DRIVER_REST_API_KEY'),
                        null
                    );

                    $result = $onesignal_client->sendNotificationCustom($parameters);

                    if ($result instanceof \GuzzleHttp\Psr7\Response) {
                        $statusCode = $result->getStatusCode();
                        $responseBody = $result->getBody()->getContents();

                        if ($statusCode < 200 || $statusCode >= 300) {
                            Log::error('Driver notification failed', [
                                'status_code' => $statusCode,
                                'response' => $responseBody,
                                'player_id' => $notifiable->player_id
                            ]);
                        }
                    }

                } catch (Exception $e) {
                    Log::error('Driver notification error', [
                        'error' => $e->getMessage(),
                        'player_id' => $notifiable->player_id
                    ]);
                }

            } else if ($notifiable->user_type == 'rider' && env('ONESIGNAL_APP_ID') && env('ONESIGNAL_REST_API_KEY')) {
                try {
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

                    $onesignal_client = new OneSignalClient(
                        env('ONESIGNAL_APP_ID'),
                        env('ONESIGNAL_RIDER_REST_API_KEY'),
                        null
                    );

                    $result = $onesignal_client->sendNotificationCustom($parameters);

                    if ($result instanceof \GuzzleHttp\Psr7\Response) {
                        $statusCode = $result->getStatusCode();
                        $responseBody = $result->getBody()->getContents();

                        if ($statusCode < 200 || $statusCode >= 300) {
                            Log::error('Rider notification failed', [
                                'status_code' => $statusCode,
                                'response' => $responseBody,
                                'player_id' => $notifiable->player_id
                            ]);
                        }
                    }

                } catch (Exception $e) {
                    Log::error('Rider notification error', [
                        'error' => $e->getMessage(),
                        'player_id' => $notifiable->player_id
                    ]);
                }

            } else {
                // Fallback to OneSignal channel
                array_push($notifications, OneSignalChannel::class);
            }
        }

        // Add FCM for riders
        if (env('FIREBASE_SERVER_KEY') && $notifiable->user_type == 'rider' && $notifiable->fcm_token != null) {
            array_push($notifications, 'fcm');
        }

        return $notifications;
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

        try {
            if ($this->type == 'push_notification' && $this->data['image'] != null) {
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
        } catch (Exception $e) {
            Log::error('OneSignal message creation error', [
                'error' => $e->getMessage(),
                'notifiable_id' => $notifiable->id ?? null
            ]);
            throw $e;
        }
    }

    public function toFcm($notifiable)
    {
        try {
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

        } catch (Exception $e) {
            Log::error('FCM message creation error', [
                'error' => $e->getMessage(),
                'notifiable_id' => $notifiable->id ?? null
            ]);
            throw $e;
        }
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
