<?php

namespace Rabianr\LogMailViewer\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\Part\AddressPart;

class MailLogController extends Controller
{

    public function index(Request $request)
    {
        [
            'cr' => $cr,
            'dir' => $dir,
        ] = ($data = $request->input('cr')) ? json_decode(base64_decode($data), true) : [
            'cr' => null,
            'dir' => 'next',
        ];

        [
            'mails' => $rawMails,
            'nextCr' => $nextCr,
            'prevCr' => $prevCr,
        ] = $this->getRawMails($cr, $dir);

        $mails = [];
        foreach ($rawMails as $rawMail) {
            $mails[] = Message::from($rawMail, false);
        }

        return view('logmailviewer::index', [
            'getMailboxes' => $this->makeGetMailboxes(),
            'mails' => $mails,
            'nextCr' => $nextCr ? base64_encode(json_encode([ 'cr' => $nextCr, 'dir' => 'next' ])) : null,
            'prevCr' => $prevCr ? base64_encode(json_encode([ 'cr' => $prevCr, 'dir' => 'prev' ])) : null,
        ]);
    }

    public function getRawMails($cr = null, $dir = 'next')
    {
        $perPage = 10;
        $logfiles = array_filter(scandir(storage_path('logs')), function ($path) use ($cr, $dir) {
            if (strpos($path, 'logmailviewer') !== 0) return false;
            if (! $cr) return true;

            $date = explode(' ', $cr)[0];

            return ($dir == 'next' && $path <= "logmailviewer-{$date}.log")
                || ($dir == 'prev' && $path >= "logmailviewer-{$date}.log");
        });

        if ($dir == 'next') {
            rsort($logfiles);
        } else {
            sort($logfiles);
        }

        $mails = [];
        $nextCr = $prevCr = null;
        $date = $mailContent = '';
        $hasPrevPage = $hasNextPage = false;

        foreach ($logfiles as $logfile) {
            $startCapturing = false;
            $lines = file(storage_path("logs/$logfile"), FILE_IGNORE_NEW_LINES);
            $_mails = [];

            foreach ($lines as $line) {
                if (! $startCapturing
                    && preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\[8fc7057844eb237dfaf44abb29f35465\]/', $line, $matches) === 1
                ) {
                    $startCapturing = true;
                    $mailContent = '';
                    $date = $matches[1];
                    continue;
                }

                if ($line === '<![[c00aac2fb70c7fb783d4707db1301d90]]>') {
                    $startCapturing = false;
                    $_mails[ $date ] = $mailContent;
                    $date = $mailContent = '';
                    continue;
                }

                $mailContent .= $line . "\n";
            }

            if ($dir == 'next') {
                $_mails = array_reverse($_mails);
            }

            foreach ($_mails as $date => $mail) {
                if ($dir == 'next') {
                    if (! $nextCr) {
                        if (! $hasPrevPage && $cr && $cr <= $date) {
                            $hasPrevPage = true;
                        }

                        if (! $cr || $cr > $date) {
                            $mails[ $date ] = $mail;

                            if ($perPage <= count($mails)) {
                                $nextCr = $date;

                                if ($hasPrevPage) {
                                    reset($mails);
                                    $prevCr = key($mails);
                                }

                                // break 2;
                            }
                        }
                    } else {
                        $hasNextPage = true;
                        break 2;
                    }
                } else {
                    if (! $prevCr) {
                        if (! $hasNextPage && $cr >= $date) {
                            $hasNextPage = true;
                        }

                        if ($cr < $date) {
                            $mails[ $date ] = $mail;

                            if ($perPage <= count($mails)) {
                                $prevCr = $date;

                                if ($hasNextPage) {
                                    reset($mails);
                                    $nextCr = key($mails);
                                }

                                $mails = array_reverse($mails);
                                // break 2;
                            }
                        }
                    } else {
                        $hasPrevPage = true;
                        break 2;
                    }
                }
            }
        }

        if (! $hasNextPage) {
            $nextCr = null;
        }

        if (! $hasPrevPage) {
            $prevCr = null;
        }

        if ($hasPrevPage && ! $prevCr) {
            reset($mails);
            $prevCr = key($mails);
        }

        return [
            'mails' => $mails,
            'nextCr' => $nextCr,
            'prevCr' => $prevCr,
        ];
    }

    protected function makeGetMailboxes()
    {
        return function (AddressHeader $addressHeader) {
            return array_map(function (AddressPart $addressPart) {
                if ($name = $addressPart->getName()) {
                    return htmlentities("$name <{$addressPart->getEmail()}>");
                }

                return $addressPart->getEmail();
            }, $addressHeader->getAddresses());
        };
    }
}
