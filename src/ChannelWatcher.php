<?php
namespace Power2All\Modules\ChannelWatcher;

use WildPHP\BaseModule;
use WildPHP\CoreModules\Connection\IrcDataObject;

class ChannelWatcher extends BaseModule
{
    protected $user;

    /**
     * @var array
     */
    protected $channelUsers;

    public function setup()
    {
        // Register our command.
        $this->getEventEmitter()->on('irc.data.in.353', [$this, 'namesInit']);
        $this->getEventEmitter()->on('irc.data.in.join', [$this, 'joinUser']);
        $this->getEventEmitter()->on('irc.data.in.part', [$this, 'partUser']);
        $this->getEventEmitter()->on('irc.data.in.quit', [$this, 'quitUser']);
        $this->getEventEmitter()->on('irc.data.in.nick', [$this, 'nickUser']);
        $this->getEventEmitter()->on('irc.data.in.mode', [$this, 'modeUser']);

        // Fix our channels list
        $this->channelUsers = array();
    }

    public function namesInit(IrcDataObject $object)
    {
        var_dump($object->getMessage());
    }

    public function joinUser(IrcDataObject $object)
    {
        $this->user = $object->getMessage()['nick'];
        var_dump('Join user: ' . $this->user);
        var_dump($object->getMessage());

        return;
    }

    public function partUser(IrcDataObject $object)
    {
        $this->user = $object->getMessage()['nick'];
        var_dump('Part user: ' . $this->user);
        var_dump($object->getMessage());

        return;
    }

    public function quitUser(IrcDataObject $object)
    {
        $this->user = $object->getMessage()['nick'];
        var_dump('Quit user: ' . $this->user);
        var_dump($object->getMessage());

        return;
    }

    public function nickUser(IrcDataObject $object)
    {
        $this->user = $object->getMessage()['nick'];
        var_dump('Renamed user: ' . $this->user);
        var_dump($object->getMessage());

        return;
    }

    public function modeUser(IrcDataObject $object)
    {
        $this->user = $object->getMessage()['nick'];
        var_dump('Mode change user: ' . $this->user);
        var_dump($object->getMessage());

        return;
    }

    public function setUser($channel, $nickname, $prefix)
    {
        $this->channelUsers[$channel][$nickname] = array(
            'prefix' => $prefix
        );

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