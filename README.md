Philip - a PHP IRC bot framework
================================

Philip is a [Slim](http://slimframework.com/)-inspired framwork for creating simple IRC bots.
It was written by [Bill Israel](http://billisrael.info/). The purpose of the project is to
allow people to create fun, simple IRC bots with a minimal amount of overhead.


Requirements
------------

 * PHP 5.3.0+
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
        "epochblue/philip": "dev-master"
    }
}
```

Run Composer's install command to download and install the Philip library:

```sh
$> php composer.phar install -v
```

Once this is complete, your directory should have a few new items (a `composer.lock` file, and
a `vendors` directory) in it, and you shoudl be ready to go. All that's left is to create the
the bot. You can name your bot whatever you want, though `bot.php` is nice and easy.
Here's a basic example:

```php
// bot.php
<?php

require __DIR__ . '/vendor/autoload.php';

$config = array(
    "hostname"   => "irc.freenode.net",
    "servername" => "example.com",
    "port"       => 6667,
    "username"   => "examplebot",
    "realname"   => "example IRC Bot",
    "nick"       => "examplebot",
    "channels"   => array( '#example-channel' ),
    "admins"     => array( 'example' ),
);

$bot = new Philip($config);

$bot->onChannel('/^!echo (.*)$/', function($request, $matches) {
    $echo = trim($matches[0]);
    return Response::msg($request->getSource(), $echo);
});

$bot->run();
```

Save your file, and start your bot:

```sh
$> php examplebot.php
```

And that's all there is to it! Your bot will connect to the IRC server, join the channels you've
wanted, and start listening for any commands you've specified. For more information about
Philip's API, please see the API section below.


API
---

Philip's API is simple and similar to JavaScript's "on*" event system. You add functionality
to your bot by telling it how to respond to certain events. Events include things like channel
messages, private messages, users joining a channel, users leaving a channel, etc.

To determine whether your bot should respond to a given event, you will supply a regular expression
that Philip will test against. If the regex matches, then Philip will execute the callback function
you provide. Putting it all together, the basic API for Philip follows this pattern:

```php
$bot->on<Event>(<regex pattern>, <callback function>)
```

Possible values for &lt;Event&gt; include `Channel`, `PrivateMessage`, `Message`, `Join`, `Part` `Error`,
and `Notice`.

#### Event Examples:

```php
$bot->onChannel()           // listens only to channel messages
$bot->onPrivateMessage()    // listens only to private messages
$bot->onMessage()           // listens to both channel messages and private messages
$bot->onJoin()              // listens only for people joining channels
$bot->onPart()              // listens only for people leaving channels
$bot->onError()             // listens only for IRC ERROR messages
$bot->onNotice()            // listens only for IRC NOTICE messages
```

The `<regex pattern>` is a standard PHP regular expression. If `null` is passed instead of a
regular expression, the callback function will be executed for all messages of that event type.
If any match groups are specified in the regular expression, they will be passed to the callback function.

If your regular expression is successfully matched, Philip will execute the callback function you provide,
allowing you to respond to the message. The `<callback function>` is an anonymous function that accepts
two parameters: `$request` and `$matches`.

`$request` is an instance of `Philip\IRC\Request` (which is a simple wrapper over a raw IRC message).
`$matches` is an array of any matches found for match groups specified by the <regex pattern>.

There are no strong requirements around what a `<callback function>` must return. However, if you
wish to send a message back to the IRC server, the function must return a `Philip\IRC\Response`.
Putting all this together, let's look an example of adding `echo` functionality to Philip:

### Example:

```php
$bot->onChannel('/^!echo (.*)$/', function($request, $matches) {
    $echo = trim($matches[0]);
    return Response::msg($request->getSource(), $echo);
});
```

In this example, the bot will listen for channel messages that begin with `!echo` and are followed
by anything else. The "anything else" is captured in a match group and passed to the callback
function. The callback function simply returns the match to the channel that originall received
the message.

#### Methods of note in the `Philip\IRC\Request` object:

```php
$request->getSendingUser()      // get the nick of the user sending a message
$request->getSource()           // get the channel of the sent message,
                                // or nick if it was a private message
$request->getMessage()          // get the text of a message for channel/private messages
```


#### Methods of note in the `Philip\IRC\Response` object:

```php
Response::msg($where, $what)    // Sends a message $what to channel/PM $where
Response::action($where, $what) // Same as a ::msg(), but sends the message as an IRC ACTION
```


Plugins
-------

Philip supports a basic plugin system, and adding a plugin to your bot is simple.

### Using a plugin

Using a plugin is simple. In your bot's project directory, create a `plugins` directory, and place the
plugin files in it. In the file for your bot, load the plugins by calling either `loadPlugin(<name>)`
(to load them one at a time), or `loadPlugins(array())` (to load multiple plugins at once). A plugin's
"name" is considered to be the plugin's class name without the word "Plugin".

For example, if you had a HelloPlugin installed, you can do load it with either of the following:

```php
$bot->loadPlugin('Hello');
$bot->loadPlugins(array('Hello'));
```

Additionally, if you'd like to turn some of your bot's functionality into a plugin, that's easy as well.

### Writing a plugin

To create a plugin, you must follow a few simple conventions, but beyond that, there's very little to them.
A Plugin is little more than a specially-named file containing a single namespace-less plain-old-PHP-object
that has, at minimum, two methds:

* a constructor that accepts an instance of a Philip bot as a paramter, and 
* an `init()` method for setting up the plugin functionality
    
Your plugin should be named like `XXXPlugin`, where XXX is the "name" of your plugin.
Below is an example of a simple plugin:

```php
// HelloPlugin.php
<?php

class HelloPlugin
{
    /** @var Philip $bot The instance of the bot to add functionality to */
    private $bot;

    /**
     * Constructor, with injected $bot dependency
     */
    public function __constructor($bot)
    {
        $this->bot = $bot;
    }

    /**
     * Does the 'heavy lifting' of initializing the plugin's behavior
     */
    public funciton init()
    {
        $this->bot->onChannel('/^hello$/', function($request, $matches) {
            return Response::msg($request-getSource(), "Hi, {$request->getSendingUser()}!");
        });
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

