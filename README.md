# Gunbot-Polo-Updater
Updates Gunbot 4.0.5+ config.js for poloniex coins by volume

This uses Poloniex PHP API to automatically update config.js to bot top volume coins or sell off coins with reduced volume.

Instructions:

1) Install PHP (php7 recommended)

2) Enable cUrl PHP Extension in php.ini

3) Create a NEW poloniex API Key

4) Set your key and secret in the .php file.

5) Set configuration based on comments.

6) Schedule to run using cron or task scheduler. Recommended 90 minute intervals.
