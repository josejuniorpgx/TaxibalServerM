<?php

namespace App\Services;

use Berkayk\OneSignal\OneSignalClient;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class OneSignalPlayerValidator
{
    /**
     * Validate and clean invalid Player IDs
     */
    public static function validateAndCleanPlayerIds()
    {
        // Get users with player_id
        $users = User::whereNotNull('player_id')->get();

        $driverInvalidIds = [];
        $riderInvalidIds = [];
        $cleanedCount = 0;

        foreach ($users as $user) {
            if ($user->user_type == 'driver') {
                $isValid = self::validateDriverPlayerId($user->player_id);
                if (!$isValid) {
                    $driverInvalidIds[] = $user->player_id;
                    $user->player_id = null;
                    $user->save();
                    $cleanedCount++;
                }
            } elseif ($user->user_type == 'rider') {
                $isValid = self::validateRiderPlayerId($user->player_id);
                if (!$isValid) {
                    $riderInvalidIds[] = $user->player_id;
                    $user->player_id = null;
                    $user->save();
                    $cleanedCount++;
                }
            }
        }

        return [
            'driver_invalid' => $driverInvalidIds,
            'rider_invalid' => $riderInvalidIds,
            'cleaned_count' => $cleanedCount
        ];
    }

    /**
     * Validate a Driver Player ID
     */
    private static function validateDriverPlayerId($playerId)
    {
        try {
            $client = new OneSignalClient(
                env('ONESIGNAL_DRIVER_APP_ID'),
                env('ONESIGNAL_DRIVER_REST_API_KEY'),
                null
            );

            // Send test notification
            $parameters = [
                'api_key' => env('ONESIGNAL_DRIVER_REST_API_KEY'),
                'app_id' => env('ONESIGNAL_DRIVER_APP_ID'),
                'include_player_ids' => [$playerId],
                'headings' => ['en' => 'Test'],
                'contents' => ['en' => 'Player ID validation test'],
                'send_after' => date('Y-m-d H:i:s', strtotime('+1 year')) // Don't actually send
            ];

            $result = $client->sendNotificationCustom($parameters);

            if ($result instanceof \GuzzleHttp\Psr7\Response) {
                $responseBody = $result->getBody()->getContents();
                $decoded = json_decode($responseBody, true);

                // If there are invalid Player ID errors
                if (isset($decoded['errors']['invalid_player_ids'])) {
                    return false;
                }

                // If there's unsubscribed error (but ID exists)
                if (isset($decoded['errors']) && in_array('All included players are not subscribed', $decoded['errors'])) {
                    return false; // Consider as invalid too
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error validating driver player ID', [
                'player_id' => $playerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate a Rider Player ID
     */
    private static function validateRiderPlayerId($playerId)
    {
        try {
            $client = new OneSignalClient(
                env('ONESIGNAL_APP_ID'),
                env('ONESIGNAL_RIDER_REST_API_KEY'),
                null
            );

            // Send test notification
            $parameters = [
                'api_key' => env('ONESIGNAL_REST_API_KEY'),
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$playerId],
                'headings' => ['en' => 'Test'],
                'contents' => ['en' => 'Player ID validation test'],
                'send_after' => date('Y-m-d H:i:s', strtotime('+1 year')) // Don't actually send
            ];

            $result = $client->sendNotificationCustom($parameters);

            if ($result instanceof \GuzzleHttp\Psr7\Response) {
                $responseBody = $result->getBody()->getContents();
                $decoded = json_decode($responseBody, true);

                // If there are invalid Player ID errors
                if (isset($decoded['errors']['invalid_player_ids'])) {
                    return false;
                }

                // If there's unsubscribed error
                if (isset($decoded['errors']) && in_array('All included players are not subscribed', $decoded['errors'])) {
                    return false;
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error validating rider player ID', [
                'player_id' => $playerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Command to execute from Artisan
     */
    public static function runCleanupCommand()
    {
        echo "Initiating Player ID validation...\n";

        $result = self::validateAndCleanPlayerIds();

        echo "Results:\n";
        echo "   - Invalid Driver IDs: " . count($result['driver_invalid']) . "\n";
        echo "   - Invalid Rider IDs: " . count($result['rider_invalid']) . "\n";
        echo "   - Total cleaned: " . $result['cleaned_count'] . "\n";

        if ($result['cleaned_count'] > 0) {
            echo "Database cleaned successfully\n";
        } else {
            echo "No invalid Player IDs found\n";
        }

        return $result;
    }
}
