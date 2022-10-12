# Provide an easy way to view mails logged by the log mail driver in browser.

## Installation

```sh
composer require rabianr/laravel-log-mail-driver-viewer
```

## Configuration

Publish the config to copy the file to your own config:
```sh
php artisan vendor:publish --tag="logmailviewer"
```

Set mail driver and mail log channel in ```.env``` file.
```sh
APP_DEBUG=true
MAIL_MAILER=log
MAIL_LOG_CHANNEL=logmailviewer
```

## Usage

Access viewer at
```
https://<domain>/_maillog
```
