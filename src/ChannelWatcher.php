<?php
namespace Power2All\Modules\ChannelWatcher;

use WildPHP\BaseModule;
use WildPHP\CoreModules\Connection\IrcDataObject;

class ChannelWatcher extends BaseModule
{
    /**
     * @var array
     */
    protected $channelUsers;

    protected $channelPrefixes;

    public function setup()
    {
        // Register our command.
        $this->getEventEmitter()->on('irc.data.in.353', [$this, 'namesInit']);
        $this->getEventEmitter()->on('irc.data.in.join', [$this, 'joinUser']);
        $this->getEventEmitter()->on('irc.data.in.part', [$this, 'partUser']);
        $this->getEventEmitter()->on('irc.data.in.quit', [$this, 'quitUser']);
        $this->getEventEmitter()->on('irc.data.in.nick', [$this, 'nickUser']);

        // Fix our channels list
        $this->channelUsers = array();

        // Set the prefixes, these can be changed here, if needed
        $this->channelPrefixes = array(
            '~' => 'q',
            '&' => 'a',
            '@' => 'o',
            '%' => 'h',
            '+' => 'v'
        );
    }

    public function namesInit(IrcDataObject $object)
    {
        // Getting the channel name this happens
        $channel = str_replace('#', '', $object->getMessage()['params'][2]);
        $users = trim($object->getMessage()['params']['tail']);

        // Fix the users into a array
        $usersArray = explode(' ', $users);

        // Go through each user, add it to the array with the correct prefix
        foreach ($usersArray as $keyUser => $valueUser) {
            $valueUser = str_replace(array_keys($this->channelPrefixes), '', $valueUser);
            $this->addUser($channel, $valueUser);
        }
    }

    public function joinUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];
        $channel = str_replace('#', '', $object->getMessage()['params']['channels']);

        $this->addUser($channel, $nick, null);

        return;
    }

    public function partUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];
        $channel = str_replace('#', '', $object->getMessage()['params']['channels']);

        $this->removeUser($channel, $nick);

        return;
    }

    public function quitUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];
        $channel = str_replace('#', '', $object->getMessage()['params']['channels']);

        $this->removeUser($channel, $nick);

        return;
    }

    public function nickUser(IrcDataObject $object)
    {
        $oldNick = $object->getMessage()['nick'];
        $newNick = $object->getMessage()['params']['nickname'];

        // Since the nickname change doesn't give a channel, we make sure it's changed everywhere
        foreach($this->getChannelsAndUsers() as $key => $value) {
            if (isset($value[$oldNick])) {
                $this->removeUser($key, $oldNick);
                $this->addUser($key, $newNick, null);
            }
        }

        return;
    }

    public function addUser($channel, $nickname)
    {
        $this->channelUsers[$channel][$nickname] = true;

        return true;
    }

    public function getUser($channel, $nickname)
    {
        if (isset($this->channelUsers[$channel][$nickname])) {
            return $this->channelUsers[$channel][$nickname];
        }

        return false;
    }

    public function removeUser($channel, $nickname)
    {
        if (isset($this->channelUsers[$channel][$nickname])) {
            unset($this->channelUsers[$channel][$nickname]);
            return true;
        }

        return false;
    }

    public function getUsers($channel)
    {
        if (isset($this->channelUsers[$channel])) {
            return $this->channelUsers[$channel];
        }

        return false;
    }

    public function getChannelsAndUsers()
    {
        return $this->channelUsers;
    }

    public function setChannel($channel)
    {
        $this->channelUsers[$channel] = array();

        return true;
    }

    public function removeChannel($channel) {
        if (isset($this->channelUsers[$channel])) {
            unset($this->channelUsers[$channel]);
            return true;
        }

        return false;
    }
}