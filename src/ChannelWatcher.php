<?php
namespace Power2All\Modules\ChannelWatcher;

use WildPHP\BaseModule;
use WildPHP\CoreModules\Connection\IrcDataObject;

/**
 * Class ChannelWatcher
 * @package Power2All\Modules\ChannelWatcher
 */
class ChannelWatcher extends BaseModule
{
    /**
     * @var $connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $channelUsers;

    /**
     * @var array
     */
    protected $channelPrefixes;

    public function setup()
    {
        // Register our command.
        $this->getEventEmitter()->on('irc.data.in.353', [$this, 'namesInit']);
        $this->getEventEmitter()->on('irc.data.in.join', [$this, 'joinUser']);
        $this->getEventEmitter()->on('irc.data.in.part', [$this, 'partUser']);
        $this->getEventEmitter()->on('irc.data.in.quit', [$this, 'quitUser']);
        $this->getEventEmitter()->on('irc.data.in.nick', [$this, 'nickUser']);

        $this->getEventEmitter()->on('irc.command.users', [$this, 'usersList']);

        // Needed connectors
        $this->connection = $this->getModule('Connection');

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

    public function usersList($command, $params, IrcDataObject $data)
    {
        $this->command = $command;
        $this->params = $params;
        $this->data = $data;
        $channel = str_replace('#', '', $data->getMessage()['params']['receivers']);

        $this->user = $data->getMessage()['nick'];

        $message = 'There are ' . count(array_keys($this->getUsers($channel))) . ' users in this channel';

        $this->connection->write($this->connection->getGenerator()->ircNotice($this->user, $message));
    }

    /**
     * @param IrcDataObject $object
     */
    public function namesInit(IrcDataObject $object)
    {
        // Getting the channel name this happens
        $channel = str_replace('#', '', $object->getMessage()['params'][2]);
        $users = trim($object->getMessage()['params']['tail']);

        // Fix the users into a array
        $usersArray = explode(' ', $users);

        //

        // Go through each user, add it to the array with the correct prefix
        foreach ($usersArray as $keyUser => $valueUser) {
            $valueUser = str_replace(array_keys($this->channelPrefixes), '', $valueUser);
            $this->addUser($channel, $valueUser);
        }
    }

    /**
     * @param IrcDataObject $object
     */
    public function joinUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];
        $channel = str_replace('#', '', $object->getMessage()['params']['channels']);

        $this->addUser($channel, $nick);

        return;
    }

    /**
     * @param IrcDataObject $object
     */
    public function partUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];
        $channel = str_replace('#', '', $object->getMessage()['params']['channels']);

        $this->removeUser($channel, $nick);

        return;
    }

    /**
     * @param IrcDataObject $object
     */
    public function quitUser(IrcDataObject $object)
    {
        $nick = $object->getMessage()['nick'];

        // User disconnected, we make sure it's removed in all arrays
        foreach($this->getChannelsAndUsers() as $key => $value) {
            if (isset($value[$nick])) {
                $this->removeUser($key, $nick);
            }
        }

        return;
    }

    /**
     * @param IrcDataObject $object
     */
    public function nickUser(IrcDataObject $object)
    {
        $oldNick = $object->getMessage()['nick'];
        $newNick = $object->getMessage()['params']['nickname'];

        // Since the nickname change doesn't give a channel, we make sure it's changed everywhere
        foreach($this->getChannelsAndUsers() as $key => $value) {
            if (isset($value[$oldNick])) {
                $this->removeUser($key, $oldNick);
                $this->addUser($key, $newNick);
            }
        }

        return;
    }

    /**
     * @param $channel
     * @param $nickname
     * @return bool
     */
    public function addUser($channel, $nickname)
    {
        $this->channelUsers[$channel][$nickname] = true;

        return true;
    }

    /**
     * @param $channel
     * @param $nickname
     * @return bool
     */
    public function getUser($channel, $nickname)
    {
        if (isset($this->channelUsers[$channel][$nickname])) {
            return $this->channelUsers[$channel][$nickname];
        }

        return false;
    }

    /**
     * @param $channel
     * @param $nickname
     * @return bool
     */
    public function removeUser($channel, $nickname)
    {
        if (isset($this->channelUsers[$channel][$nickname])) {
            unset($this->channelUsers[$channel][$nickname]);
            return true;
        }

        return false;
    }

    /**
     * @param $channel
     * @return bool|mixed
     */
    public function getUsers($channel)
    {
        if (isset($this->channelUsers[$channel])) {
            return $this->channelUsers[$channel];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getChannelsAndUsers()
    {
        return $this->channelUsers;
    }

    /**
     * @param $channel
     * @return bool
     */
    public function setChannel($channel)
    {
        if (false === isset($this->channelUsers[$channel])) {
            $this->channelUsers[$channel] = array();

            return true;
        }

        return false;
    }

    /**
     * @param $channel
     * @return bool
     */
    public function removeChannel($channel) {
        if (isset($this->channelUsers[$channel])) {
            unset($this->channelUsers[$channel]);
            return true;
        }

        return false;
    }
}