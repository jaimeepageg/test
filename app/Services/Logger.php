<?php

namespace Ceremonies\Services;

class Logger
{

    /**
     * Path to log file (relative to plugin root).
     * @var string
     */
    private static string $log_file = 'storage/plugin.log';

    /**
     * Logs a message to the log file, converting data to json
     * if supplied.
     *
     * @param $message
     * @param $data
     * @return void
     */
    public static function log($message, $data = null): void
    {
        $file_contents = '[' . date('H:i:s d-m-Y') . '] - ' . $message;
        if ($data) {
            $file_contents .= ' ' . json_encode($data);
        }
        $file_contents .= "\n";
        self::saveToFile($file_contents);
    }

    /**
     * Writes data to the log file.
     *
     * @param $message
     * @return void
     */
    private static function saveToFile($message): void
    {
        $file_path = CEREMONIES_ROOT . '/' . self::$log_file;
        if (!file_exists($file_path)) {
            touch($file_path);
        }
        file_put_contents($file_path, $message, FILE_APPEND);
    }

    /**
     * Instead of logging to a file, notify a Slack channel.
     * May only run once every 5 minutes.
     *
     * @param $message
     * @param $data
     * @return void
     */
    public static function notifySlack($message)
    {

        $lastSent = get_transient('cer_slack_last_sent');
        if (!$lastSent) {

            $messageBlocks = [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':smithers_creepin: SCC | Ceremony Account Portal Issue',
                        'emoji' => true
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ]
                ]
            ];

            $guzzle = new \GuzzleHttp\Client();
            $guzzle->post(CER_SLACK_HOOK, [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body' => json_encode([ 'blocks' => $messageBlocks ])
            ]);
            set_transient('cer_slack_last_sent', time(), 5 * MINUTE_IN_SECONDS);
        }
    }

}