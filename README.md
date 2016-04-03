Redmine Slack Notifier
========================================

Helps you not forget about log spent time to redmine in time!

[![Build Status](https://travis-ci.org/zviryatko/redmine-slack-notifier.svg?branch=master)](https://travis-ci.org/zviryatko/redmine-slack-notifier)
[![Coverage Status](https://coveralls.io/repos/github/zviryatko/redmine-slack-notifier/badge.svg?branch=master)](https://coveralls.io/github/zviryatko/redmine-slack-notifier?branch=master)

Installation / Usage
--------------------

```bash
$ composer create-project zviryatko/redmine-slack-notifier <project-path> --stability dev
```

Add script to cron in suitable time ```$ crontab -e```

	# MIN HOUR DAY MONTH DAYOFWEEK	COMMAND
	# run notifier at 8:00 pm every workday
	0 20 * * 1-5 php /path/to/project/bin/redmine-slack-notifier > /dev/null 2>&1


Requirements
------------

PHP 5.4 or above

Authors
-------

Alex Davyskiba - <sanya.davyskiba@gmail.com> - <https://twitter.com/zviryatk0> - <https://makeyoulivebetter.org.ua/><br />

See also the list of [contributors](https://github.com/zviryatko/redmine-slack-notifier/contributors) who participated in this project.

License
-------

Composer is licensed under the GPL-3 License - see the LICENSE file for details
