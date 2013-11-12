Philip - a PHP IRC bot framework
================================

[![Build Status](https://travis-ci.org/epochblue/philip.png?branch=master)](https://travis-ci.org/epochblue/philip)

Philip is a [Slim](http://slimframework.com/)-inspired framwork for creating simple IRC bots.
It was written by [Bill Israel](http://billisrael.info/). The purpose of the project is to
allow people to create fun, simple IRC bots with minimal overhead or complexity.


Requirements
------------

 * PHP 5.3.3+
 * [Composer](http://getcomposer.org/)


Installation
------------

The best way to create a bot based on Philip is to use [Composer](http://getcomposer.org/).
From the command line, create a directory for your bot. In this directory, first download Composer:

```sh
$> curl -s http://getcomposer.org/installer | php
```

Then create and open a `composer.json` file. Add Philip to the list of required libraries:

```javascript
{
    "require": {
        "epochblue/philip": "1.0.*"
    }
}
```

Run Composer's install command to download and install the Philip library:

```sh
$> php composer.phar install -v
```

Once this is complete, your directory should have a few new items (a `composer.lock` file, and
a `vendors` directory) in it, and you should be ready to go. All that's left is to create the
the bot. You can name your bot whatever you want, though `bot.php` is nice and easy.
Here's a basic example:

```php
// bot.php
<?php

require __DIR__ . '/vendor/autoload.php';

$config = array(
    "server"       => "irc.freenode.net",
    "port"         => 6697,
    "ssl"          => true,
    "username"     => "examplebot",
    "realname"     => "example IRC Bot",
    "nick"         => "examplebot",
    "password"     => "password-for-nickserv",
    "connection_password" => "connection-password",
    "channels"     => array( '#example-channel' ),
    "unflood"      => 500,
    "admins"       => array( 'example' ),
    "debug"        => true,
    "log"          => __DIR__ . '/bot.log',
);

$bot = new Philip($config);

$bot->onChannel('/^!echo (.*)$/', function($event) {
    $matches = $event->getMatches();
    $event->addResponse(Response::msg($event->getRequest()->getSource(), trim($matches[0])));
});

$bot->run();
```

Save your file, and start your bot:

```sh
$> php examplebot.php
```

And that's all there is to it! Your bot will connect to the IRC server and join the channels you've
specified. Then it'll start listening for any commands you've created. For more information about
Philip's API, please see the API section below. But first, let's quickly cover the configuration
array...

Configuration
-------------

Configuration for Philip is a basic array of key-value pairs. Here's a quick
reference to what each key value pair is:

* _server_: _string_, the name of the IRC server to connect to
* _port_: _int_, the port on the IRC sever to connect to
* _username_: _string_, the IRC username for the bot
* _realname_: _string_, the IRC "real name" for the bot
* _nick_: _string_, the IRC nickname for the bot
* _password_: _string_, _optional_, the password to identify the bot with NickServ
* *connection_password*: _string_, _optional_, the password to register your connection, if required by the server
* _channels_: _array_, an array of channels for the bot to join. Can also join channels
with passwords. For example:

    ```php
    'channels' => array(
        '#channel-without-password',
        array('#channel-with-password' => 'channel-password')
    )
    ```

* _unflood_: _int_, _optional_, the amount of time to wait between sending
messages
* _admins_: _array_, _optional_, an array of names of IRC users who have admin
control of the bot (required by some plugins)
* _debug_: _boolean_, whether or not to print debug information,
* _log_: _string_, path where to store the bot's log file, required if _debug_
is turned on


API
---

Philip's API is simple and similar to JavaScript's "on*" event system. You add functionality
to your bot by telling it how to respond to certain events. Events include things like channel
messages, private messages, users joining a channel, users leaving a channel, etc.

There are two kinds of events in Philip: server events and message events. The only real difference
between the two is that you can tell Philip to conditionally respond to message events. Since your
bot will always respond to server events, the API for those is simpler:

```php
$bot->on<Event>(<callback function>[, <priority>]);
```

Possible values for &lt;Event&gt; in this case are: `Join`, `Part`, `Error`, and `Notice`.

For message events, to determine whether your bot should respond to a given event,
you will supply a regular expression that Philip will test against. If the regex matches,
then Philip will execute the callback function you provide. The API for message events is:

```php
$bot->on<Event>(<regex pattern>, <callback function>[, <priority>]);
```

Possible values for &lt;Event&gt; include `Channel`, `PrivateMessage`, and `Message`.

#### Event Examples:

```php
// Message Events
$bot->onChannel()           // listens only to channel messages
$bot->onPrivateMessage()    // listens only to private messages
$bot->onMessages()          // listens to both channel messages and private messages

// Server Events
$bot->onError()             // listens only for IRC ERROR messages
$bot->onInvite()            // listens only for invites to channels
$bot->onJoin()              // listens only for people joining channels
$bot->onKick()              // listens only for people getting kicked from channels
$bot->onMode()              // listens only for IRC MODE change messages
$bot->onNick()              // listens only for people changing nick on channels
$bot->onNotice()            // listens only for IRC NOTICE messages
$bot->onPart()              // listens only for people leaving channels
$bot->onPing()              // listens only for IRC PING messages
$bot->onQuit()              // listens only for people leaving servers
$bot->onTopic()             // listens only for channel topic changes
```

The `<regex pattern>` is a standard PHP regular expression. If `null` is passed instead of a
regular expression, the callback function will always be executed for that event type.
If any match groups are specified in the regular expression, they will be passed to the callback function
through the event.

If your regular expression is successfully matched, Philip will execute the callback function you provide,
allowing you to respond to the message. The `<callback function>` is an anonymous function that accepts
one parameter: `$event`.

Setting the `<priority>` is optional. In case a event matches multiple callback functions, the one with
the highest priority is executed first. If priority is not set, the default value (0) will be used.
If multiple callbacks have the same priority, they are executed in the order they where added.

`$event` is an instance of `Philip\IRC\Event` (which is a simple wrapper over an IRC "event"). The
main functions in the public API for a Philip Event re:

```php
$event->getRequest()        // Returns a Philip Request object
$event->getMatches()        // Returns an array of any matches found for match
                               groups specified by the <regex pattern>.
$event->addResponse()       // Adds a response to the list of responses for the event.
```

There is no captured return value for the callback function. However, if you
wish to send a message back to the IRC server, your callback function can add a response to
the list of responses by using the `addResponse()` method on the `Event` object. The `addResponse()`
method expects its only parameter to be an instance of a Philip Response object.

Putting all this together, let's look an example of adding `echo` functionality to Philip:

### Example:

```php
$bot->onChannel('/^!echo (.*)$/', function($event) {
    $matches = $event->getMatches();
    $event->addResponse(Response::msg($request->getSource(), trim($matches[0])));
});
```

In this example, the bot will listen for channel messages that begin with `!echo` and are followed
by anything else. The "anything else" is captured in a match group and passed to the callback
function. The callback function simply adds a response to the event that send the matching message
back to the channel that originally received the message.


#### Methods of note in the `Philip\IRC\Request` object:

```php
$request->getSendingUser()      // Get the nick of the user sending a message
$request->getSource()           // Get the channel of the sent message,
                                // or nick if it was a private message
$request->getMessage()          // Get the text of a message for channel/private messages
```


#### Methods of note in the `Philip\IRC\Response` object:

```php
Response::msg($who, $msg)      // Sends a message $msg to channel/PM $who
Response::action($who, $msg)   // Same as a ::msg(), but sends the message as an IRC ACTION
```


Plugins
-------

Philip supports a basic plugin system, and adding a plugin to your bot is simple.

### Using a plugin

Using a plugin is simple. Plugins should be Composer-able, so start by include the plugins via Composer.
Once you've run `composer update` and your plugins are available in your bot, you can load the plugins by
calling either `loadPlugin(<name>)` (to load them one at a time), or `loadPlugins(array())`
(to load multiple plugins at once).

For example, if you had a plugin whose full, namespaced classname was \Example\Philip\Plugin\HelloPlugin,
you can do load it in your both with either of the following:

```php
$bot = new Philip(array(/*...*/));
$bot->loadPlugin(new \Example\PhilipPlugin\HelloPlugin($bot));
$bot->loadPlugins(array(
    new \Example\PhilipPlugin\HelloPlugin($bot),
    // ...
));
```

Plugins accept a second (optional) parameter on their constructor if the plugin
requires some configuration. Loading a plugin that accepts configuration might look
like this:

```php
$bot = new Philip(array(/*...*/));
$bot->loadPlugin(new \Example\PhilipPlugin\HelloPlugin($bot, $config['HelloPlugin']));
```

Additionally, if you'd like to turn some of your bot's functionality into a plugin, that's easy as well.

### Writing a plugin

Creating a plugin is simple. A plugin must extend the `Philip\AbstractPlugin` class and must provide
and implementation for an `init()` and a `getName()` method. And that's it. Your plugin can be named anything, however, by
convention most Philip plugins are named like `<xxx>Plugin` Below is an example of a simple plugin:

```php
// .../Example/PhilipPlugin/HelloPlugin.php
<?php

namespace Example\PhilipPlugin;

use Philip\AbstractPlugin as BasePlugin;

class HelloPlugin extends BasePlugin
{
    /**
     * Does the 'heavy lifting' of initializing the plugin's behavior
     */
    public function init()
    {
        $this->bot->onChannel('/^hello$/', function($event) {
            $request = $event->getRequest();
            $event->addResponse(
                Response::msg($request->getSource(), "Hi, {$request->getSendingUser()}!")
            );
        });
    }

    /**
     * Returns the Plugin's name
     */
    public function getName()
    {
        return 'HelloPlugin';
    }
}
```


License
-------

Copyright (c) 2012 Bill Israel <bill.israel@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software
and associated documentation files (the "Software"), to deal in the Software without restriction,
including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial
portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

_This license is also included in the `LICENSE` file._

Author
------

Bill Israel - [https://github.com/epochblue](https://github.com/epochblue) - [https://twitter.com/epochblue](https://twitter.com/epochblue)

Contributors
------------

Doug Hurst - [https://github.com/dalanhurst](https://github.com/dalanhurst) - [https://twitter.com/dalanhurst](https://twitter.com/dalanhurst) <br/>
Micah Breedlove - [https://github.com/druid628](https://github.com/druid628) - [https://twitter.com/druid628](https://twitter.com/druid628) <br/>
Julien Bianchi - [https://github.com/jubianchi](https://github.com/jubianchi) - [https://twitter.com/jubianchi](https://twitter.com/jubianchi)

Acknowledgements
----------------

Philip was heavily inspired by the [Slim framework](http://slimframework.com) and
[Isaac IRC DSL](https://github.com/vangberg/isaac) projects.
