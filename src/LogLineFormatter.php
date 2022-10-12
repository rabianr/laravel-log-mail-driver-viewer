<?php

namespace Rabianr\LogMailViewer;

use Monolog\Formatter\LineFormatter;

class LogLineFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%][8fc7057844eb237dfaf44abb29f35465]\n%message%\n<![[c00aac2fb70c7fb783d4707db1301d90]]>\n",
                'Y-m-d H:i:s',
                true,
                true,
            ));
        }
    }
}
